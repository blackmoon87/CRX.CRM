<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Person;
use App\Models\Task;
use Spartan\Database;

class BulkActionService
{
    /**
     * Map entity name to corresponding Model class and table name.
     */
    private static function getModel(string $entity): ?object
    {
        return match (strtolower(trim($entity))) {
            'companies', 'company' => new Company(),
            'people', 'person', 'contacts' => new Person(),
            'tasks', 'task' => new Task(),
            'opportunities', 'opportunity', 'deals' => new Opportunity(),
            default => null,
        };
    }

    /**
     * Bulk Soft-Delete records to the Workspace Recycle Bin.
     */
    public static function bulkDelete(string $entity, array $ids, int $workspaceId, int $userId): int
    {
        $ids = array_filter(array_map('intval', $ids), fn($id) => $id > 0);
        if (empty($ids)) {
            return 0;
        }

        $model = self::getModel($entity);
        if (!$model) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $affected = 0;

        // Perform soft delete within workspace boundaries
        foreach ($ids as $id) {
            $count = $model->table()
                ->where('id', $id)
                ->where('workspace_id', $workspaceId)
                ->where('deleted_at', null)
                ->update(['deleted_at' => $now]);

            if ($count > 0) {
                $affected++;
            }
        }

        if ($affected > 0) {
            ActivityLogger::log(
                $workspaceId,
                $userId,
                'bulk_delete',
                strtolower($entity),
                0,
                "Bulk moved {$affected} {$entity} records to Recycle Bin."
            );
        }

        return $affected;
    }

    /**
     * Bulk Reassign owner of records to a target team member.
     */
    public static function bulkAssign(string $entity, array $ids, int $targetUserId, int $workspaceId, int $actorUserId): int
    {
        $ids = array_filter(array_map('intval', $ids), fn($id) => $id > 0);
        if (empty($ids) || $targetUserId <= 0) {
            return 0;
        }

        $model = self::getModel($entity);
        if (!$model) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $affected = 0;

        foreach ($ids as $id) {
            $count = $model->table()
                ->where('id', $id)
                ->where('workspace_id', $workspaceId)
                ->where('deleted_at', null)
                ->update([
                    'assigned_user_id' => $targetUserId,
                    'updated_at'       => $now,
                ]);

            if ($count > 0) {
                $affected++;
            }
        }

        if ($affected > 0) {
            ActivityLogger::log(
                $workspaceId,
                $actorUserId,
                'bulk_reassign',
                strtolower($entity),
                0,
                "Bulk reassigned {$affected} {$entity} records to user #{$targetUserId}."
            );
        }

        return $affected;
    }

    /**
     * Bulk Update status / stage on selected records.
     */
    public static function bulkUpdateStatus(string $entity, array $ids, string $status, int $workspaceId, int $actorUserId): int
    {
        $ids = array_filter(array_map('intval', $ids), fn($id) => $id > 0);
        $status = trim($status);
        if (empty($ids) || $status === '') {
            return 0;
        }

        $model = self::getModel($entity);
        if (!$model) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $affected = 0;
        $column = (strtolower($entity) === 'opportunities' || strtolower($entity) === 'deals') ? 'stage' : 'status';

        foreach ($ids as $id) {
            $count = $model->table()
                ->where('id', $id)
                ->where('workspace_id', $workspaceId)
                ->where('deleted_at', null)
                ->update([
                    $column      => $status,
                    'updated_at' => $now,
                ]);

            if ($count > 0) {
                $affected++;
            }
        }

        if ($affected > 0) {
            ActivityLogger::log(
                $workspaceId,
                $actorUserId,
                'bulk_status_change',
                strtolower($entity),
                0,
                "Bulk updated {$affected} {$entity} records to {$column} '{$status}'."
            );
        }

        return $affected;
    }

    /**
     * Generate CSV export for only selected records.
     */
    public static function bulkExportCsv(string $entity, array $ids, int $workspaceId): string
    {
        $ids = array_filter(array_map('intval', $ids), fn($id) => $id > 0);
        if (empty($ids)) {
            return '';
        }

        $model = self::getModel($entity);
        if (!$model) {
            return '';
        }

        $records = $model->table()
            ->where('workspace_id', $workspaceId)
            ->where('deleted_at', null)
            ->whereIn('id', $ids)
            ->orderBy('id', 'ASC')
            ->get();

        if (empty($records)) {
            return '';
        }

        $fh = fopen('php://memory', 'r+');
        // Header row
        fputcsv($fh, array_keys($records[0]));
        foreach ($records as $row) {
            fputcsv($fh, $row);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return $csv ?: '';
    }

    /**
     * Bulk Dispatch Personalized Tracked Emails to multiple contacts.
     */
    public static function bulkEmail(array $personIds, string $subject, string $message, int $workspaceId, int $userId): array
    {
        $personIds = array_filter(array_map('intval', $personIds), fn($id) => $id > 0);
        if (empty($personIds) || empty($subject) || empty($message)) {
            return ['sent' => 0, 'failed' => 0];
        }

        $people = (new Person)->table()
            ->where('workspace_id', $workspaceId)
            ->whereNull('deleted_at')
            ->get();

        $peopleMap = [];
        foreach ($people as $p) {
            $peopleMap[(int)$p['id']] = $p;
        }

        $aiManager = new AiManagerService();
        $sent = 0;
        $failed = 0;

        foreach ($personIds as $pid) {
            $person = $peopleMap[$pid] ?? null;
            if (!$person || empty($person['email'])) {
                $failed++;
                continue;
            }

            // Variable substitution
            $personalizedBody = str_replace(
                ['{first_name}', '{last_name}', '{name}', '{email}'],
                [$person['first_name'], $person['last_name'] ?? '', trim($person['first_name'] . ' ' . ($person['last_name'] ?? '')), $person['email']],
                $message
            );

            $res = $aiManager->sendTrackedEmail($workspaceId, $userId, [
                'to'          => $person['email'],
                'subject'     => $subject,
                'html_body'   => nl2br($personalizedBody),
                'person_id'   => $pid,
                'entity_type' => 'people',
                'entity_id'   => $pid,
            ]);

            if ($res['success'] ?? false) {
                $sent++;
            } else {
                $failed++;
            }
        }

        if ($sent > 0) {
            ActivityLogger::log(
                $workspaceId,
                $userId,
                'bulk_email',
                'people',
                0,
                "Dispatched bulk email '{$subject}' to {$sent} contacts."
            );
        }

        return ['sent' => $sent, 'failed' => $failed];
    }
}
