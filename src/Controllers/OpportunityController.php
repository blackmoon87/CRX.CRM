<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Company;
use App\Models\Interaction;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\Person;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Task;
use App\Services\ActivityLogger;
use App\Services\AutomationService;
use App\Services\CustomFieldService;
use App\Services\TagService;
use App\Services\WorkspaceService;
use Spartan\Controller;

class OpportunityController extends Controller
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
        $wsId = $ctx['workspaceId'];
        $viewMode = $this->request->get('view') === 'table' ? 'table' : 'kanban';

        // 1. Fetch Pipelines
        $pipelines = (new Pipeline)->table()
            ->where('workspace_id', $wsId)
            ->orderBy('id', 'ASC')
            ->get();

        if (empty($pipelines)) {
            // Auto-create default pipeline if missing
            $pipeId = (int)(new Pipeline)->table()->insert([
                'workspace_id' => $wsId,
                'name'         => 'Direct Sales Pipeline',
                'is_default'   => 1,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
            $pipelines = [['id' => $pipeId, 'name' => 'Direct Sales Pipeline', 'is_default' => 1]];
        }

        $activePipeId = (int)($this->request->get('pipeline_id') ?? ($pipelines[0]['id'] ?? 1));

        // 2. Fetch Stages for Active Pipeline
        $stages = (new PipelineStage)->table()
            ->where('pipeline_id', $activePipeId)
            ->orderBy('order_column', 'ASC')
            ->get();

        if (empty($stages)) {
            $stages = [
                ['name' => 'Lead In', 'code' => 'lead', 'probability' => 20, 'rotting_days' => 7, 'color' => '#0284C7'],
                ['name' => 'Meeting Scheduled', 'code' => 'meeting', 'probability' => 40, 'rotting_days' => 10, 'color' => '#1E8E8E'],
                ['name' => 'Proposal Sent', 'code' => 'proposal', 'probability' => 60, 'rotting_days' => 14, 'color' => '#7C3AED'],
                ['name' => 'Negotiation', 'code' => 'negotiation', 'probability' => 80, 'rotting_days' => 10, 'color' => '#EA580C'],
                ['name' => 'Closed Won', 'code' => 'closed_won', 'probability' => 100, 'rotting_days' => 999, 'color' => '#16A34A'],
                ['name' => 'Closed Lost', 'code' => 'closed_lost', 'probability' => 0, 'rotting_days' => 999, 'color' => '#DC2626'],
            ];
        }

        $search = trim((string)$this->request->get('search'));
        $selectedStage = trim((string)$this->request->get('stage'));
        $sort = strtolower(trim((string)$this->request->get('sort') ?? 'id'));
        $dir = strtolower(trim((string)$this->request->get('dir') ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $page = max(1, (int)($this->request->get('page') ?? 1));
        $perPage = min(100, max(5, (int)($this->request->get('per_page') ?? 15)));

        $allowedSorts = ['id', 'name', 'amount', 'stage', 'probability', 'expected_close_date', 'created_at'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        // 3. Fetch Deals for Active Pipeline (excluding soft-deleted)
        $query = (new Opportunity)->table()
            ->leftJoin('companies', 'opportunities.company_id', '=', 'companies.id')
            ->leftJoin('people', 'opportunities.person_id', '=', 'people.id')
            ->where('opportunities.workspace_id', $wsId)
            ->where('opportunities.deleted_at', null);

        if ($activePipeId > 0) {
            $query->where('opportunities.pipeline_id', $activePipeId);
        }

        if ($search !== '') {
            $query->where(function(\Spartan\QueryBuilder $q) use ($search) {
                $q->where('opportunities.name', 'LIKE', "%{$search}%")
                  ->orWhere('companies.name', 'LIKE', "%{$search}%")
                  ->orWhere('people.first_name', 'LIKE', "%{$search}%")
                  ->orWhere('people.last_name', 'LIKE', "%{$search}%");
            });
        }

        $allowedFilterColumns = [
            'name'                => ['label' => 'Deal Name', 'type' => 'string'],
            'amount'              => ['label' => 'Amount ($)', 'type' => 'numeric'],
            'stage'               => ['label' => 'Stage', 'type' => 'exact'],
            'probability'         => ['label' => 'Probability (%)', 'type' => 'numeric'],
            'expected_close_date' => ['label' => 'Expected Close Date', 'type' => 'string'],
            'created_at'          => ['label' => 'Created Date', 'type' => 'string'],
        ];

        $activeFilters = \App\Services\FilterService::extractFilters($_GET, $allowedFilterColumns);
        \App\Services\FilterService::apply($query, $activeFilters, 'opportunities.', $allowedFilterColumns);

        $rawDeals = $query->select(
                'opportunities.*',
                'companies.name as company_name',
                'people.first_name as person_first_name',
                'people.last_name as person_last_name'
            )
            ->orderBy("opportunities.{$sort}", $dir)
            ->get();

        // 4. Calculate Rotting and Stage Grouping
        $columns = [];
        $stageProbMap = [];
        $stageRotMap = [];
        foreach ($stages as $st) {
            $code = $st['code'];
            $stageProbMap[$code] = (int)$st['probability'];
            $stageRotMap[$code] = (int)($st['rotting_days'] ?? 14);
            $columns[$code] = [
                'key'          => $code,
                'title'        => $st['name'],
                'color'        => $st['color'] ?? '#0284C7',
                'probability'  => (int)$st['probability'],
                'rotting_days' => (int)($st['rotting_days'] ?? 14),
                'deals'        => [],
                'total'        => 0.0,
            ];
        }

        $nominalTotal = 0.0;
        $weightedTotal = 0.0;
        $rottingCount = 0;
        $now = new \DateTime();

        foreach ($rawDeals as &$d) {
            $st = (string)($d['stage'] ?? 'lead');
            $amt = (float)($d['amount'] ?? 0);
            $prob = $stageProbMap[$st] ?? 20;
            $rotDays = $stageRotMap[$st] ?? 14;

            // Check rotting: if not closed and inactive past rotting_days
            $d['is_rotting'] = false;
            $d['rotting_days'] = 0;
            if ($st !== 'closed_won' && $st !== 'closed_lost') {
                $lastActivity = !empty($d['updated_at']) ? new \DateTime($d['updated_at']) : new \DateTime($d['created_at'] ?? 'now');
                $diff = $now->diff($lastActivity)->days;
                if ($diff >= $rotDays) {
                    $d['is_rotting'] = true;
                    $d['rotting_days'] = $diff;
                    $rottingCount++;
                }
            }

            // Real-Time Deal Health Scoring
            $health = \App\Services\DealHealthService::calculate($d, $ctx['workspaceId']);
            $d['health_score']  = $health['score'];
            $d['health_status'] = $health['status'];
            $d['health_badge']  = $health['badge'];

            if (!isset($columns[$st])) {
                $columns[$st] = ['key' => $st, 'title' => ucfirst($st), 'color' => '#0284C7', 'probability' => $prob, 'deals' => [], 'total' => 0.0];
            }
            $columns[$st]['deals'][] = $d;
            $columns[$st]['total'] += $amt;

            $nominalTotal += $amt;
            if ($st !== 'closed_lost') {
                $weightedTotal += ($amt * ($prob / 100));
            }
        }
        unset($d);

        // Compute Paginated Subset for Table View
        $totalDealsCount = count($rawDeals);
        $offset = ($page - 1) * $perPage;
        $tableSlice = array_slice($rawDeals, $offset, $perPage);
        $paginator = [
            'data'         => $tableSlice,
            'total'        => $totalDealsCount,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int)ceil($totalDealsCount / $perPage),
        ];

        return $this->render('opportunities/index', [
            'title'                => 'Deals & Pipeline',
            'viewMode'             => $viewMode,
            'pipelines'            => $pipelines,
            'activePipeId'         => $activePipeId,
            'columns'              => $columns,
            'allDeals'             => $rawDeals,
            'tableDeals'           => $tableSlice,
            'pagination'           => $paginator,
            'search'               => $search,
            'selectedStage'        => $selectedStage,
            'allowedFilterColumns' => $allowedFilterColumns,
            'activeFilters'        => $activeFilters,
            'currentFilterCol'     => (string)($this->request->get('filter_col') ?? ''),
            'currentFilterOp'      => (string)($this->request->get('filter_op') ?? 'contains'),
            'currentFilterVal'     => (string)($this->request->get('filter_val') ?? ''),
            'sort'                 => $sort,
            'dir'                  => $dir,
            'perPage'              => $perPage,
            'nominalTotal'         => $nominalTotal,
            'weightedTotal'        => $weightedTotal,
            'rottingCount'         => $rottingCount,
            'stages'               => $stages,
            'workspace'            => $ctx['workspace'],
            'workspaces'           => $ctx['workspaces'],
        ]);
    }

    public function create(): string
    {
        $ctx = $this->getContext();

        if (!user_can('deals.create') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', __('app.common.unauthorized_action') ?? 'You do not have permission to create deals.');
            $this->redirect('/opportunities');
            return '';
        }

        $companies = (new Company)->table()->where('workspace_id', $ctx['workspaceId'])->where('deleted_at', null)->get();
        $people = (new Person)->table()->where('workspace_id', $ctx['workspaceId'])->where('deleted_at', null)->get();

        $pipelines = (new Pipeline)->table()->where('workspace_id', $ctx['workspaceId'])->get();

        $selectedCompanyId = (int)($this->request->get('company_id') ?? 0);
        $selectedPersonId  = (int)($this->request->get('person_id') ?? 0);

        $cfService = new CustomFieldService();
        $customFields = $cfService->getFieldsForEntity($ctx['workspaceId'], 'opportunities');

        return $this->render('opportunities/create', [
            'title'             => 'Add Opportunity',
            'companies'         => $companies,
            'people'            => $people,
            'pipelines'         => $pipelines,
            'selectedCompanyId' => $selectedCompanyId,
            'selectedPersonId'  => $selectedPersonId,
            'stages'            => Opportunity::STAGES,
            'customFields'      => $customFields,
            'workspace'         => $ctx['workspace'],
            'workspaces'        => $ctx['workspaces'],
        ]);
    }

    public function store(): void
    {
        $ctx = $this->getContext();

        if (!user_can('deals.create') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', __('app.common.unauthorized_action') ?? 'You do not have permission to create deals.');
            $this->redirect('/opportunities');
            return;
        }

        $body = $this->request->getBody();

        $name = trim((string)($body['name'] ?? ''));
        if ($name === '') {
            $this->session->setFlash('error', 'Deal name is required.');
            $this->redirect('/opportunities/create');
            return;
        }

        $amount = (float)($body['amount'] ?? 0.0);
        $stage = trim((string)($body['stage'] ?? 'lead'));
        $pipeId = !empty($body['pipeline_id']) ? (int)$body['pipeline_id'] : 1;

        $id = (new Opportunity)->table()->insert([
            'workspace_id'        => $ctx['workspaceId'],
            'pipeline_id'         => $pipeId,
            'company_id'          => !empty($body['company_id']) ? (int)$body['company_id'] : null,
            'person_id'           => !empty($body['person_id']) ? (int)$body['person_id'] : null,
            'name'                => $name,
            'amount'              => $amount,
            'currency'            => $body['currency'] ?? 'USD',
            'stage'               => $stage,
            'probability'         => (int)($body['probability'] ?? 20),
            'expected_close_date' => !empty($body['expected_close_date']) ? $body['expected_close_date'] : null,
            'status'              => 'open',
            'assigned_user_id'    => $ctx['userId'],
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        $cfService = new CustomFieldService();
        $cfService->saveValues($ctx['workspaceId'], 'opportunities', (int)$id, $body);

        ActivityLogger::log($ctx['workspaceId'], $ctx['userId'], 'created', 'opportunities', (int)$id, "Created deal: {$name} ({$amount})");

        (new AutomationService())->dispatch($ctx['workspaceId'], 'deal.created', [
            'entity_type' => 'opportunities',
            'entity_id'   => (int)$id,
            'user_id'     => $ctx['userId'],
            'deal_name'   => $name,
            'amount'      => $amount,
            'stage'       => $stage,
        ]);

        $this->session->setFlash('success', "Deal '{$name}' created!");
        $this->redirectBack("/opportunities/{$id}");
    }

    public function show(string|int|null $id = null): string
    {
        $ctx = $this->getContext();
        $id = (int)($id ?? $this->request->getParam('id'));

        $deal = (new Opportunity)->table()
            ->leftJoin('companies', 'opportunities.company_id', '=', 'companies.id')
            ->leftJoin('people', 'opportunities.person_id', '=', 'people.id')
            ->where('opportunities.id', $id)
            ->where('opportunities.workspace_id', $ctx['workspaceId'])
            ->where('opportunities.deleted_at', null)
            ->select(
                'opportunities.*',
                'companies.name as company_name',
                'people.first_name as person_first_name',
                'people.last_name as person_last_name',
                'people.email as person_email'
            )
            ->first();

        if (!$deal) {
            $this->session->setFlash('error', 'Opportunity not found or in Recycle Bin.');
            $this->redirect('/opportunities');
            return '';
        }

        // Unified Interactions Timeline
        $interactions = (new Interaction)->table()
            ->where('workspace_id', $ctx['workspaceId'])
            ->where('entity_type', 'opportunities')
            ->where('entity_id', $id)
            ->orderBy('id', 'DESC')
            ->get();

        $tasks = (new Task)->table()
            ->where('entity_type', 'opportunities')
            ->where('entity_id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->where('deleted_at', null)
            ->get();

        // Tags
        $tagService = new TagService();
        $tags = $tagService->getEntityTags('opportunities', $id);
        $allTags = $tagService->getWorkspaceTags($ctx['workspaceId']);

        $cfService = new CustomFieldService();
        $customFields = $cfService->getFieldsForEntity($ctx['workspaceId'], 'opportunities');
        $customValues = $cfService->getValuesForEntity($ctx['workspaceId'], 'opportunities', $id);

        return $this->render('opportunities/show', [
            'title'        => $deal['name'],
            'deal'         => $deal,
            'stages'       => Opportunity::STAGES,
            'interactions' => $interactions,
            'tasks'        => $tasks,
            'tags'         => $tags,
            'allTags'      => $allTags,
            'customFields' => $customFields,
            'customValues' => $customValues,
            'workspace'    => $ctx['workspace'],
            'workspaces'   => $ctx['workspaces'],
        ]);
    }

    public function updateStage(): void
    {
        $ctx = $this->getContext();
        $body = $this->request->getBody();

        $dealId = (int)($body['deal_id'] ?? 0);
        $newStage = (string)($body['stage'] ?? '');
        $lostReason = trim((string)($body['lost_reason'] ?? ''));

        $deal = (new Opportunity)->table()
            ->where('id', $dealId)
            ->where('workspace_id', $ctx['workspaceId'])
            ->first();

        if ($deal) {
            $update = [
                'stage'      => $newStage,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($newStage === 'closed_won') {
                $update['status'] = 'won';
                $update['probability'] = 100;
            } elseif ($newStage === 'closed_lost') {
                $update['status'] = 'lost';
                $update['probability'] = 0;
                if ($lostReason !== '') {
                    $update['lost_reason'] = $lostReason;
                }
            }

            (new Opportunity)->table()->where('id', $dealId)->update($update);

            // Log activity & timeline interaction
            (new Interaction)->table()->insert([
                'workspace_id' => $ctx['workspaceId'],
                'user_id'      => $ctx['userId'],
                'entity_type'  => 'opportunities',
                'entity_id'    => $dealId,
                'type'         => 'stage_change',
                'title'        => "Stage changed to " . ucfirst(str_replace('_', ' ', $newStage)),
                'description'  => "Deal advanced to {$newStage}" . ($lostReason ? " (Reason: {$lostReason})" : ""),
                'created_at'   => date('Y-m-d H:i:s'),
            ]);

            ActivityLogger::log($ctx['workspaceId'], $ctx['userId'], 'stage_changed', 'opportunities', $dealId, "Deal moved to {$newStage}");

            // Dispatch Automation Rule Events
            $autoService = new AutomationService();
            if ($newStage === 'closed_won') {
                $autoService->dispatch($ctx['workspaceId'], 'deal.won', [
                    'deal_id'     => $dealId,
                    'deal_name'   => $deal['name'],
                    'entity_type' => 'opportunities',
                    'entity_id'   => $dealId,
                    'user_id'     => $ctx['userId'],
                ]);
            } elseif ($newStage === 'closed_lost') {
                $autoService->dispatch($ctx['workspaceId'], 'deal.lost', [
                    'deal_id'     => $dealId,
                    'deal_name'   => $deal['name'],
                    'lost_reason' => $lostReason,
                    'entity_type' => 'opportunities',
                    'entity_id'   => $dealId,
                    'user_id'     => $ctx['userId'],
                ]);
            }

            $this->session->setFlash('success', "Deal updated to " . ucfirst($newStage));
        }

        $redirectUrl = !empty($body['_redirect']) ? (string)$body['_redirect'] : "/opportunities/{$dealId}";
        if (str_contains(strtolower($this->request->header('Accept', '')), 'application/json')) {
            $this->response->json(['success' => true, 'deal_id' => $dealId, 'stage' => $newStage]);
            return;
        }

        $this->redirectBack($redirectUrl);
    }

    public function destroy(string|int|null $id = null): void
    {
        $ctx = $this->getContext();

        if (!user_can('deals.delete') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', __('app.common.unauthorized_action') ?? 'You do not have permission to delete deals.');
            $this->redirect('/opportunities');
            return;
        }

        $id = (int)($id ?? $this->request->getParam('id'));

        $deal = (new Opportunity)->table()
            ->where('id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->first();

        if ($deal) {
            // Soft delete
            (new Opportunity)->table()->where('id', $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
            ActivityLogger::log($ctx['workspaceId'], $ctx['userId'], 'deleted', 'opportunities', $id, "Moved deal to Recycle Bin: {$deal['name']}");
            $this->session->setFlash('success', 'Deal moved to Recycle Bin.');
        }

        $this->redirect('/opportunities');
    }

    public function bulk(): void
    {
        $ctx = $this->getContext();
        $action = trim((string)$this->request->post('bulk_action'));
        $ids = (array)($this->request->post('ids') ?? []);

        if (empty($ids)) {
            $this->session->setFlash('error', 'No opportunities selected.');
            $this->redirect('/opportunities');
            return;
        }

        if ($action === 'delete' && !user_can('deals.delete') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', __('app.common.unauthorized_action') ?? 'You do not have permission to delete deals.');
            $this->redirect('/opportunities');
            return;
        }

        if ($action === 'export' && !user_can('deals.export') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', __('app.common.unauthorized_action') ?? 'You do not have permission to export deals.');
            $this->redirect('/opportunities');
            return;
        }

        switch ($action) {
            case 'delete':
                $count = \App\Services\BulkActionService::bulkDelete('opportunities', $ids, $ctx['workspaceId'], $ctx['userId']);
                $this->session->setFlash('success', "{$count} deals moved to Recycle Bin.");
                break;
            case 'assign':
                $targetUserId = (int)$this->request->post('assigned_user_id');
                $count = \App\Services\BulkActionService::bulkAssign('opportunities', $ids, $targetUserId, $ctx['workspaceId'], $ctx['userId']);
                $this->session->setFlash('success', "{$count} deals reassigned.");
                break;
            case 'status':
            case 'stage':
                $stage = trim((string)$this->request->post('stage') ?: (string)$this->request->post('status'));
                $count = \App\Services\BulkActionService::bulkUpdateStatus('opportunities', $ids, $stage, $ctx['workspaceId'], $ctx['userId']);
                $this->session->setFlash('success', "{$count} deals updated to stage '{$stage}'.");
                break;
            case 'export':
                $csv = \App\Services\BulkActionService::bulkExportCsv('opportunities', $ids, $ctx['workspaceId']);
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="opportunities_export_' . date('Ymd_His') . '.csv"');
                echo $csv;
                exit;
            default:
                $this->session->setFlash('error', 'Unknown bulk action.');
                break;
        }

        $this->redirect('/opportunities');
    }
}

