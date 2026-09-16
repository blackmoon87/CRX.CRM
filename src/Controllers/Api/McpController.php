<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\PersonalAccessToken;
use App\Services\McpToolRegistry;
use Spartan\Controller;

class McpController extends Controller
{
    public function handle(): void
    {
        $payload = $this->request->getBody();
        $method  = $payload['method'] ?? null;
        $id      = $payload['id'] ?? null;
        $params  = $payload['params'] ?? [];

        // Determine Workspace and User context
        $workspaceId = $this->session->get('api_workspace_id') 
            ?? $this->session->get('active_workspace_id');
        $userId = $this->session->get('api_user_id') 
            ?? ($this->auth->check() ? (int)$this->auth->id() : null);

        // Also check Bearer token if not resolved yet
        if (!$workspaceId) {
            $authHeader = $this->request->header('Authorization') ?? '';
            $token = '';
            if (str_starts_with($authHeader, 'Bearer ')) {
                $token = trim(substr($authHeader, 7));
            }
            if ($token !== '') {
                $tokenRecord = (new PersonalAccessToken)->table()->where('token', $token)->first();
                if ($tokenRecord) {
                    $workspaceId = (int)$tokenRecord['workspace_id'];
                    $userId = (int)$tokenRecord['user_id'];
                }
            }
        }

        if (!$workspaceId && $method !== 'initialize' && $method !== 'ping') {
            $this->response->json([
                'jsonrpc' => '2.0',
                'id'      => $id,
                'error'   => [
                    'code'    => -32001,
                    'message' => 'Unauthorized: Valid workspace token required.',
                ],
            ], 401);
            return;
        }

        $registry = new McpToolRegistry();

        switch ($method) {
            case 'initialize':
                $this->response->json([
                    'jsonrpc' => '2.0',
                    'id'      => $id,
                    'result'  => [
                        'protocolVersion' => '2024-11-05',
                        'serverInfo' => [
                            'name'    => 'crx-mcp-server',
                            'version' => '1.0.0',
                        ],
                        'capabilities' => [
                            'tools' => new \stdClass(),
                        ],
                    ],
                ]);
                return;

            case 'ping':
                $this->response->json([
                    'jsonrpc' => '2.0',
                    'id'      => $id,
                    'result'  => new \stdClass(),
                ]);
                return;

            case 'tools/list':
                $tools = $registry->getTools();
                $this->response->json([
                    'jsonrpc' => '2.0',
                    'id'      => $id,
                    'result'  => [
                        'tools' => $tools,
                    ],
                ]);
                return;

            case 'tools/call':
                $toolName = $params['name'] ?? '';
                $arguments = $params['arguments'] ?? [];

                try {
                    $output = $registry->execute($toolName, $arguments, (int)$workspaceId, $userId);
                    $this->response->json([
                        'jsonrpc' => '2.0',
                        'id'      => $id,
                        'result'  => [
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                ],
                            ],
                            'isError' => false,
                        ],
                    ]);
                } catch (\Throwable $e) {
                    $this->response->json([
                        'jsonrpc' => '2.0',
                        'id'      => $id,
                        'result'  => [
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => "Tool execution error: " . $e->getMessage(),
                                ],
                            ],
                            'isError' => true,
                        ],
                    ]);
                }
                return;

            default:
                $this->response->json([
                    'jsonrpc' => '2.0',
                    'id'      => $id,
                    'error'   => [
                        'code'    => -32601,
                        'message' => "Method not found: [{$method}]",
                    ],
                ], 404);
                return;
        }
    }
}
