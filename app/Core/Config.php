<?php

namespace App\Core;

class Config
{
    private static ?array $data = null;

    private static function load(): array
    {
        if (self::$data === null) {
            $path = APP_ROOT . '/config/config.php';
            if (!is_file($path)) {
                http_response_code(500);
                exit('Missing config/config.php. Copy config/config.sample.php to config/config.php and fill in your values.');
            }
            self::$data = require $path;
        }
        return self::$data;
    }

    /**
     * Dot-notation getter, e.g. Config::get('db.host') or Config::get('db').
     */
    public static function get(string $key, $default = null)
    {
        $data = self::load();
        $segments = explode('.', $key);
        $value = $data;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}
