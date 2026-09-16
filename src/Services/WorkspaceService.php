<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Membership;
use App\Models\Workspace;
use Spartan\SessionInterface;

class WorkspaceService
{
    public function __construct(private SessionInterface $session)
    {
    }

    public function getActiveWorkspaceId(int $userId): ?int
    {
        $activeId = $this->session->get('active_workspace_id');
        if ($activeId) {
            // Verify user is still a member
            $membership = (new Membership)->table()
                ->where('workspace_id', (int)$activeId)
                ->where('user_id', $userId)
                ->first();
            if ($membership) {
                return (int)$activeId;
            }
        }

        // Default to user's first workspace
        $firstMembership = (new Membership)->table()
            ->where('user_id', $userId)
            ->first();

        if ($firstMembership) {
            $wsId = (int)$firstMembership['workspace_id'];
            $this->session->set('active_workspace_id', $wsId);
            return $wsId;
        }

        return null;
    }

    public function setActiveWorkspace(int $userId, int $workspaceId): bool
    {
        $membership = (new Membership)->table()
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $userId)
            ->first();

        if ($membership) {
            $this->session->set('active_workspace_id', $workspaceId);
            return true;
        }

        return false;
    }

    public function getActiveWorkspace(int $userId): ?array
    {
        $wsId = $this->getActiveWorkspaceId($userId);
        if (!$wsId) {
            return null;
        }
        return (new Workspace)->table()->find($wsId);
    }

    public function getUserWorkspaces(int $userId): array
    {
        return (new Workspace)->table()
            ->join('memberships', 'workspaces.id', 'memberships.workspace_id')
            ->where('memberships.user_id', $userId)
            ->select('workspaces.id', 'workspaces.name', 'workspaces.slug', 'memberships.role')
            ->get();
    }
}
