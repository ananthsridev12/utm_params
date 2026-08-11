<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Url;
use App\Core\View;
use App\Models\Tenant;

/**
 * Minimal platform-level back office: list every tenant workspace and
 * suspend/reactivate them. Only users with users.is_super_admin = 1.
 */
class SuperAdminController
{
    public function index(): void
    {
        Auth::requireSuperAdmin();
        View::render('super_admin/index', ['title' => 'Super Admin', 'tenants' => Tenant::all()]);
    }

    public function suspend(array $params): void
    {
        Auth::requireSuperAdmin();
        if (!Csrf::verifyRequest()) {
            Flash::error('Your session expired, please try again.');
            header('Location: ' . Url::to('super-admin'));
            exit;
        }
        Tenant::setStatus((int) $params['id'], 'suspended');
        Flash::success('Workspace suspended.');
        header('Location: ' . Url::to('super-admin'));
        exit;
    }

    public function activate(array $params): void
    {
        Auth::requireSuperAdmin();
        if (!Csrf::verifyRequest()) {
            Flash::error('Your session expired, please try again.');
            header('Location: ' . Url::to('super-admin'));
            exit;
        }
        Tenant::setStatus((int) $params['id'], 'active');
        Flash::success('Workspace reactivated.');
        header('Location: ' . Url::to('super-admin'));
        exit;
    }
}
