<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\WorkflowRule;
use App\Services\WorkspaceService;
use Spartan\Controller;

class WorkflowController extends Controller
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
        $rules = (new WorkflowRule)->table()
            ->where('workspace_id', $ctx['workspaceId'])
            ->orderBy('id', 'DESC')
            ->get();

        $tags = (new \App\Models\Tag)->table()->where('workspace_id', $ctx['workspaceId'])->orderBy('name', 'ASC')->get();
        $users = (new \App\Models\User)->table()->select('id', 'name', 'email')->get();

        return $this->render('settings/workflows', [
            'title'      => 'Workflow Automations & Rules',
            'rules'      => $rules,
            'tags'       => $tags,
            'users'      => $users,
            'workspace'  => $ctx['workspace'],
            'workspaces' => $ctx['workspaces'],
        ]);
    }

    public function store(): void
    {
        $ctx = $this->getContext();
        $body = $this->request->getBody();

        $name = trim((string)($body['name'] ?? ''));
        $event = trim((string)($body['trigger_event'] ?? 'deal.won'));
        $action = trim((string)($body['action_type'] ?? 'create_task'));

        if ($name === '') {
            $this->session->setFlash('error', 'Workflow rule name is required.');
            $this->redirect('/settings/workflows');
            return;
        }

        $payload = match($action) {
            'create_task' => [
                'title'            => trim((string)($body['task_title'] ?? 'Follow-up Task')),
                'priority'         => trim((string)($body['priority'] ?? 'high')),
                'due_days'         => (int)($body['due_days'] ?? 0),
                'assigned_user_id' => !empty($body['task_assigned_user_id']) ? (int)$body['task_assigned_user_id'] : null,
            ],
            'tag_entity' => [
                'tag_id'   => (int)($body['tag_id'] ?? 0),
                'tag_name' => trim((string)($body['tag_name'] ?? '')),
            ],
            'update_stage' => [
                'target_stage' => trim((string)($body['target_stage'] ?? 'proposal')),
            ],
            'reassign_owner' => [
                'assigned_user_id' => (int)($body['reassign_user_id'] ?? 1),
            ],
            'log_note' => [
                'note_title' => trim((string)($body['note_title'] ?? 'Automated Workflow Log')),
                'note_body'  => trim((string)($body['note_body'] ?? 'Action executed automatically.')),
            ],
            'send_webhook' => [
                'url'    => trim((string)($body['webhook_url'] ?? '')),
                'secret' => trim((string)($body['webhook_secret'] ?? '')),
            ],
            'send_email' => [
                'to'      => trim((string)($body['email_to'] ?? '')),
                'subject' => trim((string)($body['email_subject'] ?? 'CRX Alert')),
                'message' => trim((string)($body['email_message'] ?? '')),
            ],
            default => [],
        };

        (new WorkflowRule)->table()->insert([
            'workspace_id'    => $ctx['workspaceId'],
            'name'            => $name,
            'trigger_event'   => $event,
            'conditions'      => json_encode(['event' => $event]),
            'action_type'     => $action,
            'action_payload'  => json_encode($payload),
            'is_active'       => 1,
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        $this->session->setFlash('success', "Workflow rule '{$name}' activated!");
        $this->redirect('/settings/workflows');
    }

    public function toggle(string|int|null $id = null): void
    {
        $ctx = $this->getContext();
        $id = (int)($id ?? $this->request->getParam('id'));

        $rule = (new WorkflowRule)->table()
            ->where('id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->first();

        if ($rule) {
            $newActive = $rule['is_active'] ? 0 : 1;
            (new WorkflowRule)->table()->where('id', $id)->update(['is_active' => $newActive]);
            $this->session->setFlash('success', 'Workflow status updated.');
        }

        $this->redirect('/settings/workflows');
    }

    public function destroy(string|int|null $id = null): void
    {
        $ctx = $this->getContext();
        $id = (int)($id ?? $this->request->getParam('id'));

        (new WorkflowRule)->table()
            ->where('id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->delete();

        $this->session->setFlash('success', 'Workflow removed.');
        $this->redirect('/settings/workflows');
    }

    public function test(string|int|null $id = null): void
    {
        $ctx = $this->getContext();
        $id = (int)($id ?? $this->request->getParam('id'));

        $rule = (new WorkflowRule)->table()
            ->where('id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->first();

        if ($rule) {
            $autoService = new \App\Services\AutomationService();
            $autoService->dispatch($ctx['workspaceId'], $rule['trigger_event'], [
                'deal_name'    => 'Acme Corp Expansion [Simulation]',
                'company_name' => 'Acme Global [Simulation]',
                'entity_type'  => 'opportunities',
                'entity_id'    => 1,
                'user_id'      => $ctx['userId'],
            ]);
            $this->session->setFlash('success', "⚡ Test simulation executed for '{$rule['name']}'! Task created successfully.");
        }

        $this->redirect('/settings/workflows');
    }
}
