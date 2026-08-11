<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Url;
use App\Core\View;
use App\Models\User;

class UserController
{
    private const ROLES = ['viewer', 'editor', 'admin', 'owner'];

    public function index(): void
    {
        Auth::requireRole('admin');
        View::render('users/index', [
            'title' => 'Users & Roles',
            'records' => User::allForTenant(),
        ]);
    }

    public function create(): void
    {
        Auth::requireRole('admin');
        View::render('users/form', ['title' => 'Users & Roles', 'mode' => 'create', 'record' => [], 'errors' => []]);
    }

    public function store(): void
    {
        Auth::requireRole('admin');
        if (!Csrf::verifyRequest()) {
            Flash::error('Your session expired, please try again.');
            header('Location: ' . Url::to('users'));
            exit;
        }

        $name = trim((string) Request::post('name'));
        $email = trim((string) Request::post('email'));
        $role = in_array(Request::post('role'), self::ROLES, true) ? Request::post('role') : 'viewer';
        $password = (string) Request::post('password');

        $errors = [];
        if ($name === '') $errors['name'] = 'Name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email.';
        elseif (User::emailExists($email)) $errors['email'] = 'That email is already registered.';
        if (strlen($password) < 8) $errors['password'] = 'Set a temporary password of at least 8 characters -- share it with them directly.';

        if (!empty($errors)) {
            View::render('users/form', [
                'title' => 'Users & Roles', 'mode' => 'create',
                'record' => ['name' => $name, 'email' => $email, 'role' => $role], 'errors' => $errors,
            ]);
            return;
        }

        User::inviteToTenant([
            'name' => $name, 'email' => $email, 'role' => $role,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);
        Flash::success($name . ' was added. Share their temporary password with them directly -- there is no outbound email on this install.');
        header('Location: ' . Url::to('users'));
        exit;
    }

    public function edit(array $params): void
    {
        Auth::requireRole('admin');
        $record = User::findInTenant((int) $params['id']);
        if (!$record) { http_response_code(404); exit('Not found.'); }
        View::render('users/form', ['title' => 'Users & Roles', 'mode' => 'edit', 'record' => $record, 'errors' => []]);
    }

    public function update(array $params): void
    {
        Auth::requireRole('admin');
        if (!Csrf::verifyRequest()) {
            Flash::error('Your session expired, please try again.');
            header('Location: ' . Url::to('users'));
            exit;
        }
        $id = (int) $params['id'];
        $name = trim((string) Request::post('name'));
        $role = in_array(Request::post('role'), self::ROLES, true) ? Request::post('role') : 'viewer';
        $status = Request::post('status') === 'disabled' ? 'disabled' : 'active';

        if ($id === Auth::id() && $role !== 'owner') {
            Flash::error('You cannot demote your own account.');
            header('Location: ' . Url::to('users'));
            exit;
        }

        $errors = [];
        if ($name === '') $errors['name'] = 'Name is required.';
        if (!empty($errors)) {
            $record = User::findInTenant($id);
            View::render('users/form', ['title' => 'Users & Roles', 'mode' => 'edit', 'record' => array_merge($record, ['name' => $name, 'role' => $role, 'status' => $status]), 'errors' => $errors]);
            return;
        }

        User::updateInTenant($id, ['name' => $name, 'role' => $role, 'status' => $status]);
        Flash::success('User updated.');
        header('Location: ' . Url::to('users'));
        exit;
    }

    public function resetPassword(array $params): void
    {
        Auth::requireRole('admin');
        if (!Csrf::verifyRequest()) {
            Flash::error('Your session expired, please try again.');
            header('Location: ' . Url::to('users'));
            exit;
        }
        $password = (string) Request::post('password');
        if (strlen($password) < 8) {
            Flash::error('New password must be at least 8 characters.');
            header('Location: ' . Url::to('users/edit/' . (int) $params['id']));
            exit;
        }
        User::resetPasswordInTenant((int) $params['id'], password_hash($password, PASSWORD_BCRYPT));
        Flash::success('Password reset. Share the new password with the user directly.');
        header('Location: ' . Url::to('users'));
        exit;
    }

    public function destroy(array $params): void
    {
        Auth::requireRole('admin');
        if (!Csrf::verifyRequest()) {
            Flash::error('Your session expired, please try again.');
            header('Location: ' . Url::to('users'));
            exit;
        }
        $id = (int) $params['id'];
        if ($id === Auth::id()) {
            Flash::error('You cannot delete your own account.');
            header('Location: ' . Url::to('users'));
            exit;
        }
        User::deleteInTenant($id);
        Flash::success('User removed.');
        header('Location: ' . Url::to('users'));
        exit;
    }
}
