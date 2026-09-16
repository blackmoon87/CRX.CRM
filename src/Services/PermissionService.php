<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Membership;
use App\Models\Permission;
use App\Models\Role;
use Spartan\Application;
use Spartan\QueryBuilder;

class PermissionService
{
    private static array $cachedPermissions = [];

    /**
     * Check if a user has a specific permission within a workspace.
     */
    public function can(int $userId, int $workspaceId, string $permission): bool
    {
        $permissions = $this->getUserPermissions($userId, $workspaceId);
        if (in_array('*', $permissions, true)) {
            return true;
        }
        return in_array($permission, $permissions, true);
    }

    /**
     * Get all permission slugs granted to a user in a specific workspace.
     */
    public function getUserPermissions(int $userId, int $workspaceId): array
    {
        $cacheKey = "{$userId}:{$workspaceId}";
        if (isset(self::$cachedPermissions[$cacheKey])) {
            return self::$cachedPermissions[$cacheKey];
        }

        $membership = (new Membership)->table()
            ->where('user_id', $userId)
            ->where('workspace_id', $workspaceId)
            ->first();

        if (!$membership) {
            return self::$cachedPermissions[$cacheKey] = [];
        }

        $roleSlug = (string)$membership['role'];

        // Owner has absolute root access to everything
        if ($roleSlug === 'owner') {
            return self::$cachedPermissions[$cacheKey] = ['*'];
        }

        // Find role definition (workspace custom role or system preset)
        $db = Application::$app->db;
        $role = (new QueryBuilder($db, 'roles'))
            ->where('slug', $roleSlug)
            ->where(function (QueryBuilder $q) use ($workspaceId) {
                $q->where('workspace_id', $workspaceId)
                  ->orWhereNull('workspace_id');
            })
            ->first();

        if (!$role) {
            return self::$cachedPermissions[$cacheKey] = [];
        }

        $permissions = (new QueryBuilder($db, 'permissions'))
            ->join('role_permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('role_permissions.role_id', (int)$role['id'])
            ->select('permissions.slug')
            ->get();

        $slugs = array_column($permissions, 'slug');
        return self::$cachedPermissions[$cacheKey] = $slugs;
    }

    /**
     * Clear the in-memory permissions cache.
     */
    public static function clearCache(): void
    {
        self::$cachedPermissions = [];
    }

    /**
     * Get all available permissions grouped by module.
     */
    public function getAllPermissionsGrouped(): array
    {
        $db = Application::$app->db;
        $rows = (new QueryBuilder($db, 'permissions'))
            ->orderBy('id', 'ASC')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $mod = $row['module'] ?? 'general';
            $grouped[$mod][] = $row;
        }

        return $grouped;
    }

    /**
     * Get all roles available for a workspace (System presets + Workspace custom roles),
     * along with member counts and assigned permission slugs.
     */
    public function getWorkspaceRoles(int $workspaceId): array
    {
        $db = Application::$app->db;
        $roles = (new QueryBuilder($db, 'roles'))
            ->where(function (QueryBuilder $q) use ($workspaceId) {
                $q->where('workspace_id', $workspaceId)
                  ->orWhereNull('workspace_id');
            })
            ->orderBy('is_system', 'DESC')
            ->orderBy('id', 'ASC')
            ->get();

        $result = [];
        foreach ($roles as $role) {
            $roleId = (int)$role['id'];
            $roleSlug = (string)$role['slug'];

            // Get count of members with this role in this workspace
            $memberCount = (new QueryBuilder($db, 'memberships'))
                ->where('workspace_id', $workspaceId)
                ->where('role', $roleSlug)
                ->count();

            // Get permission slugs for this role
            if ($roleSlug === 'owner') {
                $perms = ['*'];
            } else {
                $pRows = (new QueryBuilder($db, 'permissions'))
                    ->join('role_permissions', 'role_permissions.permission_id', '=', 'permissions.id')
                    ->where('role_permissions.role_id', $roleId)
                    ->select('permissions.slug')
                    ->get();
                $perms = array_column($pRows, 'slug');
            }

            $role['member_count'] = $memberCount;
            $role['permissions'] = $perms;
            $result[] = $role;
        }

        return $result;
    }

    /**
     * Create a new custom role for a workspace with assigned permissions.
     */
    public function createCustomRole(int $workspaceId, string $name, ?string $description, array $permissionSlugs): int
    {
        $db = Application::$app->db;
        $cleanSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9_]+/', '_', $name), '_'));
        if ($cleanSlug === '') {
            $cleanSlug = 'custom_role_' . time();
        }

        // Ensure slug is unique within this workspace
        $existing = (new QueryBuilder($db, 'roles'))
            ->where('slug', $cleanSlug)
            ->where(function (QueryBuilder $q) use ($workspaceId) {
                $q->where('workspace_id', $workspaceId)
                  ->orWhereNull('workspace_id');
            })
            ->first();

        if ($existing) {
            $cleanSlug .= '_' . substr(uniqid(), -4);
        }

        $roleId = (new QueryBuilder($db, 'roles'))->insertGetId([
            'name'         => trim($name),
            'slug'         => $cleanSlug,
            'description'  => $description ? trim($description) : null,
            'workspace_id' => $workspaceId,
            'is_system'    => 0,
        ]);

        $this->syncRolePermissions($roleId, $permissionSlugs);
        self::clearCache();

        return $roleId;
    }

    /**
     * Update an existing custom role and its permissions.
     */
    public function updateCustomRole(int $roleId, int $workspaceId, string $name, ?string $description, array $permissionSlugs): bool
    {
        $db = Application::$app->db;
        $role = (new QueryBuilder($db, 'roles'))
            ->where('id', $roleId)
            ->where('workspace_id', $workspaceId)
            ->where('is_system', 0)
            ->first();

        if (!$role) {
            return false;
        }

        (new QueryBuilder($db, 'roles'))
            ->where('id', $roleId)
            ->update([
                'name'        => trim($name),
                'description' => $description ? trim($description) : null,
            ]);

        $this->syncRolePermissions($roleId, $permissionSlugs);
        self::clearCache();

        return true;
    }

    /**
     * Delete a custom role from the workspace.
     */
    public function deleteCustomRole(int $roleId, int $workspaceId): array
    {
        $db = Application::$app->db;
        $role = (new QueryBuilder($db, 'roles'))
            ->where('id', $roleId)
            ->where('workspace_id', $workspaceId)
            ->where('is_system', 0)
            ->first();

        if (!$role) {
            return ['success' => false, 'message' => 'Role not found or is a protected system preset.'];
        }

        // Check if any members currently have this role
        $memberCount = (new QueryBuilder($db, 'memberships'))
            ->where('workspace_id', $workspaceId)
            ->where('role', $role['slug'])
            ->count();

        if ($memberCount > 0) {
            return [
                'success' => false,
                'message' => "Cannot delete role '{$role['name']}' because {$memberCount} team member(s) are currently assigned to it. Please reassign them first.",
            ];
        }

        // Delete mapped permissions
        (new QueryBuilder($db, 'role_permissions'))
            ->where('role_id', $roleId)
            ->delete();

        // Delete the role
        (new QueryBuilder($db, 'roles'))
            ->where('id', $roleId)
            ->delete();

        self::clearCache();

        return ['success' => true, 'message' => 'Role deleted successfully.'];
    }

    /**
     * Synchronize permissions for a role.
     */
    private function syncRolePermissions(int $roleId, array $permissionSlugs): void
    {
        $db = Application::$app->db;

        // Clear existing permissions
        (new QueryBuilder($db, 'role_permissions'))
            ->where('role_id', $roleId)
            ->delete();

        if (empty($permissionSlugs)) {
            return;
        }

        // Look up IDs for the given slugs
        $permRows = (new QueryBuilder($db, 'permissions'))
            ->whereIn('slug', $permissionSlugs)
            ->select('id')
            ->get();

        foreach ($permRows as $p) {
            (new QueryBuilder($db, 'role_permissions'))->insert([
                'role_id'       => $roleId,
                'permission_id' => (int)$p['id'],
            ]);
        }
    }
}
