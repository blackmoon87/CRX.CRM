<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;

class UserApiController extends BaseApiController
{
    public function show(): void
    {
        $userId = $this->getUserId();
        $user = (new User)->find($userId);

        if (!$user) {
            $this->respondError('User not found', 404);
            return;
        }

        $wsId = $this->getWorkspaceId();
        $workspace = (new Workspace)->find($wsId);
        $membership = (new Membership)->table()
            ->where('workspace_id', $wsId)
            ->where('user_id', $userId)
            ->first();

        $this->respondJson([
            'id'           => (int)$user['id'],
            'name'         => $user['name'],
            'email'        => $user['email'],
            'workspace'    => $workspace ? [
                'id'   => (int)$workspace['id'],
                'name' => $workspace['name'],
                'slug' => $workspace['slug'],
                'role' => $membership['role'] ?? 'owner',
            ] : null,
            'created_at'   => $user['created_at'] ?? null,
        ]);
    }
}
