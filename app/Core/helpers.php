<?php
/**
 * Small global helper functions for use inside plain-PHP view templates,
 * so views don't need "use" imports for every render.
 */

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;

function url(string $route, array $params = []): string
{
    return Url::to($route, $params);
}

function asset(string $path): string
{
    return Url::asset($path);
}

function e($value): string
{
    return View::e($value === null ? '' : (string) $value);
}

function csrf_field(): string
{
    return Csrf::field();
}

function current_user(): ?array
{
    return Auth::user();
}

function has_role(string $role): bool
{
    return Auth::hasAtLeast($role);
}

function status_badge(?string $status): string
{
    $status = $status ?: 'inactive';
    return '<span class="badge ' . e($status) . '">' . e(ucfirst($status)) . '</span>';
}
