<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Company;
use App\Models\Interaction;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\Person;
use App\Models\Task;
use App\Services\ActivityLogger;
use App\Services\AutomationService;
use App\Services\CustomFieldService;
use App\Services\TagService;
use App\Services\WorkspaceService;
use Spartan\Controller;

class CompanyController extends Controller
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
        $search = trim((string)$this->request->get('search'));
        $industry = trim((string)$this->request->get('industry'));
        $sort = strtolower(trim((string)$this->request->get('sort') ?? 'id'));
        $dir = strtolower(trim((string)$this->request->get('dir') ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $page = max(1, (int)($this->request->get('page') ?? 1));
        $perPage = min(100, max(5, (int)($this->request->get('per_page') ?? 15)));

        $allowedSorts = ['id', 'name', 'domain', 'industry', 'annual_revenue', 'created_at'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        $query = (new Company)->table()
            ->where('workspace_id', $ctx['workspaceId'])
            ->where('deleted_at', null);

        if ($search !== '') {
            $query->where(function(\Spartan\QueryBuilder $q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('domain', 'LIKE', "%{$search}%")
                  ->orWhere('industry', 'LIKE', "%{$search}%")
                  ->orWhere('city', 'LIKE', "%{$search}%");
            });
        }

        if ($industry !== '') {
            $query->where('industry', $industry);
        }

        $allowedFilterColumns = [
            'name'           => ['label' => 'Company Name', 'type' => 'string'],
            'domain'         => ['label' => 'Domain', 'type' => 'string'],
            'industry'       => ['label' => 'Industry', 'type' => 'string'],
            'annual_revenue' => ['label' => 'Annual Revenue', 'type' => 'numeric'],
            'phone'          => ['label' => 'Phone', 'type' => 'string'],
            'email'          => ['label' => 'Email', 'type' => 'string'],
            'size'           => ['label' => 'Company Size', 'type' => 'string'],
            'address'        => ['label' => 'Street Address', 'type' => 'string'],
            'city'           => ['label' => 'City', 'type' => 'string'],
            'state'          => ['label' => 'State / Region', 'type' => 'string'],
            'postal_code'    => ['label' => 'Postal / ZIP Code', 'type' => 'string'],
            'country'        => ['label' => 'Country', 'type' => 'string'],
        ];

        $activeFilters = \App\Services\FilterService::extractFilters($_GET, $allowedFilterColumns);
        \App\Services\FilterService::apply($query, $activeFilters, 'companies.', $allowedFilterColumns);

        $paginator = $query->orderBy($sort, $dir)->paginate($perPage, $page);

        // Grouping logic for multi-parameter targeting (Tags, Country, Industry, Size, State, etc.)
        $groupBy = (string)($this->request->get('group_by') ?? '');
        $groupedCompanies = null;
        if ($groupBy === 'tags') {
            $groupedCompanies = [];
            $tagService = new \App\Services\TagService();
            foreach ($paginator['data'] as $row) {
                $tags = $tagService->getEntityTags('companies', (int)$row['id']);
                if (empty($tags)) {
                    $groupedCompanies['🏷️ Untagged'][] = $row;
                } else {
                    foreach ($tags as $t) {
                        $groupedCompanies['🏷️ ' . $t['name']][] = $row;
                    }
                }
            }
            ksort($groupedCompanies);
        } elseif ($groupBy !== '' && isset($allowedFilterColumns[$groupBy])) {
            $groupedCompanies = [];
            foreach ($paginator['data'] as $row) {
                $grpKey = trim((string)($row[$groupBy] ?? '')) ?: 'Unassigned / Global';
                $groupedCompanies[$grpKey][] = $row;
            }
            ksort($groupedCompanies);
        }

        return $this->render('companies/index', [
            'title'                => 'Companies',
            'companies'            => $paginator['data'],
            'groupedCompanies'     => $groupedCompanies,
            'groupBy'              => $groupBy,
            'pagination'           => $paginator,
            'search'               => $search,
            'allowedFilterColumns' => $allowedFilterColumns,
            'activeFilters'        => $activeFilters,
            'currentFilterCol'     => (string)($this->request->get('filter_col') ?? ''),
            'currentFilterOp'      => (string)($this->request->get('filter_op') ?? 'contains'),
            'duplicates'           => \App\Services\DuplicateDetectionService::findDuplicateCompanies($ctx['workspaceId']),
            'sort'                 => $sort,
            'dir'                  => $dir,
            'perPage'              => $perPage,
            'workspace'            => $ctx['workspace'],
            'workspaces'           => $ctx['workspaces'],
        ]);
    }

    public function create(): string
    {
        $ctx = $this->getContext();

        if (!user_can('companies.create') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', __('app.common.unauthorized_action') ?? 'You do not have permission to create companies.');
            $this->redirect('/companies');
            return '';
        }

        $cfService = new CustomFieldService();
        $customFields = $cfService->getFieldsForEntity($ctx['workspaceId'], 'companies');

        return $this->render('companies/create', [
            'title'        => 'Add Company',
            'customFields' => $customFields,
            'workspace'    => $ctx['workspace'],
            'workspaces'   => $ctx['workspaces'],
        ]);
    }

    public function store(): void
    {
        $ctx = $this->getContext();

        if (!user_can('companies.create') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', __('app.common.unauthorized_action') ?? 'You do not have permission to create companies.');
            $this->redirect('/companies');
            return;
        }

        $body = $this->request->getBody();

        $name = trim((string)($body['name'] ?? ''));
        if ($name === '') {
            $this->session->setFlash('error', 'Company name is required.');
            $this->redirect('/companies/create');
            return;
        }

        $annualRevenue = !empty($body['annual_revenue']) ? (float)$body['annual_revenue'] : null;

        $id = (new Company)->table()->insert([
            'workspace_id'     => $ctx['workspaceId'],
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
            'annual_revenue'   => $annualRevenue,
            'description'      => $body['description'] ?? null,
            'assigned_user_id' => $ctx['userId'],
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        $cfService = new CustomFieldService();
        $cfService->saveValues($ctx['workspaceId'], 'companies', (int)$id, $body);

        ActivityLogger::log(
            $ctx['workspaceId'],
            $ctx['userId'],
            'created',
            'companies',
            (int)$id,
            "Created company: {$name}"
        );

        (new AutomationService())->dispatch($ctx['workspaceId'], 'company.created', [
            'entity_type'  => 'companies',
            'entity_id'    => (int)$id,
            'user_id'      => $ctx['userId'],
            'company_name' => $name,
            'domain'       => $body['domain'] ?? '',
            'industry'     => $body['industry'] ?? '',
        ]);

        $this->session->setFlash('success', "Company '{$name}' created successfully!");
        $this->redirect("/companies/{$id}");
    }

    public function show(string|int|null $id = null): string
    {
        $ctx = $this->getContext();
        $id = (int)($id ?? $this->request->getParam('id'));

        $company = (new Company)->table()
            ->where('id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->where('deleted_at', null)
            ->first();

        if (!$company) {
            $this->session->setFlash('error', 'Company not found or in Recycle Bin.');
            $this->redirect('/companies');
            return '';
        }

        $people = (new Person)->table()
            ->where('company_id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->where('deleted_at', null)
            ->get();

        $opportunities = (new Opportunity)->table()
            ->where('company_id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->where('deleted_at', null)
            ->get();

        $tasks = (new Task)->table()
            ->where('entity_type', 'companies')
            ->where('entity_id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->where('deleted_at', null)
            ->get();

        // Unified Interactions Timeline (calls, meetings, notes, emails)
        $interactions = (new Interaction)->table()
            ->where('workspace_id', $ctx['workspaceId'])
            ->where('entity_type', 'companies')
            ->where('entity_id', $id)
            ->orderBy('id', 'DESC')
            ->get();

        // Tags
        $tagService = new TagService();
        $tags = $tagService->getEntityTags('companies', $id);
        $allTags = $tagService->getWorkspaceTags($ctx['workspaceId']);

        $cfService = new CustomFieldService();
        $customFields = $cfService->getFieldsForEntity($ctx['workspaceId'], 'companies');
        $customValues = $cfService->getValuesForEntity($ctx['workspaceId'], 'companies', $id);

        return $this->render('companies/show', [
            'title'         => $company['name'],
            'company'       => $company,
            'people'        => $people,
            'opportunities' => $opportunities,
            'tasks'         => $tasks,
            'interactions'  => $interactions,
            'tags'          => $tags,
            'allTags'       => $allTags,
            'customFields'  => $customFields,
            'customValues'  => $customValues,
            'workspace'     => $ctx['workspace'],
            'workspaces'    => $ctx['workspaces'],
        ]);
    }

    public function destroy(string|int|null $id = null): void
    {
        $ctx = $this->getContext();

        if (!user_can('companies.delete') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', __('app.common.unauthorized_action') ?? 'You do not have permission to delete companies.');
            $this->redirect('/companies');
            return;
        }

        $id = (int)($id ?? $this->request->getParam('id'));

        $company = (new Company)->table()
            ->where('id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->first();

        if ($company) {
            // Soft delete
            (new Company)->table()->where('id', $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
            ActivityLogger::log($ctx['workspaceId'], $ctx['userId'], 'deleted', 'companies', $id, "Moved company to Recycle Bin: {$company['name']}");
            $this->session->setFlash('success', 'Company moved to Recycle Bin.');
        }

        $this->redirect('/companies');
    }

    public function bulk(): void
    {
        $ctx = $this->getContext();
        $action = trim((string)$this->request->post('bulk_action'));
        $ids = (array)($this->request->post('ids') ?? []);

        if (empty($ids)) {
            $this->session->setFlash('error', 'No companies selected.');
            $this->redirect('/companies');
            return;
        }

        if ($action === 'delete' && !user_can('companies.delete') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', __('app.common.unauthorized_action') ?? 'You do not have permission to delete companies.');
            $this->redirect('/companies');
            return;
        }

        if ($action === 'export' && !user_can('companies.export') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', __('app.common.unauthorized_action') ?? 'You do not have permission to export companies.');
            $this->redirect('/companies');
            return;
        }

        switch ($action) {
            case 'delete':
                $count = \App\Services\BulkActionService::bulkDelete('companies', $ids, $ctx['workspaceId'], $ctx['userId']);
                $this->session->setFlash('success', "{$count} companies moved to Recycle Bin.");
                break;
            case 'assign':
                $targetUserId = (int)$this->request->post('assigned_user_id');
                $count = \App\Services\BulkActionService::bulkAssign('companies', $ids, $targetUserId, $ctx['workspaceId'], $ctx['userId']);
                $this->session->setFlash('success', "{$count} companies reassigned.");
                break;
            case 'export':
                $csv = \App\Services\BulkActionService::bulkExportCsv('companies', $ids, $ctx['workspaceId']);
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="companies_export_' . date('Ymd_His') . '.csv"');
                echo $csv;
                exit;
            default:
                $this->session->setFlash('error', 'Unknown bulk action.');
                break;
        }

        $this->redirect('/companies');
    }

    public function merge(): void
    {
        $ctx = $this->getContext();
        $primaryId = (int)$this->request->post('primary_id');
        $duplicateId = (int)$this->request->post('duplicate_id');

        if ($primaryId > 0 && $duplicateId > 0 && $primaryId !== $duplicateId) {
            $success = \App\Services\DuplicateDetectionService::mergeCompanies($primaryId, $duplicateId, $ctx['workspaceId'], $ctx['userId']);
            if ($success) {
                $this->session->setFlash('success', 'Companies merged successfully! All contacts, deals, and notes consolidated.');
            } else {
                $this->session->setFlash('error', 'Unable to merge companies.');
            }
        } else {
            $this->session->setFlash('error', 'Invalid company selection for merge.');
        }

        $this->redirect('/companies');
    }
}

