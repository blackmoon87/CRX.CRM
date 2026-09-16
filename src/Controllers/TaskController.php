<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Person;
use App\Models\Task;
use App\Services\ActivityLogger;
use App\Services\AutomationService;
use App\Services\WorkspaceService;
use Spartan\Controller;

class TaskController extends Controller
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
        $status = trim((string)$this->request->get('status'));
        $priority = trim((string)$this->request->get('priority'));
        $sort = strtolower(trim((string)$this->request->get('sort') ?? 'due_date'));
        $dir = strtolower(trim((string)$this->request->get('dir') ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
        $page = max(1, (int)($this->request->get('page') ?? 1));
        $perPage = min(100, max(5, (int)($this->request->get('per_page') ?? 15)));

        $allowedSorts = ['id', 'due_date', 'priority', 'status', 'title', 'created_at'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'due_date';
        }

        $query = (new Task)->table()
            ->where('workspace_id', $ctx['workspaceId'])
            ->where('deleted_at', null);

        if ($search !== '') {
            $query->where(function(\Spartan\QueryBuilder $q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $allowedFilterColumns = [
            'title'       => ['label' => 'Task Title', 'type' => 'string'],
            'description' => ['label' => 'Description', 'type' => 'string'],
            'priority'    => ['label' => 'Priority', 'type' => 'exact'],
            'status'      => ['label' => 'Status', 'type' => 'exact'],
            'due_date'    => ['label' => 'Due Date', 'type' => 'string'],
            'created_at'  => ['label' => 'Created Date', 'type' => 'string'],
        ];

        $activeFilters = \App\Services\FilterService::extractFilters($_GET, $allowedFilterColumns);
        \App\Services\FilterService::apply($query, $activeFilters, 'tasks.', $allowedFilterColumns);

        $paginator = $query->orderBy($sort, $dir)->paginate($perPage, $page);

        $companies = (new Company)->table()->where('workspace_id', $ctx['workspaceId'])->where('deleted_at', null)->get();
        $people = (new Person)->table()->where('workspace_id', $ctx['workspaceId'])->where('deleted_at', null)->get();
        $opportunities = (new Opportunity)->table()->where('workspace_id', $ctx['workspaceId'])->where('deleted_at', null)->get();
        $customFields = (new \App\Services\CustomFieldService())->getFieldsForEntity($ctx['workspaceId'], 'tasks');

        return $this->render('tasks/index', [
            'title'                => 'Tasks',
            'tasks'                => $paginator['data'],
            'pagination'           => $paginator,
            'search'               => $search,
            'status'               => $status,
            'priority'             => $priority,
            'allowedFilterColumns' => $allowedFilterColumns,
            'activeFilters'        => $activeFilters,
            'currentFilterCol'     => (string)($this->request->get('filter_col') ?? ''),
            'currentFilterOp'      => (string)($this->request->get('filter_op') ?? 'contains'),
            'currentFilterVal'     => (string)($this->request->get('filter_val') ?? ''),
            'sort'                 => $sort,
            'dir'                  => $dir,
            'perPage'              => $perPage,
            'companies'            => $companies,
            'people'               => $people,
            'opportunities'        => $opportunities,
            'customFields'         => $customFields,
            'workspace'            => $ctx['workspace'],
            'workspaces'           => $ctx['workspaces'],
        ]);
    }

    public function create(): string
    {
        $ctx = $this->getContext();

        if (!user_can('tasks.create') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', __('app.common.unauthorized_action') ?? 'You do not have permission to create tasks.');
            $this->redirect('/tasks');
            return '';
        }

        $companies = (new Company)->table()->where('workspace_id', $ctx['workspaceId'])->where('deleted_at', null)->get();
        $people = (new Person)->table()->where('workspace_id', $ctx['workspaceId'])->where('deleted_at', null)->get();
        $opportunities = (new Opportunity)->table()->where('workspace_id', $ctx['workspaceId'])->where('deleted_at', null)->get();
        $users = (new \App\Models\User)->table()->select('id', 'name', 'email')->get();
        $customFields = (new \App\Services\CustomFieldService())->getFieldsForEntity($ctx['workspaceId'], 'tasks');

        return $this->render('tasks/create', [
            'title'         => 'Create New Task',
            'companies'     => $companies,
            'people'        => $people,
            'opportunities' => $opportunities,
            'users'         => $users,
            'customFields'  => $customFields,
            'workspace'     => $ctx['workspace'],
            'workspaces'    => $ctx['workspaces'],
        ]);
    }

    public function store(): void
    {
        $ctx = $this->getContext();

        if (!user_can('tasks.create') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', __('app.common.unauthorized_action') ?? 'You do not have permission to create tasks.');
            $this->redirect('/tasks');
            return;
        }

        $body = $this->request->getBody();

        $title = trim((string)($body['title'] ?? ''));
        if ($title === '') {
            $this->session->setFlash('error', 'Task title is required.');
            $this->redirectBack('/tasks');
            return;
        }

        $entityType = !empty($body['entity_type']) ? (string)$body['entity_type'] : null;
        $entityId   = !empty($body['entity_id']) ? (int)$body['entity_id'] : null;

        $id = (new Task)->table()->insertGetId([
            'workspace_id'     => $ctx['workspaceId'],
            'title'            => $title,
            'description'      => $body['description'] ?? null,
            'due_date'         => !empty($body['due_date']) ? $body['due_date'] : null,
            'priority'         => $body['priority'] ?? 'medium',
            'status'           => 'pending',
            'entity_type'      => $entityType,
            'entity_id'        => $entityId,
            'assigned_user_id' => $ctx['userId'],
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        (new \App\Services\CustomFieldService())->saveValues($ctx['workspaceId'], 'tasks', (int)$id, $body);

        ActivityLogger::log(
            $ctx['workspaceId'],
            $ctx['userId'],
            'created',
            'tasks',
            (int)$id,
            "Created task: {$title}"
        );

        $this->session->setFlash('success', 'Task added.');
        $this->redirectBack('/tasks');
    }

    public function toggleStatus(string|int|null $id = null): void
    {
        $ctx = $this->getContext();
        $id = (int)($id ?? $this->request->getParam('id'));

        $task = (new Task)->table()
            ->where('id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->where('deleted_at', null)
            ->first();

        if ($task) {
            $newStatus = $task['status'] === 'completed' ? 'pending' : 'completed';
            (new Task)->table()->where('id', $id)->update([
                'status'     => $newStatus,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            if ($newStatus === 'completed') {
                (new AutomationService())->dispatch($ctx['workspaceId'], 'task.completed', [
                    'entity_type' => 'tasks',
                    'entity_id'   => $id,
                    'user_id'     => $ctx['userId'],
                    'task_title'  => $task['title'],
                ]);
            }

            $this->session->setFlash('success', "Task marked as {$newStatus}.");
        }

        $this->redirectBack('/tasks');
    }

    public function destroy(string|int|null $id = null): void
    {
        $ctx = $this->getContext();

        if (!user_can('tasks.delete') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', __('app.common.unauthorized_action') ?? 'You do not have permission to delete tasks.');
            $this->redirectBack('/tasks');
            return;
        }

        $id = (int)($id ?? $this->request->getParam('id'));

        $task = (new Task)->table()
            ->where('id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->first();

        if ($task) {
            // Soft delete
            (new Task)->table()->where('id', $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
            $this->session->setFlash('success', 'Task moved to Recycle Bin.');
        }

        $this->redirectBack('/tasks');
    }

    public function bulk(): void
    {
        $ctx = $this->getContext();
        $action = trim((string)$this->request->post('bulk_action'));
        $ids = (array)($this->request->post('ids') ?? []);

        if (empty($ids)) {
            $this->session->setFlash('error', 'No tasks selected.');
            $this->redirect('/tasks');
            return;
        }

        if ($action === 'delete' && !user_can('tasks.delete') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', __('app.common.unauthorized_action') ?? 'You do not have permission to delete tasks.');
            $this->redirect('/tasks');
            return;
        }

        switch ($action) {
            case 'delete':
                $count = \App\Services\BulkActionService::bulkDelete('tasks', $ids, $ctx['workspaceId'], $ctx['userId']);
                $this->session->setFlash('success', "{$count} tasks moved to Recycle Bin.");
                break;
            case 'assign':
                $targetUserId = (int)$this->request->post('assigned_user_id');
                $count = \App\Services\BulkActionService::bulkAssign('tasks', $ids, $targetUserId, $ctx['workspaceId'], $ctx['userId']);
                $this->session->setFlash('success', "{$count} tasks reassigned.");
                break;
            case 'status':
                $status = trim((string)$this->request->post('status'));
                $count = \App\Services\BulkActionService::bulkUpdateStatus('tasks', $ids, $status, $ctx['workspaceId'], $ctx['userId']);
                $this->session->setFlash('success', "{$count} tasks marked as {$status}.");
                break;
            case 'export':
                $csv = \App\Services\BulkActionService::bulkExportCsv('tasks', $ids, $ctx['workspaceId']);
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="tasks_export_' . date('Ymd_His') . '.csv"');
                echo $csv;
                exit;
            default:
                $this->session->setFlash('error', 'Unknown bulk action.');
                break;
        }

        $this->redirect('/tasks');
    }
}

