<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Spartan\Controller;

class AuthController extends Controller
{
    public function showLogin(): string
    {
        if ($this->auth->check()) {
            $this->redirect('/dashboard');
            return '';
        }
        return $this->render('auth/login', [
            'title' => 'Sign In',
        ]);
    }

    public function login(): void
    {
        $body = $this->request->getBody();
        $email = trim((string)($body['email'] ?? ''));
        $password = (string)($body['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->session->setFlash('error', 'Please enter email and password.');
            $this->redirect('/login');
            return;
        }

        $user = (new User)->table()->where('email', $email)->first();
        if (!$user || !password_verify($password, $user['password'])) {
            $this->session->setFlash('error', 'Invalid email or password.');
            $this->redirect('/login');
            return;
        }

        // Login user
        $this->auth->loginUsingId((int)$user['id']);
        $this->session->regenerate();

        // Set active workspace
        $membership = (new Membership)->table()->where('user_id', (int)$user['id'])->first();
        if ($membership) {
            $this->session->set('active_workspace_id', (int)$membership['workspace_id']);
        }

        $this->session->setFlash('success', 'Welcome back, ' . $user['name'] . '!');
        $this->redirect('/dashboard');
    }

    public function showRegister(): string
    {
        if ($this->auth->check()) {
            $this->redirect('/dashboard');
            return '';
        }
        return $this->render('auth/register', [
            'title' => 'Create an Account',
        ]);
    }

    public function register(): void
    {
        $body = $this->request->getBody();
        $name = trim((string)($body['name'] ?? ''));
        $email = trim((string)($body['email'] ?? ''));
        $password = (string)($body['password'] ?? '');
        $workspaceName = trim((string)($body['workspace_name'] ?? ''));

        if ($name === '' || $email === '' || strlen($password) < 8 || $workspaceName === '') {
            $this->session->setFlash('error', 'Please fill in all fields (password minimum 8 characters).');
            $this->redirect('/register');
            return;
        }

        $existing = (new User)->table()->where('email', $email)->first();
        if ($existing) {
            $this->session->setFlash('error', 'An account with this email already exists.');
            $this->redirect('/register');
            return;
        }

        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $userId = (new User)->table()->insert([
            'name'       => $name,
            'email'      => $email,
            'password'   => $hashed,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $workspaceName), '-')) . '-' . rand(100, 999);
        $wsId = (new Workspace)->table()->insert([
            'name'       => $workspaceName,
            'slug'       => $slug,
            'owner_id'   => $userId,
            'settings'   => json_encode(['currency' => 'USD', 'timezone' => 'UTC']),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        (new Membership)->table()->insert([
            'workspace_id' => $wsId,
            'user_id'      => $userId,
            'role'         => 'admin',
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        // Default admin role
        (new User)->table('user_roles')->insert([
            'user_id' => $userId,
            'role_id' => 1,
        ]);

        $this->auth->loginUsingId((int)$userId);
        $this->session->regenerate();
        $this->session->set('active_workspace_id', (int)$wsId);

        $this->session->setFlash('success', 'Your CRX workspace has been created!');
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        $this->auth->logout();
        $this->session->destroy();
        $this->redirect('/login');
    }
}
