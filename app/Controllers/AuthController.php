<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Str;
use App\Core\Url;
use App\Core\View;
use App\Models\Tenant;
use App\Models\User;
use PDOException;
use Throwable;

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            header('Location: ' . Url::to('dashboard'));
            exit;
        }
        View::render('auth/login', ['title' => 'Log in'], 'layout/auth');
    }

    public function login(): void
    {
        if (!Csrf::verifyRequest()) {
            Flash::error('Your session expired, please try again.');
            header('Location: ' . Url::to('login'));
            exit;
        }

        $email = trim((string) Request::post('email'));
        $password = (string) Request::post('password');

        if ($email === '' || $password === '') {
            Flash::error('Enter your email and password.');
            header('Location: ' . Url::to('login'));
            exit;
        }

        if (!Auth::attempt($email, $password)) {
            Flash::error('Incorrect email or password.');
            header('Location: ' . Url::to('login'));
            exit;
        }

        $user = Auth::user();
        if ($user && $user['tenant_id'] !== null && ($user['tenant_status'] ?? 'active') !== 'active') {
            Auth::logout();
            Flash::error('This account\'s workspace has been suspended.');
            header('Location: ' . Url::to('login'));
            exit;
        }

        // Platform super admins with no tenant of their own land on the back office.
        if ($user && $user['tenant_id'] === null) {
            header('Location: ' . Url::to('super-admin'));
            exit;
        }

        header('Location: ' . Url::to('dashboard'));
        exit;
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: ' . Url::to('login'));
        exit;
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            header('Location: ' . Url::to('dashboard'));
            exit;
        }
        View::render('auth/register', ['title' => 'Create your workspace', 'old' => []], 'layout/auth');
    }

    public function register(): void
    {
        if (!Csrf::verifyRequest()) {
            Flash::error('Your session expired, please try again.');
            header('Location: ' . Url::to('register'));
            exit;
        }

        $companyName = trim((string) Request::post('company_name'));
        $name = trim((string) Request::post('name'));
        $email = trim((string) Request::post('email'));
        $password = (string) Request::post('password');
        $passwordConfirm = (string) Request::post('password_confirm');

        $errors = [];
        if ($companyName === '') $errors[] = 'Company name is required.';
        if ($name === '') $errors[] = 'Your name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $passwordConfirm) $errors[] = 'Passwords do not match.';
        if ($email && User::emailExists($email)) $errors[] = 'That email is already registered.';

        if (!empty($errors)) {
            foreach ($errors as $e) Flash::error($e);
            View::render('auth/register', [
                'title' => 'Create your workspace',
                'old' => ['company_name' => $companyName, 'name' => $name, 'email' => $email],
            ], 'layout/auth');
            return;
        }

        $slugBase = Str::slugify($companyName) ?: 'workspace';
        $slug = $slugBase;
        $suffix = 1;
        while (Tenant::slugExists($slug)) {
            $slug = $slugBase . '-' . (++$suffix);
        }

        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $tenantId = Tenant::create($companyName, $slug);
            User::create([
                'tenant_id' => $tenantId,
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                'role' => 'owner',
            ]);
            $this->seedDefaultSnippetTemplates($pdo, $tenantId);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            Flash::error('Could not create your workspace. Please try again.');
            header('Location: ' . Url::to('register'));
            exit;
        }

        Auth::attempt($email, $password);
        Flash::success('Welcome! Your workspace "' . $companyName . '" is ready.');
        header('Location: ' . Url::to('dashboard'));
        exit;
    }

    private function seedDefaultSnippetTemplates($pdo, int $tenantId): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO snippet_templates (tenant_id, key_name, name, template, is_default) VALUES (?, ?, ?, ?, 1)'
        );
        $stmt->execute([$tenantId, 'ga4', 'GA4 dataLayer push', <<<JS
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({
  'event': '{{event_name}}',
  'page_url': window.location.href,
  'service_vertical': '{{service_vertical}}',
  'form_type': '{{form_type}}',
  'form_location': '{{form_location}}',
  'lead_magnet_name': {{lead_magnet_name_js}},
  'funnel_stage': '{{funnel_stage}}',
  'utm_source': getTrafficSource(),
  'utm_campaign': getUrlParam('utm_campaign'),
  'utm_medium': getUrlParam('utm_medium')
});
JS
        ]);
        $stmt->execute([$tenantId, 'crm', 'CRM lead object', <<<JS
const leadData = {
  form_id:      '{{form_id}}',
  page_url:     window.location.href,
  vertical:     '{{service_vertical}}',
  service:      {{service_js}},
  utm_source:   getUrlParam('utm_source'),
  utm_medium:   getUrlParam('utm_medium'),
  utm_campaign: getUrlParam('utm_campaign'),
  utm_cv:       getUrlParam('utm_cv'),
  utm_content:  getUrlParam('utm_content'),
  utm_term:     getUrlParam('utm_term')
};
JS
        ]);
    }
}
