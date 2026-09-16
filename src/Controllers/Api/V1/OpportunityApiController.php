<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\Person;
use App\Models\Task;

class OpportunityApiController extends BaseApiController
{
    public function index(): void
    {
        $wsId = $this->getWorkspaceId();
        $query = (new Opportunity)->table()->where('workspace_id', $wsId);

        $search = trim((string)($this->request->get('search') ?? $this->request->get('q') ?? ''));
        if ($search !== '') {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $stage = trim((string)($this->request->get('stage') ?? ''));
        if ($stage !== '') {
            $query->where('stage', $stage);
        }

        $status = trim((string)($this->request->get('status') ?? ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        $companyId = $this->request->get('company_id');
        if ($companyId) {
            $query->where('company_id', (int)$companyId);
        }

        $personId = $this->request->get('person_id');
        if ($personId) {
            $query->where('person_id', (int)$personId);
        }

        $perPage = max(1, min(100, (int)($this->request->get('per_page') ?? 15)));
        $page = max(1, (int)($this->request->get('page') ?? 1));
        $offset = ($page - 1) * $perPage;

        $total = (clone $query)->count();
        $records = $query->orderBy('id', 'DESC')->limit($perPage)->offset($offset)->get();

        $data = [];
        foreach ($records as $rec) {
            $rec['id'] = (int)$rec['id'];
            $rec['custom_fields'] = $this->customFields()->getFormattedCustomFields($wsId, 'opportunities', $rec['id']);
            $data[] = $rec;
        }

        $this->respondJson($data, 200, [
            'total'        => $total,
            'page'         => $page,
            'per_page'     => $perPage,
            'last_page'    => (int)ceil($total / $perPage),
        ]);
    }

    public function store(): void
    {
        $wsId = $this->getWorkspaceId();
        $body = $this->request->getBody();

        $name = trim((string)($body['name'] ?? ''));
        if ($name === '') {
            $this->respondError('The name field is required.', 422, ['name' => ['The name field is required.']]);
            return;
        }

        $data = [
            'workspace_id'        => $wsId,
            'company_id'          => !empty($body['company_id']) ? (int)$body['company_id'] : null,
            'person_id'           => !empty($body['person_id']) ? (int)$body['person_id'] : null,
            'name'                => $name,
            'amount'              => !empty($body['amount']) ? (float)$body['amount'] : 0.00,
            'currency'            => $body['currency'] ?? 'USD',
            'stage'               => $body['stage'] ?? 'lead',
            'probability'         => isset($body['probability']) ? (int)$body['probability'] : 20,
            'expected_close_date' => !empty($body['expected_close_date']) ? $body['expected_close_date'] : null,
            'status'              => $body['status'] ?? 'open',
            'assigned_user_id'    => !empty($body['assigned_user_id']) ? (int)$body['assigned_user_id'] : $this->getUserId(),
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ];

        $dealId = (new Opportunity)->table()->insertGetId($data);

        // Save custom fields
        $this->customFields()->saveValues($wsId, 'opportunities', $dealId, $body);

        $this->recordActivity('created', 'opportunities', $dealId, "Created deal '{$name}' for $" . number_format($data['amount'], 2));

        $deal = (new Opportunity)->find($dealId);
        $deal['id'] = (int)$deal['id'];
        $deal['custom_fields'] = $this->customFields()->getFormattedCustomFields($wsId, 'opportunities', $dealId);

        $this->respondJson($deal, 201);
    }

    public function show(string|int $id): void
    {
        $wsId = $this->getWorkspaceId();
        $deal = (new Opportunity)->table()
            ->where('id', (int)$id)
            ->where('workspace_id', $wsId)
            ->first();

        if (!$deal) {
            $this->respondError('Opportunity not found', 404);
            return;
        }

        $dealId = (int)$deal['id'];
        $deal['id'] = $dealId;
        $deal['custom_fields'] = $this->customFields()->getFormattedCustomFields($wsId, 'opportunities', $dealId);

        if (!empty($deal['company_id'])) {
            $deal['company'] = (new Company)->table()->where('id', (int)$deal['company_id'])->first();
        }

        if (!empty($deal['person_id'])) {
            $deal['person'] = (new Person)->table()->where('id', (int)$deal['person_id'])->first();
        }

        $deal['notes'] = (new Note)->table()
            ->where('entity_type', 'opportunities')
            ->where('entity_id', $dealId)
            ->where('workspace_id', $wsId)
            ->orderBy('id', 'DESC')
            ->get();

        $deal['tasks'] = (new Task)->table()
            ->where('entity_type', 'opportunities')
            ->where('entity_id', $dealId)
            ->where('workspace_id', $wsId)
            ->get();

        $this->respondJson($deal);
    }

    public function update(string|int $id): void
    {
        $wsId = $this->getWorkspaceId();
        $dealId = (int)$id;

        $existing = (new Opportunity)->table()
            ->where('id', $dealId)
            ->where('workspace_id', $wsId)
            ->first();

        if (!$existing) {
            $this->respondError('Opportunity not found', 404);
            return;
        }

        $body = $this->request->getBody();
        $update = [];

        $fields = ['name', 'currency', 'stage', 'status'];
        foreach ($fields as $f) {
            if (array_key_exists($f, $body)) {
                $update[$f] = $body[$f];
            }
        }

        if (array_key_exists('amount', $body)) {
            $update['amount'] = (float)$body['amount'];
        }

        if (array_key_exists('probability', $body)) {
            $update['probability'] = (int)$body['probability'];
        }

        if (array_key_exists('expected_close_date', $body)) {
            $update['expected_close_date'] = !empty($body['expected_close_date']) ? $body['expected_close_date'] : null;
        }

        if (array_key_exists('company_id', $body)) {
            $update['company_id'] = !empty($body['company_id']) ? (int)$body['company_id'] : null;
        }

        if (array_key_exists('person_id', $body)) {
            $update['person_id'] = !empty($body['person_id']) ? (int)$body['person_id'] : null;
        }

        if (array_key_exists('assigned_user_id', $body)) {
            $update['assigned_user_id'] = !empty($body['assigned_user_id']) ? (int)$body['assigned_user_id'] : null;
        }

        $update['updated_at'] = date('Y-m-d H:i:s');

        (new Opportunity)->table()->where('id', $dealId)->update($update);

        // Update custom fields
        $this->customFields()->saveValues($wsId, 'opportunities', $dealId, $body);

        $this->recordActivity('updated', 'opportunities', $dealId, "Updated deal via REST API");

        $deal = (new Opportunity)->find($dealId);
        $deal['id'] = (int)$deal['id'];
        $deal['custom_fields'] = $this->customFields()->getFormattedCustomFields($wsId, 'opportunities', $dealId);

        $this->respondJson($deal);
    }

    public function destroy(string|int $id): void
    {
        $wsId = $this->getWorkspaceId();
        $dealId = (int)$id;

        $existing = (new Opportunity)->table()
            ->where('id', $dealId)
            ->where('workspace_id', $wsId)
            ->first();

        if (!$existing) {
            $this->respondError('Opportunity not found', 404);
            return;
        }

        (new Opportunity)->table()->where('id', $dealId)->delete();

        $this->recordActivity('deleted', 'opportunities', $dealId, "Deleted deal '{$existing['name']}' via REST API");

        $this->response->setStatusCode(204);
    }
}
