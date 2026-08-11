<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\TenantContext;
use App\Core\Url;
use App\Core\View;
use App\Models\Tenant;

class TenantSettingsController
{
    public function edit(): void
    {
        Auth::requireRole('admin');
        $tenant = Tenant::find(TenantContext::requireTenant());
        View::render('tenant_settings/edit', ['title' => 'Company Settings', 'tenant' => $tenant, 'errors' => []]);
    }

    public function update(): void
    {
        Auth::requireRole('admin');
        if (!Csrf::verifyRequest()) {
            Flash::error('Your session expired, please try again.');
            header('Location: ' . Url::to('tenant-settings'));
            exit;
        }
        $name = trim((string) Request::post('name'));
        $domain = trim((string) Request::post('primary_domain'));

        if ($name === '') {
            Flash::error('Company name is required.');
            header('Location: ' . Url::to('tenant-settings'));
            exit;
        }

        Tenant::update(TenantContext::requireTenant(), ['name' => $name, 'primary_domain' => $domain ?: null]);
        Flash::success('Company settings updated.');
        header('Location: ' . Url::to('tenant-settings'));
        exit;
    }
}
