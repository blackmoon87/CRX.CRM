<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\Person;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkflowRule;

class AutomationService
{
    /**
     * Dispatch an event to the automation rules engine.
     */
    public function dispatch(int $workspaceId, string $event, array $payload): void
    {
        $rules = (new WorkflowRule)->table()
            ->where('workspace_id', $workspaceId)
            ->where('trigger_event', $event)
            ->where('is_active', 1)
            ->get();

        foreach ($rules as $rule) {
            $this->executeRule($rule, $payload, $workspaceId);
        }
    }

    private function executeRule(array $rule, array $payload, int $workspaceId): void
    {
        $actionType = $rule['action_type'];
        $actionPayload = json_decode((string)$rule['action_payload'], true) ?? [];
        $entityType = $payload['entity_type'] ?? '';
        $entityId = (int)($payload['entity_id'] ?? 0);

        switch ($actionType) {
            case 'create_task':
                $title = $actionPayload['title'] ?? 'Automated Workflow Task';
                if (isset($payload['deal_name'])) {
                    $title .= ': ' . $payload['deal_name'];
                } elseif (isset($payload['company_name'])) {
                    $title .= ': ' . $payload['company_name'];
                }

                $dueDays = (int)($actionPayload['due_days'] ?? 0);
                $dueDate = $dueDays > 0 ? date('Y-m-d', strtotime("+{$dueDays} days")) : date('Y-m-d');

                (new Task)->table()->insert([
                    'workspace_id'     => $workspaceId,
                    'title'            => $title,
                    'description'      => "Created automatically by rule: {$rule['name']}",
                    'priority'         => $actionPayload['priority'] ?? 'medium',
                    'status'           => 'pending',
                    'due_date'         => $dueDate,
                    'entity_type'      => $entityType !== '' ? $entityType : null,
                    'entity_id'        => $entityId > 0 ? $entityId : null,
                    'assigned_user_id' => $actionPayload['assigned_user_id'] ?? ($payload['user_id'] ?? 1),
                    'created_at'       => date('Y-m-d H:i:s'),
                    'updated_at'       => date('Y-m-d H:i:s'),
                ]);
                break;

            case 'tag_entity':
                $tagId = (int)($actionPayload['tag_id'] ?? 0);
                if ($tagId > 0 && $entityType !== '' && $entityId > 0) {
                    (new TagService())->attachTag($tagId, $entityType, $entityId);
                }
                break;

            case 'update_stage':
                $targetStage = trim((string)($actionPayload['target_stage'] ?? ''));
                if ($targetStage !== '' && $entityId > 0) {
                    if ($entityType === 'opportunities') {
                        $status = ($targetStage === 'closed_won' || $targetStage === 'closed_lost') ? 'closed' : 'open';
                        (new Opportunity)->table()->where('id', $entityId)->where('workspace_id', $workspaceId)->update([
                            'stage'      => $targetStage,
                            'status'     => $status,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    } elseif ($entityType === 'people') {
                        (new Person)->table()->where('id', $entityId)->where('workspace_id', $workspaceId)->update([
                            'status'     => $targetStage,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
                break;

            case 'reassign_owner':
                $newUserId = (int)($actionPayload['assigned_user_id'] ?? 0);
                if ($newUserId > 0 && $entityId > 0) {
                    $model = match($entityType) {
                        'opportunities' => new Opportunity,
                        'people'        => new Person,
                        'companies'     => new Company,
                        'tasks'         => new Task,
                        default         => null,
                    };
                    if ($model) {
                        $model->table()->where('id', $entityId)->where('workspace_id', $workspaceId)->update([
                            'assigned_user_id' => $newUserId,
                            'updated_at'       => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
                break;

            case 'log_note':
                $noteTitle = $actionPayload['note_title'] ?? "Automated Workflow Audit: {$rule['name']}";
                $noteBody  = $actionPayload['note_body'] ?? "Action executed automatically upon trigger '{$rule['trigger_event']}' on " . date('Y-m-d H:i:s');
                if ($entityType !== '' && $entityId > 0) {
                    (new Note)->table()->insert([
                        'workspace_id' => $workspaceId,
                        'entity_type'  => $entityType,
                        'entity_id'    => $entityId,
                        'user_id'      => (int)($payload['user_id'] ?? 1),
                        'title'        => $noteTitle,
                        'body'         => $noteBody,
                        'created_at'   => date('Y-m-d H:i:s'),
                        'updated_at'   => date('Y-m-d H:i:s'),
                    ]);
                }
                break;

            case 'send_webhook':
                $url = $actionPayload['url'] ?? '';
                if (!empty($url)) {
                    $this->fireWebhook($url, [
                        'rule_id'      => $rule['id'],
                        'rule_name'    => $rule['name'],
                        'event'        => $rule['trigger_event'],
                        'workspace_id' => $workspaceId,
                        'data'         => $payload,
                        'timestamp'    => date('c'),
                    ]);
                }
                break;

            case 'send_email':
                $to = $actionPayload['to'] ?? ($payload['email'] ?? '');
                if (empty($to) && !empty($payload['user_id'])) {
                    $u = (new User)->find((int)$payload['user_id']);
                    $to = $u['email'] ?? '';
                }
                if (empty($to)) {
                    $to = config('mail.from.address') ?? 'admin@crx.local';
                }

                $subject = $actionPayload['subject'] ?? "CRX Alert: {$rule['name']}";
                $body    = $actionPayload['message'] ?? "Workflow rule '{$rule['name']}' triggered on " . ($payload['name'] ?? $entityType);

                // Replace placeholders e.g. {{name}}, {{amount}}, {{stage}}
                foreach ($payload as $k => $v) {
                    if (is_scalar($v)) {
                        $subject = str_replace('{{' . $k . '}}', (string)$v, $subject);
                        $body    = str_replace('{{' . $k . '}}', (string)$v, $body);
                    }
                }

                (new MailService())->sendAlert(
                    $to,
                    $subject,
                    $rule['name'],
                    $body,
                    [
                        'Event'       => $rule['trigger_event'],
                        'Entity'      => ucfirst((string)$entityType),
                        'Entity ID'   => $entityId,
                        'Timestamp'   => date('Y-m-d H:i:s'),
                    ],
                    $entityType !== '' && $entityId > 0 ? (url('/' . $entityType) ?: "/{$entityType}") : null,
                    'View in CRX'
                );
                break;
        }
    }

    private function fireWebhook(string $url, array $data): void
    {
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'User-Agent: CRX-Workflow-Bot/1.0']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Throwable $e) {}
    }
}
