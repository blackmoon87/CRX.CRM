<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Models\Note;

class NoteApiController extends BaseApiController
{
    public function index(): void
    {
        $wsId = $this->getWorkspaceId();
        $query = (new Note)->table()->where('workspace_id', $wsId);

        $entityType = trim((string)($this->request->get('entity_type') ?? ''));
        if ($entityType !== '') {
            $query->where('entity_type', $entityType);
        }

        $entityId = $this->request->get('entity_id');
        if ($entityId) {
            $query->where('entity_id', (int)$entityId);
        }

        $perPage = max(1, min(100, (int)($this->request->get('per_page') ?? 25)));
        $page = max(1, (int)($this->request->get('page') ?? 1));
        $offset = ($page - 1) * $perPage;

        $total = (clone $query)->count();
        $records = $query->orderBy('id', 'DESC')->limit($perPage)->offset($offset)->get();

        $data = [];
        foreach ($records as $rec) {
            $rec['id'] = (int)$rec['id'];
            $data[] = $rec;
        }

        $this->respondJson($data, 200, [
            'total'        => $total,
            'page'         => $page,
            'per_page'     => $perPage,
            'last_page'    => (int)ceil($total / $perPage),
        ]);
    }

    public function store(): void
    {
        $wsId = $this->getWorkspaceId();
        $body = $this->request->getBody();

        $content = trim((string)($body['body'] ?? $body['content'] ?? ''));
        if ($content === '') {
            $this->respondError('The body field is required.', 422, ['body' => ['The body field is required.']]);
            return;
        }

        $entityType = trim((string)($body['entity_type'] ?? ''));
        $entityId = !empty($body['entity_id']) ? (int)$body['entity_id'] : 0;

        if ($entityType === '' || $entityId === 0) {
            $this->respondError('entity_type and entity_id are required.', 422);
            return;
        }

        $data = [
            'workspace_id' => $wsId,
            'entity_type'  => $entityType,
            'entity_id'    => $entityId,
            'user_id'      => $this->getUserId(),
            'title'        => $body['title'] ?? null,
            'body'         => $content,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ];

        $noteId = (new Note)->table()->insertGetId($data);

        $this->recordActivity('created', 'notes', $noteId, "Added a note on {$entityType} #{$entityId}");

        $note = (new Note)->find($noteId);
        $note['id'] = (int)$note['id'];

        $this->respondJson($note, 201);
    }

    public function show(string|int $id): void
    {
        $wsId = $this->getWorkspaceId();
        $note = (new Note)->table()
            ->where('id', (int)$id)
            ->where('workspace_id', $wsId)
            ->first();

        if (!$note) {
            $this->respondError('Note not found', 404);
            return;
        }

        $note['id'] = (int)$note['id'];
        $this->respondJson($note);
    }

    public function update(string|int $id): void
    {
        $wsId = $this->getWorkspaceId();
        $noteId = (int)$id;

        $existing = (new Note)->table()
            ->where('id', $noteId)
            ->where('workspace_id', $wsId)
            ->first();

        if (!$existing) {
            $this->respondError('Note not found', 404);
            return;
        }

        $body = $this->request->getBody();
        $update = [];

        if (array_key_exists('title', $body)) {
            $update['title'] = $body['title'];
        }

        if (array_key_exists('body', $body)) {
            $update['body'] = (string)$body['body'];
        } elseif (array_key_exists('content', $body)) {
            $update['body'] = (string)$body['content'];
        }

        $update['updated_at'] = date('Y-m-d H:i:s');

        (new Note)->table()->where('id', $noteId)->update($update);

        $this->recordActivity('updated', 'notes', $noteId, "Updated note #{$noteId}");

        $note = (new Note)->find($noteId);
        $note['id'] = (int)$note['id'];

        $this->respondJson($note);
    }

    public function destroy(string|int $id): void
    {
        $wsId = $this->getWorkspaceId();
        $noteId = (int)$id;

        $existing = (new Note)->table()
            ->where('id', $noteId)
            ->where('workspace_id', $wsId)
            ->first();

        if (!$existing) {
            $this->respondError('Note not found', 404);
            return;
        }

        (new Note)->table()->where('id', $noteId)->delete();

        $this->recordActivity('deleted', 'notes', $noteId, "Deleted note #{$noteId}");

        $this->response->setStatusCode(204);
    }
}
