<?php

namespace App\Core;

class Str
{
    public static function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }

    /**
     * Builds a tracking form_id the same way the original spreadsheet did:
     * {page_type_short}-{service_vertical}-{service}-{form_type}-{form_location}
     * Empty segments (e.g. no service) are kept as empty strings so the same
     * double-hyphen pattern the sheet used (e.g. "cp-gl--contact_form-hero") is preserved.
     */
    public static function buildFormId(string $pageTypeShort, string $verticalCode, string $serviceSlug, string $formType, string $formLocation): string
    {
        $segments = [
            strtolower($pageTypeShort),
            strtolower($verticalCode),
            strtolower($serviceSlug),
            strtolower($formType),
            strtolower($formLocation),
        ];
        return implode('-', $segments);
    }
}
