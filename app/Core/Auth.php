<?php

namespace App\Core;

use App\Models\User;

class Auth
{
    private const SESSION_KEY = 'user_id';

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);
        if (!$user || $user['status'] !== 'active') {
            return false;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = (int) $user['id'];
        User::touchLastLogin((int) $user['id']);
        return true;
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        session_regenerate_id(true);
    }

    public static function check(): bool
    {
        return !empty($_SESSION[self::SESSION_KEY]);
    }

    private static ?array $cachedUser = null;
    private static bool $resolved = false;

    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$cachedUser;
        }
        self::$resolved = true;
        if (empty($_SESSION[self::SESSION_KEY])) {
            return null;
        }
        self::$cachedUser = User::find((int) $_SESSION[self::SESSION_KEY]);
        return self::$cachedUser;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function isSuperAdmin(): bool
    {
        $user = self::user();
        return (bool) ($user['is_super_admin'] ?? false);
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    /**
     * Role hierarchy: owner > admin > editor > viewer.
     */
    public static function hasAtLeast(string $role): bool
    {
        $levels = ['viewer' => 1, 'editor' => 2, 'admin' => 3, 'owner' => 4];
        $current = $levels[self::role()] ?? 0;
        return $current >= ($levels[$role] ?? 99);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ' . Url::to('login'));
            exit;
        }
    }

    public static function requireRole(string $role): void
    {
        self::requireLogin();
        if (!self::hasAtLeast($role)) {
            http_response_code(403);
            Flash::error('You do not have permission to do that.');
            header('Location: ' . Url::to('dashboard'));
            exit;
        }
    }

    public static function requireSuperAdmin(): void
    {
        self::requireLogin();
        if (!self::isSuperAdmin()) {
            http_response_code(403);
            exit('Forbidden');
        }
    }
}
