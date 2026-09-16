<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tag;
use Spartan\Application;

class TagService
{
    public function getWorkspaceTags(int $workspaceId): array
    {
        return (new Tag)->table()
            ->where('workspace_id', $workspaceId)
            ->orderBy('name', 'ASC')
            ->get();
    }

    public function getEntityTags(string $entityType, int $entityId): array
    {
        $db = Application::$app->db;
        $stmt = $db->prepare("
            SELECT t.id, t.name, t.color 
            FROM tags t
            JOIN taggables tg ON tg.tag_id = t.id
            WHERE tg.taggable_type = ? AND tg.taggable_id = ?
            ORDER BY t.name ASC
        ");
        $stmt->execute([$entityType, $entityId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function attachTag(int $tagId, string $entityType, int $entityId): void
    {
        $db = Application::$app->db;
        $stmt = $db->prepare("
            INSERT OR IGNORE INTO taggables (tag_id, taggable_type, taggable_id)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$tagId, $entityType, $entityId]);
    }

    public function detachTag(int $tagId, string $entityType, int $entityId): void
    {
        $db = Application::$app->db;
        $stmt = $db->prepare("
            DELETE FROM taggables 
            WHERE tag_id = ? AND taggable_type = ? AND taggable_id = ?
        ");
        $stmt->execute([$tagId, $entityType, $entityId]);
    }
}
