<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\Person;
use App\Models\Task;

class CompanyApiController extends BaseApiController
{
    public function index(): void
    {
        $wsId = $this->getWorkspaceId();
        $query = (new Company)->table()->where('workspace_id', $wsId);

        $search = trim((string)($this->request->get('search') ?? $this->request->get('q') ?? ''));
        if ($search !== '') {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $industry = trim((string)($this->request->get('industry') ?? ''));
        if ($industry !== '') {
            $query->where('industry', $industry);
        }

        $perPage = max(1, min(100, (int)($this->request->get('per_page') ?? 15)));
        $page = max(1, (int)($this->request->get('page') ?? 1));
        $offset = ($page - 1) * $perPage;

        $total = (clone $query)->count();
        $records = $query->orderBy('id', 'DESC')->limit($perPage)->offset($offset)->get();

        $data = [];
        foreach ($records as $rec) {
            $rec['id'] = (int)$rec['id'];
            $rec['custom_fields'] = $this->customFields()->getFormattedCustomFields($wsId, 'companies', $rec['id']);
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
            'workspace_id'     => $wsId,
            'name'             => $name,
            'domain'           => $body['domain'] ?? null,
            'industry'         => $body['industry'] ?? null,
            'size'             => $body['size'] ?? null,
            'phone'            => $body['phone'] ?? null,
            'email'            => $body['email'] ?? null,
            'website'          => $body['website'] ?? null,
            'address'          => $body['address'] ?? null,
            'city'             => $body['city'] ?? null,
            'state'            => $body['state'] ?? null,
            'postal_code'      => $body['postal_code'] ?? null,
            'country'          => $body['country'] ?? null,
            'annual_revenue'   => !empty($body['annual_revenue']) ? (float)$body['annual_revenue'] : null,
            'description'      => $body['description'] ?? null,
            'assigned_user_id' => !empty($body['assigned_user_id']) ? (int)$body['assigned_user_id'] : $this->getUserId(),
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ];

        $companyId = (new Company)->table()->insertGetId($data);

        // Save custom fields
        $this->customFields()->saveValues($wsId, 'companies', $companyId, $body);

        $this->recordActivity('created', 'companies', $companyId, "Created company '{$name}' via REST API");

        $company = (new Company)->find($companyId);
        $company['id'] = (int)$company['id'];
        $company['custom_fields'] = $this->customFields()->getFormattedCustomFields($wsId, 'companies', $companyId);

        $this->respondJson($company, 201);
    }

    public function show(string|int $id): void
    {
        $wsId = $this->getWorkspaceId();
        $company = (new Company)->table()
            ->where('id', (int)$id)
            ->where('workspace_id', $wsId)
            ->first();

        if (!$company) {
            $this->respondError('Company not found', 404);
            return;
        }

        $companyId = (int)$company['id'];
        $company['id'] = $companyId;
        $company['custom_fields'] = $this->customFields()->getFormattedCustomFields($wsId, 'companies', $companyId);

        // Include associated contacts
        $company['people'] = (new Person)->table()
            ->where('company_id', $companyId)
            ->where('workspace_id', $wsId)
            ->get();

        // Include associated opportunities
        $company['opportunities'] = (new Opportunity)->table()
            ->where('company_id', $companyId)
            ->where('workspace_id', $wsId)
            ->get();

        $company['notes'] = (new Note)->table()
            ->where('entity_type', 'companies')
            ->where('entity_id', $companyId)
            ->where('workspace_id', $wsId)
            ->orderBy('id', 'DESC')
            ->get();

        $company['tasks'] = (new Task)->table()
            ->where('entity_type', 'companies')
            ->where('entity_id', $companyId)
            ->where('workspace_id', $wsId)
            ->get();

        $this->respondJson($company);
    }

    public function update(string|int $id): void
    {
        $wsId = $this->getWorkspaceId();
        $companyId = (int)$id;

        $existing = (new Company)->table()
            ->where('id', $companyId)
            ->where('workspace_id', $wsId)
            ->first();

        if (!$existing) {
            $this->respondError('Company not found', 404);
            return;
        }

        $body = $this->request->getBody();
        $update = [];

        $fields = ['name', 'domain', 'industry', 'size', 'phone', 'email', 'website', 'address', 'city', 'state', 'postal_code', 'country', 'description'];
        foreach ($fields as $f) {
            if (array_key_exists($f, $body)) {
                $update[$f] = $body[$f];
            }
        }

        if (array_key_exists('annual_revenue', $body)) {
            $update['annual_revenue'] = !empty($body['annual_revenue']) ? (float)$body['annual_revenue'] : null;
        }

        if (array_key_exists('assigned_user_id', $body)) {
            $update['assigned_user_id'] = !empty($body['assigned_user_id']) ? (int)$body['assigned_user_id'] : null;
        }

        $update['updated_at'] = date('Y-m-d H:i:s');

        (new Company)->table()->where('id', $companyId)->update($update);

        // Update custom fields
        $this->customFields()->saveValues($wsId, 'companies', $companyId, $body);

        $this->recordActivity('updated', 'companies', $companyId, "Updated company via REST API");

        $company = (new Company)->find($companyId);
        $company['id'] = (int)$company['id'];
        $company['custom_fields'] = $this->customFields()->getFormattedCustomFields($wsId, 'companies', $companyId);

        $this->respondJson($company);
    }

    public function destroy(string|int $id): void
    {
        $wsId = $this->getWorkspaceId();
        $companyId = (int)$id;

        $existing = (new Company)->table()
            ->where('id', $companyId)
            ->where('workspace_id', $wsId)
            ->first();

        if (!$existing) {
            $this->respondError('Company not found', 404);
            return;
        }

        (new Company)->table()->where('id', $companyId)->delete();

        $this->recordActivity('deleted', 'companies', $companyId, "Deleted company '{$existing['name']}' via REST API");

        $this->response->setStatusCode(204);
    }
}
