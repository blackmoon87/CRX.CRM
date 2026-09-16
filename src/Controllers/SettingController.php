<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\PersonalAccessToken;
use App\Services\WorkspaceService;
use Spartan\Controller;

class SettingController extends Controller
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

    public function apiTokens(): string
    {
        $ctx = $this->getContext();
        $tokens = (new PersonalAccessToken)->table()
            ->where('workspace_id', $ctx['workspaceId'])
            ->orderBy('id', 'DESC')
            ->get();

        return $this->render('settings/api_tokens', [
            'title'      => 'API & MCP Agent Keys',
            'tokens'     => $tokens,
            'workspace'  => $ctx['workspace'],
            'workspaces' => $ctx['workspaces'],
        ]);
    }

    public function generateToken(): void
    {
        $ctx = $this->getContext();
        $name = trim((string)$this->request->post('name'));
        if ($name === '') {
            $name = 'Agent Key ' . date('Y-m-d');
        }

        $tokenStr = 'crx_' . bin2hex(random_bytes(24));

        (new PersonalAccessToken)->table()->insert([
            'workspace_id' => $ctx['workspaceId'],
            'user_id'      => $ctx['userId'],
            'name'         => $name,
            'token'        => $tokenStr,
            'abilities'    => json_encode(['*']),
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->session->setFlash('success', "New API & MCP Token generated: {$tokenStr}");
        $this->redirect('/settings/api-tokens');
    }

    public function revokeToken(string|int|null $id = null): void
    {
        $ctx = $this->getContext();
        $id = (int)($id ?? $this->request->getParam('id'));

        (new PersonalAccessToken)->table()
            ->where('id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->delete();

        $this->session->setFlash('success', 'Token revoked.');
        $this->redirect('/settings/api-tokens');
    }
}
