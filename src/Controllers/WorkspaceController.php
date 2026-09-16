<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Activity;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceService;
use Spartan\Controller;

class WorkspaceController extends Controller
{
    public function switchWorkspace(): void
    {
        $userId = (int)$this->auth->id();
        $targetId = (int)$this->request->getParam('workspace_id');

        $wsService = new WorkspaceService($this->session);
        if ($wsService->setActiveWorkspace($userId, $targetId)) {
            $this->session->setFlash('success', 'Switched workspace.');
        } else {
            $this->session->setFlash('error', 'You do not have access to this workspace.');
        }

        $this->redirect('/dashboard');
    }

    public function settings(): string
    {
        $userId = (int)$this->auth->id();
        $wsService = new WorkspaceService($this->session);
        $workspace = $wsService->getActiveWorkspace($userId);
        $workspaces = $wsService->getUserWorkspaces($userId);
        $wsId = (int)$workspace['id'];

        $members = (new Membership)->table()
            ->join('users', 'memberships.user_id', 'users.id')
            ->where('memberships.workspace_id', $wsId)
            ->select('memberships.id as membership_id', 'users.id as user_id', 'users.name', 'users.email', 'memberships.role', 'memberships.created_at')
            ->get();

        $roles = (new \App\Services\PermissionService())->getWorkspaceRoles($wsId);

        return $this->render('settings/workspace', [
            'title'         => 'Workspace Settings & Team',
            'workspace'     => $workspace,
            'workspaces'    => $workspaces,
            'members'       => $members,
            'roles'         => $roles,
            'currentUserId' => $userId,
        ]);
    }

    public function updateSettings(): void
    {
        $userId = (int)$this->auth->id();
        $wsService = new WorkspaceService($this->session);
        $workspace = $wsService->getActiveWorkspace($userId);

        $name = trim((string)$this->request->post('name'));
        if ($name === '') {
            $this->session->setFlash('error', 'Workspace name cannot be empty.');
            $this->redirect('/settings/workspace');
            return;
        }

        (new Workspace)->table()->where('id', (int)$workspace['id'])->update([
            'name'       => $name,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->session->setFlash('success', 'Workspace updated successfully.');
        $this->redirect('/settings/workspace');
    }

    public function inviteMember(): void
    {
        $userId = (int)$this->auth->id();
        $wsService = new WorkspaceService($this->session);
        $workspace = $wsService->getActiveWorkspace($userId);
        $wsId = (int)$workspace['id'];

        $email = strtolower(trim((string)$this->request->post('email')));
        $name = trim((string)$this->request->post('name'));
        $role = trim((string)$this->request->post('role') ?? 'member');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->setFlash('error', 'Please enter a valid email address.');
            $this->redirect('/settings/workspace');
            return;
        }

        $availableRoles = (new \App\Services\PermissionService())->getWorkspaceRoles($wsId);
        $validSlugs = array_column($availableRoles, 'slug');

        if (!in_array($role, $validSlugs, true)) {
            $role = 'member';
        }

        // Find or create user
        $user = (new User)->table()->where('email', $email)->first();
        if (!$user) {
            $userName = $name !== '' ? $name : explode('@', $email)[0];
            $tempPass = password_hash('welcome123', PASSWORD_BCRYPT);
            $newUserId = (new User)->table()->insertGetId([
                'name'       => $userName,
                'email'      => $email,
                'password'   => $tempPass,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $targetUserId = $newUserId;
        } else {
            $targetUserId = (int)$user['id'];
        }

        // Check if already in workspace
        $existing = (new Membership)->table()
            ->where('workspace_id', $wsId)
            ->where('user_id', $targetUserId)
            ->first();

        if ($existing) {
            $this->session->setFlash('error', "User '{$email}' is already a member of this workspace.");
            $this->redirect('/settings/workspace');
            return;
        }

        (new Membership)->table()->insert([
            'workspace_id' => $wsId,
            'user_id'      => $targetUserId,
            'role'         => $role,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        try {
            (new Activity)->table()->insert([
                'workspace_id' => $wsId,
                'user_id'      => $userId,
                'action'       => 'invited_member',
                'entity_type'  => 'users',
                'entity_id'    => $targetUserId,
                'description'  => "Added {$email} as {$role} to workspace",
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {}

        $this->session->setFlash('success', "Team member '{$email}' has been added with role '{$role}'!");
        $this->redirect('/settings/workspace');
    }

    public function removeMember(string|int $membershipId): void
    {
        $userId = (int)$this->auth->id();
        $wsService = new WorkspaceService($this->session);
        $workspace = $wsService->getActiveWorkspace($userId);
        $wsId = (int)$workspace['id'];

        $membership = (new Membership)->table()
            ->where('id', (int)$membershipId)
            ->where('workspace_id', $wsId)
            ->first();

        if (!$membership) {
            $this->session->setFlash('error', 'Member not found.');
            $this->redirect('/settings/workspace');
            return;
        }

        if ((int)$membership['user_id'] === $userId) {
            $this->session->setFlash('error', 'You cannot remove yourself from the workspace.');
            $this->redirect('/settings/workspace');
            return;
        }

        (new Membership)->table()->where('id', (int)$membershipId)->delete();

        $this->session->setFlash('success', 'Member removed from workspace.');
        $this->redirect('/settings/workspace');
    }

    public function updateMemberRole(string|int $membershipId): void
    {
        $userId = (int)$this->auth->id();
        $wsService = new WorkspaceService($this->session);
        $workspace = $wsService->getActiveWorkspace($userId);
        $wsId = (int)$workspace['id'];

        if (!user_can('settings.members') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', 'You are not authorized to update member roles.');
            $this->redirect('/settings/workspace');
            return;
        }

        $membership = (new Membership)->table()
            ->where('id', (int)$membershipId)
            ->where('workspace_id', $wsId)
            ->first();

        if (!$membership) {
            $this->session->setFlash('error', 'Member not found.');
            $this->redirect('/settings/workspace');
            return;
        }

        $newRole = trim((string)$this->request->post('role'));
        $validRoles = (new \App\Services\PermissionService())->getWorkspaceRoles($wsId);
        $validSlugs = array_column($validRoles, 'slug');

        if (!in_array($newRole, $validSlugs, true)) {
            $this->session->setFlash('error', 'Invalid role selected.');
            $this->redirect('/settings/workspace');
            return;
        }

        (new Membership)->table()->where('id', (int)$membershipId)->update([
            'role'       => $newRole,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        \App\Services\PermissionService::clearCache();

        $this->session->setFlash('success', 'Member role updated successfully.');
        $this->redirect('/settings/workspace');
    }
}

