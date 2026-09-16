<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ActivityLogger;
use App\Services\PermissionService;
use App\Services\WorkspaceService;
use Spartan\Controller;

class RoleController extends Controller
{
    private function getContext(): array
    {
        $userId = (int)$this->auth->id();
        $wsService = new WorkspaceService($this->session);
        return [
            'userId'      => $userId,
            'workspaceId' => (int)$wsService->getActiveWorkspaceId($userId),
            'workspace'   => $wsService->getActiveWorkspace($userId),
            'workspaces'  => $wsService->getUserWorkspaces($userId),
        ];
    }

    public function index(): string
    {
        $ctx = $this->getContext();
        $wsId = $ctx['workspaceId'];
        $permService = new PermissionService();

        // Check if user has permission to view/manage roles
        if (!user_can('settings.roles') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', __('app.common.unauthorized_action') ?? 'You are not authorized to manage roles.');
            $this->redirect('/settings/workspace');
            return '';
        }

        $roles = $permService->getWorkspaceRoles($wsId);
        $permissionsGrouped = $permService->getAllPermissionsGrouped();

        return $this->render('settings/roles/index', [
            'title'              => __('app.settings.roles.title') ?? 'Roles & Permissions',
            'roles'              => $roles,
            'permissionsGrouped' => $permissionsGrouped,
            'workspace'          => $ctx['workspace'],
            'workspaces'         => $ctx['workspaces'],
            'currentRole'        => active_user_role(),
        ]);
    }

    public function store(): void
    {
        $ctx = $this->getContext();
        $wsId = $ctx['workspaceId'];

        if (!user_can('settings.roles') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', 'You are not authorized to create roles.');
            $this->redirect('/settings/roles');
            return;
        }

        $name = trim((string)$this->request->post('name'));
        $description = trim((string)$this->request->post('description'));
        $permissions = (array)($this->request->post('permissions') ?? []);

        if ($name === '') {
            $this->session->setFlash('error', 'Role name cannot be empty.');
            $this->redirect('/settings/roles');
            return;
        }

        $permService = new PermissionService();
        $roleId = $permService->createCustomRole($wsId, $name, $description, $permissions);

        try {
            (new ActivityLogger())->log(
                $wsId,
                $ctx['userId'],
                'created_role',
                'roles',
                $roleId,
                "Created custom role '{$name}' with " . count($permissions) . " permissions"
            );
        } catch (\Throwable $e) {}

        $this->session->setFlash('success', "Role '{$name}' created successfully with " . count($permissions) . " permissions.");
        $this->redirect('/settings/roles');
    }

    public function update(string|int $id): void
    {
        $ctx = $this->getContext();
        $wsId = $ctx['workspaceId'];
        $roleId = (int)$id;

        if (!user_can('settings.roles') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', 'You are not authorized to update roles.');
            $this->redirect('/settings/roles');
            return;
        }

        $name = trim((string)$this->request->post('name'));
        $description = trim((string)$this->request->post('description'));
        $permissions = (array)($this->request->post('permissions') ?? []);

        if ($name === '') {
            $this->session->setFlash('error', 'Role name cannot be empty.');
            $this->redirect('/settings/roles');
            return;
        }

        $permService = new PermissionService();
        $updated = $permService->updateCustomRole($roleId, $wsId, $name, $description, $permissions);

        if ($updated) {
            try {
                (new ActivityLogger())->log(
                    $wsId,
                    $ctx['userId'],
                    'updated_role',
                    'roles',
                    $roleId,
                    "Updated role '{$name}' permissions (" . count($permissions) . " granted)"
                );
            } catch (\Throwable $e) {}

            $this->session->setFlash('success', "Role '{$name}' updated successfully.");
        } else {
            $this->session->setFlash('error', 'Could not update role. Protected system presets cannot be modified.');
        }

        $this->redirect('/settings/roles');
    }

    public function delete(string|int $id): void
    {
        $ctx = $this->getContext();
        $wsId = $ctx['workspaceId'];
        $roleId = (int)$id;

        if (!user_can('settings.roles') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', 'You are not authorized to delete roles.');
            $this->redirect('/settings/roles');
            return;
        }

        $permService = new PermissionService();
        $res = $permService->deleteCustomRole($roleId, $wsId);

        if ($res['success']) {
            $this->session->setFlash('success', $res['message']);
        } else {
            $this->session->setFlash('error', $res['message']);
        }

        $this->redirect('/settings/roles');
    }
}
