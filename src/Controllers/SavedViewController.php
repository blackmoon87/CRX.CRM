<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\SavedView;
use App\Services\ActivityLogger;
use App\Services\WorkspaceService;
use Spartan\Controller;

class SavedViewController extends Controller
{
    private function getContext(): array
    {
        $userId = (int)$this->auth->id();
        $wsService = new WorkspaceService($this->session);
        return [
            'userId'      => $userId,
            'workspaceId' => $wsService->getActiveWorkspaceId($userId),
        ];
    }

    public function store(): void
    {
        $ctx = $this->getContext();
        $name = trim((string)$this->request->post('name'));
        $entityType = trim((string)$this->request->post('entity_type'));
        $icon = trim((string)($this->request->post('icon') ?: '📁'));
        $queryParams = (string)$this->request->post('query_params');
        $isShared = (int)($this->request->post('is_shared') ?? 0);

        if ($name === '' || $entityType === '') {
            $this->session->setFlash('error', 'View name and entity type are required.');
            $this->redirectBack('/' . $entityType);
            return;
        }

        $now = date('Y-m-d H:i:s');
        $id = (new SavedView)->table()->insert([
            'workspace_id' => $ctx['workspaceId'],
            'user_id'      => $ctx['userId'],
            'entity_type'  => $entityType,
            'name'         => $name,
            'icon'         => $icon,
            'query_params' => $queryParams,
            'is_shared'    => $isShared,
            'is_default'   => 0,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        ActivityLogger::log($ctx['workspaceId'], $ctx['userId'], 'created', 'saved_views', (int)$id, "Created saved view '{$name}' for {$entityType}");

        $this->session->setFlash('success', "View '{$name}' saved.");

        // Decode query params to reconstruct redirect URL
        $params = json_decode($queryParams, true) ?? [];
        $params['view_id'] = $id;
        $url = '/' . $entityType . (empty($params) ? '' : '?' . http_build_query($params));
        $this->redirect($url);
    }

    public function update(string|int|null $id = null): void
    {
        $ctx = $this->getContext();
        $id = (int)($id ?? $this->request->getParam('id'));

        $view = (new SavedView)->table()
            ->where('id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->first();

        if (!$view) {
            $this->session->setFlash('error', 'Saved view not found.');
            $this->redirectBack('/people');
            return;
        }

        $name = trim((string)$this->request->post('name') ?: $view['name']);
        $icon = trim((string)$this->request->post('icon') ?: $view['icon']);
        $queryParams = (string)($this->request->post('query_params') ?: $view['query_params']);
        $isShared = isset($_POST['is_shared']) ? (int)$this->request->post('is_shared') : (int)$view['is_shared'];

        (new SavedView)->table()->where('id', $id)->update([
            'name'         => $name,
            'icon'         => $icon,
            'query_params' => $queryParams,
            'is_shared'    => $isShared,
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->session->setFlash('success', "View '{$name}' updated.");
        $this->redirectBack('/' . $view['entity_type'] . '?view_id=' . $id);
    }

    public function destroy(string|int|null $id = null): void
    {
        $ctx = $this->getContext();
        $id = (int)($id ?? $this->request->getParam('id'));

        $view = (new SavedView)->table()
            ->where('id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->first();

        if ($view) {
            (new SavedView)->table()->where('id', $id)->delete();
            ActivityLogger::log($ctx['workspaceId'], $ctx['userId'], 'deleted', 'saved_views', $id, "Deleted saved view '{$view['name']}'");
            $this->session->setFlash('success', "View '{$view['name']}' deleted.");
            $this->redirect('/' . $view['entity_type']);
            return;
        }

        $this->redirectBack('/people');
    }

    public function setDefault(string|int|null $id = null): void
    {
        $ctx = $this->getContext();
        $id = (int)($id ?? $this->request->getParam('id'));

        $view = (new SavedView)->table()
            ->where('id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->first();

        if ($view) {
            // Unset previous defaults for this user + entity_type
            (new SavedView)->table()
                ->where('workspace_id', $ctx['workspaceId'])
                ->where('user_id', $ctx['userId'])
                ->where('entity_type', $view['entity_type'])
                ->update(['is_default' => 0]);

            (new SavedView)->table()->where('id', $id)->update(['is_default' => 1]);

            $this->session->setFlash('success', "View '{$view['name']}' set as default.");
        }

        $this->redirectBack('/' . ($view['entity_type'] ?? 'people'));
    }
}
