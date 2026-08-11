<?php

namespace App\Core;

class Flash
{
    public static function set(string $type, string $message): void
    {
        $_SESSION['_flash'][$type][] = $message;
    }

    public static function success(string $message): void
    {
        self::set('success', $message);
    }

    public static function error(string $message): void
    {
        self::set('error', $message);
    }

    /**
     * Returns all pending flash messages and clears them.
     */
    public static function consume(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }
}
