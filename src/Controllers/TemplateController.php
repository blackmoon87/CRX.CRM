<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\CommunicationTemplate;
use App\Services\WorkspaceService;
use Spartan\Controller;

class TemplateController extends Controller
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
        $templates = (new CommunicationTemplate)->table()
            ->where('workspace_id', $ctx['workspaceId'])
            ->orderBy('category', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();

        return $this->render('settings/templates', [
            'title'      => 'Communication Snippets & Templates',
            'templates'  => $templates,
            'workspace'  => $ctx['workspace'],
            'workspaces' => $ctx['workspaces'],
        ]);
    }

    public function store(): void
    {
        $ctx = $this->getContext();
        $body = $this->request->getBody();

        $name = trim((string)($body['name'] ?? ''));
        $subject = trim((string)($body['subject'] ?? ''));
        $content = trim((string)($body['body'] ?? ''));
        $category = trim((string)($body['category'] ?? 'sales'));

        if ($name === '' || $content === '') {
            $this->session->setFlash('error', 'Template name and body are required.');
            $this->redirect('/settings/templates');
            return;
        }

        (new CommunicationTemplate)->table()->insert([
            'workspace_id' => $ctx['workspaceId'],
            'name'         => $name,
            'subject'      => $subject,
            'body'         => $content,
            'category'     => $category,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->session->setFlash('success', "Snippet '{$name}' created!");
        $this->redirect('/settings/templates');
    }

    public function destroy(string|int|null $id = null): void
    {
        $ctx = $this->getContext();
        $id = (int)($id ?? $this->request->getParam('id'));

        (new CommunicationTemplate)->table()
            ->where('id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->delete();

        $this->session->setFlash('success', 'Snippet removed.');
        $this->redirect('/settings/templates');
    }

    /**
     * Interpolate variables in a template.
     */
    public static function interpolate(string $text, array $variables): string
    {
        foreach ($variables as $key => $val) {
            $text = str_replace('{{' . $key . '}}', (string)$val, $text);
        }
        return $text;
    }
}
