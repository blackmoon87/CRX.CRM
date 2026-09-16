<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Person;
use App\Models\Task;
use App\Services\ActivityLogger;
use App\Services\WorkspaceService;
use Spartan\Controller;

class InlineEditController extends Controller
{
    private const WHITELISTS = [
        'people' => ['first_name', 'last_name', 'email', 'phone', 'job_title', 'status'],
        'companies' => ['name', 'domain', 'industry', 'annual_revenue', 'phone', 'email', 'city'],
        'tasks' => ['title', 'priority', 'status', 'due_date'],
        'opportunities' => ['name', 'amount', 'stage', 'probability', 'expected_close_date'],
    ];

    public function update(): void
    {
        $userId = (int)$this->auth->id();
        $wsService = new WorkspaceService($this->session);
        $workspaceId = $wsService->getActiveWorkspaceId($userId);

        $body = $this->request->getBody();

        $entity = strtolower(trim((string)($body['entity'] ?? '')));
        $id = (int)($body['id'] ?? 0);
        $field = trim((string)($body['field'] ?? ''));
        $value = $body['value'] ?? null;


        if (!isset(self::WHITELISTS[$entity])) {
            $this->response->json(['success' => false, 'error' => "Invalid entity '{$entity}'."], 400);
            return;
        }

        if (!in_array($field, self::WHITELISTS[$entity], true)) {
            $this->response->json(['success' => false, 'error' => "Field '{$field}' is not editable inline."], 403);
            return;
        }

        $model = match ($entity) {
            'people' => new Person(),
            'companies' => new Company(),
            'tasks' => new Task(),
            'opportunities' => new Opportunity(),
            default => null,
        };

        if (!$model) {
            $this->response->json(['success' => false, 'error' => 'Model not found.'], 404);
            return;
        }

        $record = $model->table()
            ->where('id', $id)
            ->where('workspace_id', $workspaceId)
            ->where('deleted_at', null)
            ->first();

        if (!$record) {
            $this->response->json(['success' => false, 'error' => 'Record not found in this workspace.'], 404);
            return;
        }

        $oldVal = $record[$field] ?? null;

        // Clean values
        if (is_string($value)) {
            $value = trim($value);
        }
        if (in_array($field, ['amount', 'annual_revenue', 'probability'], true)) {
            $value = is_numeric($value) ? (float)$value : 0.0;
        }

        $model->table()->where('id', $id)->update([
            $field => $value,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        ActivityLogger::log(
            $workspaceId,
            $userId,
            'inline_edit',
            $entity,
            $id,
            "Updated {$field} from '{$oldVal}' to '{$value}'"
        );

        $this->response->json([
            'success'   => true,
            'entity'    => $entity,
            'id'        => $id,
            'field'     => $field,
            'value'     => $value,
            'old_value' => $oldVal,
        ]);
    }
}
