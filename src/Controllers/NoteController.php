<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Note;
use App\Services\ActivityLogger;
use App\Services\WorkspaceService;
use Spartan\Controller;

class NoteController extends Controller
{
    public function store(): void
    {
        $userId = (int)$this->auth->id();
        $wsService = new WorkspaceService($this->session);
        $workspaceId = $wsService->getActiveWorkspaceId($userId);

        $body = $this->request->getBody();
        $content = trim((string)($body['body'] ?? ''));
        $entityType = trim((string)($body['entity_type'] ?? ''));
        $entityId = (int)($body['entity_id'] ?? 0);

        $default = ($entityType !== '' && $entityId > 0) ? "/{$entityType}/{$entityId}" : '/dashboard';

        if ($content === '' || $entityType === '' || !$entityId) {
            $this->session->setFlash('error', 'Note content cannot be empty.');
            $this->redirectBack($default);
            return;
        }

        $id = (new Note)->table()->insert([
            'workspace_id' => $workspaceId,
            'entity_type'  => $entityType,
            'entity_id'    => $entityId,
            'user_id'      => $userId,
            'title'        => trim((string)($body['title'] ?? '')) ?: null,
            'body'         => $content,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        ActivityLogger::log(
            $workspaceId,
            $userId,
            'created',
            'notes',
            (int)$id,
            "Added a note to {$entityType} #{$entityId}"
        );

        $this->session->setFlash('success', 'Note added successfully.');
        $this->redirectBack($default);
    }

    public function destroy(string|int|null $id = null): void
    {
        $userId = (int)$this->auth->id();
        $wsService = new WorkspaceService($this->session);
        $workspaceId = $wsService->getActiveWorkspaceId($userId);

        $id = (int)($id ?? $this->request->getParam('id'));

        $note = (new Note)->table()
            ->where('id', $id)
            ->where('workspace_id', $workspaceId)
            ->first();

        $default = $note ? "/{$note['entity_type']}/{$note['entity_id']}" : '/dashboard';

        if ($note) {
            (new Note)->table()->where('id', $id)->delete();
        }

        $this->session->setFlash('success', 'Note deleted.');
        $this->redirectBack($default);
    }
}
