<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogger
{
    public static function log(
        int $workspaceId,
        ?int $userId,
        string $action,
        string $entityType,
        int $entityId,
        string $description,
        array $metadata = []
    ): void {
        (new ActivityLog)->table()->insert([
            'workspace_id' => $workspaceId,
            'user_id'      => $userId,
            'action'       => $action,
            'entity_type'  => $entityType,
            'entity_id'    => $entityId,
            'description'  => $description,
            'metadata'     => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }
}
