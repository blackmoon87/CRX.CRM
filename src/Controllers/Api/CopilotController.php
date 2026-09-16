<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Services\CopilotService;
use Spartan\Controller;

class CopilotController extends Controller
{
    public function chat(): void
    {
        $payload = $this->request->getBody();
        $prompt = trim((string)($payload['prompt'] ?? ''));
        $history = (array)($payload['history'] ?? []);

        if ($prompt === '') {
            $this->response->json(['success' => false, 'reply' => 'Prompt cannot be empty.'], 400);
            return;
        }

        $screenContext = (array)($payload['screen_context'] ?? []);

        $workspaceId = (int)($this->session->get('active_workspace_id') ?? 1);
        $userId = $this->auth->check() ? (int)$this->auth->id() : 1;

        $service = new CopilotService();
        $result = $service->chat($prompt, $workspaceId, $userId, $history, $screenContext);

        $this->response->json($result);
    }

    public function executeMcp(): void
    {
        $payload = $this->request->getBody();
        $tool = trim((string)($payload['tool'] ?? ''));
        $arguments = (array)($payload['arguments'] ?? []);

        if ($tool === '') {
            $this->response->json(['success' => false, 'error' => 'Tool name is required.'], 400);
            return;
        }

        $workspaceId = (int)($this->session->get('active_workspace_id') ?? 1);
        $userId = $this->auth->check() ? (int)$this->auth->id() : 1;

        try {
            $registry = new \App\Services\McpToolRegistry();
            $result = $registry->execute($tool, $arguments, $workspaceId, $userId);
            $this->response->json([
                'success' => true,
                'tool'    => $tool,
                'data'    => $result,
            ]);
        } catch (\Throwable $e) {
            $this->response->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
