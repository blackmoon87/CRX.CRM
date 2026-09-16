<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Tag;
use App\Services\TagService;
use App\Services\WorkspaceService;
use Spartan\Controller;

class TagController extends Controller
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
        $tagService = new TagService();
        $tags = $tagService->getWorkspaceTags($ctx['workspaceId']);

        return $this->render('settings/tags', [
            'title'      => 'Tags & Segmentation',
            'tags'       => $tags,
            'workspace'  => $ctx['workspace'],
            'workspaces' => $ctx['workspaces'],
        ]);
    }

    public function store(): void
    {
        $ctx = $this->getContext();
        $name = trim((string)$this->request->post('name'));
        $color = trim((string)($this->request->post('color') ?? '#7C3AED'));

        if ($name === '') {
            $this->session->setFlash('error', 'Tag name is required.');
            $this->redirect('/settings/tags');
            return;
        }

        (new Tag)->table()->insert([
            'workspace_id' => $ctx['workspaceId'],
            'name'         => ltrim($name, '#'),
            'color'        => $color,
        ]);

        $this->session->setFlash('success', "Tag '#{$name}' created!");
        $this->redirect('/settings/tags');
    }

    public function destroy(string|int|null $id = null): void
    {
        $ctx = $this->getContext();
        $id = (int)($id ?? $this->request->getParam('id'));

        (new Tag)->table()
            ->where('id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->delete();

        $this->session->setFlash('success', 'Tag deleted.');
        $this->redirect('/settings/tags');
    }

    public function attach(): void
    {
        $tagId = (int)$this->request->post('tag_id');
        $entityType = (string)$this->request->post('entity_type');
        $entityId = (int)$this->request->post('entity_id');

        if ($tagId > 0 && $entityId > 0 && $entityType !== '') {
            (new TagService())->attachTag($tagId, $entityType, $entityId);
            $this->session->setFlash('success', 'Tag attached.');
        }

        $this->redirectBack("/{$entityType}/{$entityId}");
    }

    public function detach(): void
    {
        $tagId = (int)$this->request->post('tag_id');
        $entityType = (string)$this->request->post('entity_type');
        $entityId = (int)$this->request->post('entity_id');

        if ($tagId > 0 && $entityId > 0 && $entityType !== '') {
            (new TagService())->detachTag($tagId, $entityType, $entityId);
            $this->session->setFlash('success', 'Tag removed.');
        }

        $this->redirectBack("/{$entityType}/{$entityId}");
    }
}
