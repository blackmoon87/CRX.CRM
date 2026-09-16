<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\Person;
use App\Models\Task;

class PeopleApiController extends BaseApiController
{
    public function index(): void
    {
        $wsId = $this->getWorkspaceId();
        $query = (new Person)->table()->where('workspace_id', $wsId);

        $search = trim((string)($this->request->get('search') ?? $this->request->get('q') ?? ''));
        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $companyId = $this->request->get('company_id');
        if ($companyId) {
            $query->where('company_id', (int)$companyId);
        }

        $status = trim((string)($this->request->get('status') ?? ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        $perPage = max(1, min(100, (int)($this->request->get('per_page') ?? 15)));
        $page = max(1, (int)($this->request->get('page') ?? 1));
        $offset = ($page - 1) * $perPage;

        $total = (clone $query)->count();
        $records = $query->orderBy('id', 'DESC')->limit($perPage)->offset($offset)->get();

        $data = [];
        foreach ($records as $rec) {
            $rec['id'] = (int)$rec['id'];
            $rec['custom_fields'] = $this->customFields()->getFormattedCustomFields($wsId, 'people', $rec['id']);
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

        $firstName = trim((string)($body['first_name'] ?? ''));
        if ($firstName === '') {
            $this->respondError('The first_name field is required.', 422, ['first_name' => ['The first_name field is required.']]);
            return;
        }

        $data = [
            'workspace_id'     => $wsId,
            'company_id'       => !empty($body['company_id']) ? (int)$body['company_id'] : null,
            'first_name'       => $firstName,
            'last_name'        => $body['last_name'] ?? null,
            'email'            => $body['email'] ?? null,
            'phone'            => $body['phone'] ?? null,
            'job_title'        => $body['job_title'] ?? null,
            'address'          => $body['address'] ?? null,
            'city'             => $body['city'] ?? null,
            'state'            => $body['state'] ?? null,
            'postal_code'      => $body['postal_code'] ?? null,
            'country'          => $body['country'] ?? null,
            'status'           => $body['status'] ?? 'lead',
            'assigned_user_id' => !empty($body['assigned_user_id']) ? (int)$body['assigned_user_id'] : $this->getUserId(),
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ];

        $personId = (new Person)->table()->insertGetId($data);

        // Save custom fields
        $this->customFields()->saveValues($wsId, 'people', $personId, $body);

        $this->recordActivity('created', 'people', $personId, "Created contact '{$firstName}' via REST API");

        $person = (new Person)->find($personId);
        $person['id'] = (int)$person['id'];
        $person['custom_fields'] = $this->customFields()->getFormattedCustomFields($wsId, 'people', $personId);

        $this->respondJson($person, 201);
    }

    public function show(string|int $id): void
    {
        $wsId = $this->getWorkspaceId();
        $person = (new Person)->table()
            ->where('id', (int)$id)
            ->where('workspace_id', $wsId)
            ->first();

        if (!$person) {
            $this->respondError('Person not found', 404);
            return;
        }

        $personId = (int)$person['id'];
        $person['id'] = $personId;
        $person['custom_fields'] = $this->customFields()->getFormattedCustomFields($wsId, 'people', $personId);

        // Include company
        if (!empty($person['company_id'])) {
            $person['company'] = (new Company)->table()
                ->where('id', (int)$person['company_id'])
                ->first();
        }

        // Include notes
        $person['notes'] = (new Note)->table()
            ->where('entity_type', 'people')
            ->where('entity_id', $personId)
            ->where('workspace_id', $wsId)
            ->orderBy('id', 'DESC')
            ->get();

        // Include tasks
        $person['tasks'] = (new Task)->table()
            ->where('entity_type', 'people')
            ->where('entity_id', $personId)
            ->where('workspace_id', $wsId)
            ->get();

        $this->respondJson($person);
    }

    public function update(string|int $id): void
    {
        $wsId = $this->getWorkspaceId();
        $personId = (int)$id;

        $existing = (new Person)->table()
            ->where('id', $personId)
            ->where('workspace_id', $wsId)
            ->first();

        if (!$existing) {
            $this->respondError('Person not found', 404);
            return;
        }

        $body = $this->request->getBody();
        $update = [];

        $fields = ['first_name', 'last_name', 'email', 'phone', 'job_title', 'address', 'city', 'state', 'postal_code', 'country', 'status'];
        foreach ($fields as $f) {
            if (array_key_exists($f, $body)) {
                $update[$f] = $body[$f];
            }
        }

        if (array_key_exists('company_id', $body)) {
            $update['company_id'] = !empty($body['company_id']) ? (int)$body['company_id'] : null;
        }

        if (array_key_exists('assigned_user_id', $body)) {
            $update['assigned_user_id'] = !empty($body['assigned_user_id']) ? (int)$body['assigned_user_id'] : null;
        }

        $update['updated_at'] = date('Y-m-d H:i:s');

        (new Person)->table()->where('id', $personId)->update($update);

        // Update custom fields
        $this->customFields()->saveValues($wsId, 'people', $personId, $body);

        $this->recordActivity('updated', 'people', $personId, "Updated contact via REST API");

        $person = (new Person)->find($personId);
        $person['id'] = (int)$person['id'];
        $person['custom_fields'] = $this->customFields()->getFormattedCustomFields($wsId, 'people', $personId);

        $this->respondJson($person);
    }

    public function destroy(string|int $id): void
    {
        $wsId = $this->getWorkspaceId();
        $personId = (int)$id;

        $existing = (new Person)->table()
            ->where('id', $personId)
            ->where('workspace_id', $wsId)
            ->first();

        if (!$existing) {
            $this->respondError('Person not found', 404);
            return;
        }

        (new Person)->table()->where('id', $personId)->delete();

        $this->recordActivity('deleted', 'people', $personId, "Deleted contact '{$existing['first_name']}' via REST API");

        $this->response->setStatusCode(204);
    }
}
