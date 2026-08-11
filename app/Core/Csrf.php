<?php

namespace App\Core;

class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
    }

    public static function verify(?string $token): bool
    {
        return is_string($token) && !empty($_SESSION[self::SESSION_KEY])
            && hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    public static function verifyRequest(): bool
    {
        return self::verify(Request::post('_csrf'));
    }
}
