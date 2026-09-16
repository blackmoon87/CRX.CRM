<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Models\Task;

class TaskApiController extends BaseApiController
{
    public function index(): void
    {
        $wsId = $this->getWorkspaceId();
        $query = (new Task)->table()->where('workspace_id', $wsId);

        $status = trim((string)($this->request->get('status') ?? ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        $priority = trim((string)($this->request->get('priority') ?? ''));
        if ($priority !== '') {
            $query->where('priority', $priority);
        }

        $entityType = trim((string)($this->request->get('entity_type') ?? ''));
        if ($entityType !== '') {
            $query->where('entity_type', $entityType);
        }

        $entityId = $this->request->get('entity_id');
        if ($entityId) {
            $query->where('entity_id', (int)$entityId);
        }

        $search = trim((string)($this->request->get('search') ?? $this->request->get('q') ?? ''));
        if ($search !== '') {
            $query->where('title', 'LIKE', "%{$search}%");
        }

        $perPage = max(1, min(100, (int)($this->request->get('per_page') ?? 15)));
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

        $title = trim((string)($body['title'] ?? ''));
        if ($title === '') {
            $this->respondError('The title field is required.', 422, ['title' => ['The title field is required.']]);
            return;
        }

        $data = [
            'workspace_id'     => $wsId,
            'title'            => $title,
            'description'      => $body['description'] ?? null,
            'due_date'         => !empty($body['due_date']) ? $body['due_date'] : null,
            'priority'         => $body['priority'] ?? 'medium',
            'status'           => $body['status'] ?? 'pending',
            'entity_type'      => $body['entity_type'] ?? null,
            'entity_id'        => !empty($body['entity_id']) ? (int)$body['entity_id'] : null,
            'assigned_user_id' => !empty($body['assigned_user_id']) ? (int)$body['assigned_user_id'] : $this->getUserId(),
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ];

        $taskId = (new Task)->table()->insertGetId($data);

        $this->recordActivity('created', 'tasks', $taskId, "Created task '{$title}' via REST API");

        $task = (new Task)->find($taskId);
        $task['id'] = (int)$task['id'];

        $this->respondJson($task, 201);
    }

    public function show(string|int $id): void
    {
        $wsId = $this->getWorkspaceId();
        $task = (new Task)->table()
            ->where('id', (int)$id)
            ->where('workspace_id', $wsId)
            ->first();

        if (!$task) {
            $this->respondError('Task not found', 404);
            return;
        }

        $task['id'] = (int)$task['id'];
        $this->respondJson($task);
    }

    public function update(string|int $id): void
    {
        $wsId = $this->getWorkspaceId();
        $taskId = (int)$id;

        $existing = (new Task)->table()
            ->where('id', $taskId)
            ->where('workspace_id', $wsId)
            ->first();

        if (!$existing) {
            $this->respondError('Task not found', 404);
            return;
        }

        $body = $this->request->getBody();
        $update = [];

        $fields = ['title', 'description', 'priority', 'status', 'entity_type'];
        foreach ($fields as $f) {
            if (array_key_exists($f, $body)) {
                $update[$f] = $body[$f];
            }
        }

        if (array_key_exists('due_date', $body)) {
            $update['due_date'] = !empty($body['due_date']) ? $body['due_date'] : null;
        }

        if (array_key_exists('entity_id', $body)) {
            $update['entity_id'] = !empty($body['entity_id']) ? (int)$body['entity_id'] : null;
        }

        if (array_key_exists('assigned_user_id', $body)) {
            $update['assigned_user_id'] = !empty($body['assigned_user_id']) ? (int)$body['assigned_user_id'] : null;
        }

        $update['updated_at'] = date('Y-m-d H:i:s');

        (new Task)->table()->where('id', $taskId)->update($update);

        $this->recordActivity('updated', 'tasks', $taskId, "Updated task '{$existing['title']}' via REST API");

        $task = (new Task)->find($taskId);
        $task['id'] = (int)$task['id'];

        $this->respondJson($task);
    }

    public function destroy(string|int $id): void
    {
        $wsId = $this->getWorkspaceId();
        $taskId = (int)$id;

        $existing = (new Task)->table()
            ->where('id', $taskId)
            ->where('workspace_id', $wsId)
            ->first();

        if (!$existing) {
            $this->respondError('Task not found', 404);
            return;
        }

        (new Task)->table()->where('id', $taskId)->delete();

        $this->recordActivity('deleted', 'tasks', $taskId, "Deleted task '{$existing['title']}' via REST API");

        $this->response->setStatusCode(204);
    }
}
