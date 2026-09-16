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

class PeopleController extends Controller
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
        $companyId = !empty($this->request->get('company_id')) ? (int)$this->request->get('company_id') : null;
        $jobTitle = trim((string)$this->request->get('job_title'));
        $sort = strtolower(trim((string)$this->request->get('sort') ?? 'id'));
        $dir = strtolower(trim((string)$this->request->get('dir') ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $page = max(1, (int)($this->request->get('page') ?? 1));
        $perPage = min(100, max(5, (int)($this->request->get('per_page') ?? 15)));

        $allowedSorts = ['id', 'first_name', 'last_name', 'email', 'job_title', 'created_at'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        $query = (new Person)->table()
            ->leftJoin('companies', 'people.company_id', '=', 'companies.id')
            ->where('people.workspace_id', $ctx['workspaceId'])
            ->where('people.deleted_at', null);

        if ($search !== '') {
            $query->where(function(\Spartan\QueryBuilder $q) use ($search) {
                $q->where('people.first_name', 'LIKE', "%{$search}%")
                  ->orWhere('people.last_name', 'LIKE', "%{$search}%")
                  ->orWhere('people.email', 'LIKE', "%{$search}%")
                  ->orWhere('people.job_title', 'LIKE', "%{$search}%")
                  ->orWhere('companies.name', 'LIKE', "%{$search}%");
            });
        }

        if ($companyId) {
            $query->where('people.company_id', $companyId);
        }

        $allowedFilterColumns = [
            'first_name'  => ['label' => 'First Name', 'type' => 'string'],
            'last_name'   => ['label' => 'Last Name', 'type' => 'string'],
            'email'       => ['label' => 'Email', 'type' => 'string'],
            'job_title'   => ['label' => 'Job Title', 'type' => 'string'],
            'phone'       => ['label' => 'Phone', 'type' => 'string'],
            'address'     => ['label' => 'Street Address', 'type' => 'string'],
            'city'        => ['label' => 'City', 'type' => 'string'],
            'state'       => ['label' => 'State / Region', 'type' => 'string'],
            'postal_code' => ['label' => 'Postal / ZIP Code', 'type' => 'string'],
            'country'     => ['label' => 'Country', 'type' => 'string'],
            'status'      => ['label' => 'Status', 'type' => 'exact'],
        ];

        $activeFilters = \App\Services\FilterService::extractFilters($_GET, $allowedFilterColumns);
        \App\Services\FilterService::apply($query, $activeFilters, 'people.', $allowedFilterColumns);

        // Available companies for filter dropdown
        $companies = (new \App\Models\Company)->table()
            ->where('workspace_id', $ctx['workspaceId'])
            ->where('deleted_at', null)
            ->select('id', 'name')
            ->orderBy('name', 'ASC')
            ->get();

        $paginator = $query->select('people.*', 'companies.name as company_name')
            ->orderBy("people.{$sort}", $dir)
            ->paginate($perPage, $page);

        // Grouping logic for multi-parameter targeting (Tags, Country, State, City, Status, Company)
        $groupBy = (string)($this->request->get('group_by') ?? '');
        $groupedPeople = null;
        if ($groupBy === 'tags') {
            $groupedPeople = [];
            $tagService = new \App\Services\TagService();
            foreach ($paginator['data'] as $row) {
                $tags = $tagService->getEntityTags('people', (int)$row['id']);
                if (empty($tags)) {
                    $groupedPeople['🏷️ Untagged'][] = $row;
                } else {
                    foreach ($tags as $t) {
                        $groupedPeople['🏷️ ' . $t['name']][] = $row;
                    }
                }
            }
            ksort($groupedPeople);
        } elseif ($groupBy !== '' && (isset($allowedFilterColumns[$groupBy]) || $groupBy === 'company_name')) {
            $groupedPeople = [];
            foreach ($paginator['data'] as $row) {
                $val = $groupBy === 'company_name' ? ($row['company_name'] ?? '') : ($row[$groupBy] ?? '');
                $grpKey = trim((string)$val) ?: 'Unassigned / Global';
                $groupedPeople[$grpKey][] = $row;
            }
            ksort($groupedPeople);
        }

        return $this->render('people/index', [
            'title'                => 'Contacts',
            'people'               => $paginator['data'],
            'groupedPeople'        => $groupedPeople,
            'groupBy'              => $groupBy,
            'pagination'           => $paginator,
            'search'               => $search,
            'companyId'            => $companyId,
            'allowedFilterColumns' => $allowedFilterColumns,
            'activeFilters'        => $activeFilters,
            'currentFilterCol'     => (string)($this->request->get('filter_col') ?? ''),
            'currentFilterOp'      => (string)($this->request->get('filter_op') ?? 'contains'),
            'currentFilterVal'     => (string)($this->request->get('filter_val') ?? ''),
            'companies'            => $companies,
            'duplicates'           => \App\Services\DuplicateDetectionService::findDuplicateContacts($ctx['workspaceId']),
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

        if (!user_can('contacts.create') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', __('app.common.unauthorized_action') ?? 'You do not have permission to create contacts.');
            $this->redirect('/people');
            return '';
        }

        $companies = (new Company)->table()->where('workspace_id', $ctx['workspaceId'])->where('deleted_at', null)->get();
        $selectedCompanyId = (int)($this->request->get('company_id') ?? 0);

        $cfService = new CustomFieldService();
        $customFields = $cfService->getFieldsForEntity($ctx['workspaceId'], 'people');

        return $this->render('people/create', [
            'title'             => 'Add Contact',
            'companies'         => $companies,
            'selectedCompanyId' => $selectedCompanyId,
            'customFields'      => $customFields,
            'workspace'         => $ctx['workspace'],
            'workspaces'        => $ctx['workspaces'],
        ]);
    }

    public function store(): void
    {
        $ctx = $this->getContext();

        if (!user_can('contacts.create') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', __('app.common.unauthorized_action') ?? 'You do not have permission to create contacts.');
            $this->redirect('/people');
            return;
        }

        $body = $this->request->getBody();

        $firstName = trim((string)($body['first_name'] ?? ''));
        if ($firstName === '') {
            $this->session->setFlash('error', 'First name is required.');
            $this->redirect('/people/create');
            return;
        }

        $companyId = !empty($body['company_id']) ? (int)$body['company_id'] : null;

        $id = (new Person)->table()->insert([
            'workspace_id'     => $ctx['workspaceId'],
            'company_id'       => $companyId,
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
            'assigned_user_id' => $ctx['userId'],
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        $cfService = new CustomFieldService();
        $cfService->saveValues($ctx['workspaceId'], 'people', (int)$id, $body);

        ActivityLogger::log(
            $ctx['workspaceId'],
            $ctx['userId'],
            'created',
            'people',
            (int)$id,
            "Created contact: {$firstName} " . ($body['last_name'] ?? '')
        );

        (new AutomationService())->dispatch($ctx['workspaceId'], 'contact.created', [
            'entity_type' => 'people',
            'entity_id'   => (int)$id,
            'user_id'     => $ctx['userId'],
            'first_name'  => $firstName,
            'last_name'   => $body['last_name'] ?? '',
            'email'       => $body['email'] ?? '',
        ]);

        $this->session->setFlash('success', "Contact created successfully!");
        $this->redirectBack("/people/{$id}");
    }

    public function show(string|int|null $id = null): string
    {
        $ctx = $this->getContext();
        $id = (int)($id ?? $this->request->getParam('id'));

        $person = (new Person)->table()
            ->leftJoin('companies', 'people.company_id', '=', 'companies.id')
            ->where('people.id', $id)
            ->where('people.workspace_id', $ctx['workspaceId'])
            ->where('people.deleted_at', null)
            ->select('people.*', 'companies.name as company_name')
            ->first();

        if (!$person) {
            $this->session->setFlash('error', 'Contact not found or in Recycle Bin.');
            $this->redirect('/people');
            return '';
        }

        $opportunities = (new Opportunity)->table()
            ->where('person_id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->where('deleted_at', null)
            ->get();

        $tasks = (new Task)->table()
            ->where('entity_type', 'people')
            ->where('entity_id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->where('deleted_at', null)
            ->get();

        // Unified Interactions Timeline
        $interactions = (new Interaction)->table()
            ->where('workspace_id', $ctx['workspaceId'])
            ->where('entity_type', 'people')
            ->where('entity_id', $id)
            ->orderBy('id', 'DESC')
            ->get();

        // Tags
        $tagService = new TagService();
        $tags = $tagService->getEntityTags('people', $id);
        $allTags = $tagService->getWorkspaceTags($ctx['workspaceId']);

        $cfService = new CustomFieldService();
        $customFields = $cfService->getFieldsForEntity($ctx['workspaceId'], 'people');
        $customValues = $cfService->getValuesForEntity($ctx['workspaceId'], 'people', $id);

        return $this->render('people/show', [
            'title'         => $person['first_name'] . ' ' . ($person['last_name'] ?? ''),
            'person'        => $person,
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

        if (!user_can('contacts.delete') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', __('app.common.unauthorized_action') ?? 'You do not have permission to delete contacts.');
            $this->redirect('/people');
            return;
        }

        $id = (int)($id ?? $this->request->getParam('id'));

        $person = (new Person)->table()
            ->where('id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->first();

        if ($person) {
            // Soft delete
            (new Person)->table()->where('id', $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
            ActivityLogger::log($ctx['workspaceId'], $ctx['userId'], 'deleted', 'people', $id, "Moved contact to Recycle Bin: {$person['first_name']}");
            $this->session->setFlash('success', 'Contact moved to Recycle Bin.');
        }

        $this->redirect('/people');
    }

    public function bulk(): void
    {
        $ctx = $this->getContext();
        $action = trim((string)$this->request->post('bulk_action'));
        $ids = (array)($this->request->post('ids') ?? []);

        if (empty($ids)) {
            $this->session->setFlash('error', 'No contacts selected.');
            $this->redirect('/people');
            return;
        }

        if ($action === 'delete' && !user_can('contacts.delete') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', __('app.common.unauthorized_action') ?? 'You do not have permission to delete contacts.');
            $this->redirect('/people');
            return;
        }

        if ($action === 'export' && !user_can('contacts.export') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', __('app.common.unauthorized_action') ?? 'You do not have permission to export contacts.');
            $this->redirect('/people');
            return;
        }

        switch ($action) {
            case 'delete':
                $count = \App\Services\BulkActionService::bulkDelete('people', $ids, $ctx['workspaceId'], $ctx['userId']);
                $this->session->setFlash('success', "{$count} contacts moved to Recycle Bin.");
                break;
            case 'assign':
                $targetUserId = (int)$this->request->post('assigned_user_id');
                $count = \App\Services\BulkActionService::bulkAssign('people', $ids, $targetUserId, $ctx['workspaceId'], $ctx['userId']);
                $this->session->setFlash('success', "{$count} contacts reassigned.");
                break;
            case 'status':
                $status = trim((string)$this->request->post('status'));
                $count = \App\Services\BulkActionService::bulkUpdateStatus('people', $ids, $status, $ctx['workspaceId'], $ctx['userId']);
                $this->session->setFlash('success', "{$count} contacts updated to status '{$status}'.");
                break;
            case 'email':
                $subject = trim((string)$this->request->post('email_subject'));
                $message = trim((string)$this->request->post('email_message'));
                if (empty($subject) || empty($message)) {
                    $this->session->setFlash('error', 'Please provide both an email subject and message body.');
                    break;
                }
                $res = \App\Services\BulkActionService::bulkEmail($ids, $subject, $message, $ctx['workspaceId'], $ctx['userId']);
                $this->session->setFlash('success', "Dispatched tracked campaign emails to {$res['sent']} contacts!" . ($res['failed'] > 0 ? " ({$res['failed']} contacts had no valid email)" : ''));
                break;
            case 'export':
                $csv = \App\Services\BulkActionService::bulkExportCsv('people', $ids, $ctx['workspaceId']);
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="contacts_export_' . date('Ymd_His') . '.csv"');
                echo $csv;
                exit;
            default:
                $this->session->setFlash('error', 'Unknown bulk action.');
                break;
        }

        $this->redirect('/people');
    }

    public function merge(): void
    {
        $ctx = $this->getContext();
        $primaryId = (int)$this->request->post('primary_id');
        $duplicateId = (int)$this->request->post('duplicate_id');

        if ($primaryId > 0 && $duplicateId > 0 && $primaryId !== $duplicateId) {
            $success = \App\Services\DuplicateDetectionService::mergeContacts($primaryId, $duplicateId, $ctx['workspaceId'], $ctx['userId']);
            if ($success) {
                $this->session->setFlash('success', 'Contacts merged successfully! Linked deals, tasks, and notes re-parented.');
            } else {
                $this->session->setFlash('error', 'Unable to merge contacts.');
            }
        } else {
            $this->session->setFlash('error', 'Invalid contact selection for merge.');
        }

        $this->redirect('/people');
    }
}

