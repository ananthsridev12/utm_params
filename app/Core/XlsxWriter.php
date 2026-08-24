<?php

namespace App\Core;

use ZipArchive;

/**
 * Minimal, dependency-free .xlsx (Office Open XML) writer built on PHP's
 * built-in ZipArchive -- no Composer/PhpSpreadsheet, matching this project's
 * "no framework, no Composer dependency" rule for shared/cPanel hosting (see
 * CLAUDE.md). Supports multiple sheets with a bold header row and plain
 * string/number cells -- enough for a readable data export, not a full
 * spreadsheet engine (no formulas, column widths, or custom styling beyond
 * the one bold header format).
 */
class XlsxWriter
{
    /** @var array<int, array{name:string, header:array<int,string>, rows:array<int,array<int,mixed>>}> */
    private array $sheets = [];

    /** @param array<int,string> $header @param array<int,array<int,mixed>> $rows */
    public function addSheet(string $name, array $header, array $rows): void
    {
        $safeName = substr((string) preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $name), 0, 31);
        $this->sheets[] = ['name' => $safeName ?: 'Sheet', 'header' => $header, 'rows' => $rows];
    }

    /** Builds the workbook, sends download headers, streams it, and exits. */
    public function send(string $filename): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        foreach ($this->sheets as $i => $sheet) {
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $this->sheetXml($sheet));
        }
        $zip->close();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . (string) filesize($tmpFile));
        readfile($tmpFile);
        unlink($tmpFile);
        exit;
    }

    private function contentTypesXml(): string
    {
        $overrides = '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        foreach ($this->sheets as $i => $sheet) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . ($i + 1) . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . $overrides
            . '</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbookXml(): string
    {
        $sheetsXml = '';
        foreach ($this->sheets as $i => $sheet) {
            $sheetsXml .= '<sheet name="' . $this->escape($sheet['name']) . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 2) . '"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheetsXml . '</sheets>'
            . '</workbook>';
    }

    private function workbookRelsXml(): string
    {
        // rId1 is reserved for styles.xml; each sheet i takes rId(i+2), matching the
        // r:id values workbookXml() assigns.
        $rels = '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        foreach ($this->sheets as $i => $sheet) {
            $rels .= '<Relationship Id="rId' . ($i + 2) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . ($i + 1) . '.xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>';
    }

    private function stylesXml(): string
    {
        // Two cell formats: index 0 = default body cell, index 1 = bold header row.
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="10"/><name val="Calibri"/></font><font><b/><sz val="10"/><name val="Calibri"/></font></fonts>'
            . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '</cellXfs>'
            . '</styleSheet>';
    }

    /** @param array{name:string, header:array<int,string>, rows:array<int,array<int,mixed>>} $sheet */
    private function sheetXml(array $sheet): string
    {
        $rowsXml = '';
        $rowNum = 1;
        if (!empty($sheet['header'])) {
            $rowsXml .= $this->rowXml($rowNum, $sheet['header'], 1);
            $rowNum++;
        }
        foreach ($sheet['rows'] as $row) {
            $rowsXml .= $this->rowXml($rowNum, $row, 0);
            $rowNum++;
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>' . $rowsXml . '</sheetData>'
            . '</worksheet>';
    }

    /** @param array<int,mixed> $cells */
    private function rowXml(int $rowNum, array $cells, int $styleIndex): string
    {
        $cellsXml = '';
        $col = 0;
        foreach (array_values($cells) as $value) {
            $ref = $this->columnLetter($col) . $rowNum;
            $styleAttr = $styleIndex ? ' s="' . $styleIndex . '"' : '';
            if (is_int($value) || is_float($value)) {
                $cellsXml .= '<c r="' . $ref . '"' . $styleAttr . '><v>' . $value . '</v></c>';
            } else {
                $text = $this->escape((string) $value);
                $cellsXml .= '<c r="' . $ref . '" t="inlineStr"' . $styleAttr . '><is><t xml:space="preserve">' . $text . '</t></is></c>';
            }
            $col++;
        }
        return '<row r="' . $rowNum . '">' . $cellsXml . '</row>';
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = intdiv($index - 1, 26);
        }
        return $letter;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
