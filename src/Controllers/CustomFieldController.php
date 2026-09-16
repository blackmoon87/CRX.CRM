<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\CustomField;
use App\Services\WorkspaceService;
use Spartan\Controller;

class CustomFieldController extends Controller
{
    private function getContext(): array
    {
        $userId = (int)$this->auth->id();
        $wsService = new WorkspaceService($this->session);
        return [
            'userId'      => $userId,
            'workspaceId' => $wsService->getActiveWorkspaceId($userId),
            'workspace'   => $wsService->getActiveWorkspace($userId),
            'workspaces'  => $wsService->getUserWorkspaces($userId),
        ];
    }

    public function index(): string
    {
        $ctx = $this->getContext();
        $fields = (new CustomField)->table()
            ->where('workspace_id', $ctx['workspaceId'])
            ->orderBy('entity_type', 'ASC')
            ->orderBy('order_column', 'ASC')
            ->get();

        return $this->render('settings/custom_fields', [
            'title'      => 'Custom Fields Manager',
            'fields'     => $fields,
            'workspace'  => $ctx['workspace'],
            'workspaces' => $ctx['workspaces'],
        ]);
    }

    public function store(): void
    {
        $ctx = $this->getContext();
        $body = $this->request->getBody();

        $name = trim((string)($body['name'] ?? ''));
        $entityType = trim((string)($body['entity_type'] ?? 'companies'));
        $type = trim((string)($body['type'] ?? 'text'));
        $code = trim((string)($body['code'] ?? ''));
        $optionsRaw = trim((string)($body['options'] ?? ''));

        if ($code === '') {
            $code = strtolower(trim(preg_replace('/[^A-Za-z0-9_]+/', '_', $name), '_'));
        }

        if ($name === '' || $code === '') {
            $this->session->setFlash('error', 'Field name and code are required.');
            $this->redirect('/settings/custom-fields');
            return;
        }

        $optionsJson = null;
        if (in_array($type, ['select', 'multi_select'], true) && $optionsRaw !== '') {
            $opts = array_values(array_filter(array_map('trim', explode(',', $optionsRaw))));
            $optionsJson = json_encode($opts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        (new CustomField)->table()->insert([
            'workspace_id' => $ctx['workspaceId'],
            'entity_type'  => $entityType,
            'name'         => $name,
            'code'         => $code,
            'type'         => $type,
            'options'      => $optionsJson,
            'is_required'  => !empty($body['is_required']) ? 1 : 0,
            'order_column' => 0,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->session->setFlash('success', "Custom field '{$name}' created!");
        $this->redirect('/settings/custom-fields');
    }

    public function destroy(string|int|null $id = null): void
    {
        $ctx = $this->getContext();
        $id = (int)($id ?? $this->request->getParam('id'));

        (new CustomField)->table()
            ->where('id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->delete();

        $this->session->setFlash('success', 'Custom field removed.');
        $this->redirect('/settings/custom-fields');
    }
}
