<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Person;
use App\Models\Task;
use App\Services\WorkspaceService;
use Spartan\Controller;

class DashboardController extends Controller
{
    public function index(): string
    {
        $userId = (int)$this->auth->id();
        $wsService = new WorkspaceService($this->session);
        $workspaceId = $wsService->getActiveWorkspaceId($userId);
        $workspace = $wsService->getActiveWorkspace($userId);
        $workspaces = $wsService->getUserWorkspaces($userId);

        $companiesCount = (new Company)->table()->where('workspace_id', $workspaceId)->count();
        $peopleCount    = (new Person)->table()->where('workspace_id', $workspaceId)->count();
        $deals          = (new Opportunity)->table()->where('workspace_id', $workspaceId)->get();
        $tasks          = (new Task)->table()
            ->where('workspace_id', $workspaceId)
            ->where('status', 'completed', '!=')
            ->orderBy('id', 'DESC')
            ->limit(5)
            ->get();

        $recentActivities = (new ActivityLog)->table()
            ->where('workspace_id', $workspaceId)
            ->orderBy('id', 'DESC')
            ->limit(8)
            ->get();

        $totalPipeline = 0.0;
        $wonTotal = 0.0;
        $dealsCount = count($deals);
        $stageCounts = [
            Opportunity::STAGE_LEAD        => 0,
            Opportunity::STAGE_QUALIFIED   => 0,
            Opportunity::STAGE_PROPOSAL    => 0,
            Opportunity::STAGE_NEGOTIATION => 0,
            Opportunity::STAGE_CLOSED_WON  => 0,
            Opportunity::STAGE_CLOSED_LOST => 0,
        ];

        foreach ($deals as $d) {
            $amount = (float)$d['amount'];
            $st = (string)$d['stage'];
            if (isset($stageCounts[$st])) {
                $stageCounts[$st]++;
            }
            if ($st === Opportunity::STAGE_CLOSED_WON) {
                $wonTotal += $amount;
            } else if ($st !== Opportunity::STAGE_CLOSED_LOST) {
                $totalPipeline += $amount;
            }
        }

        return $this->render('dashboard/index', [
            'title'            => 'CRM Overview',
            'workspace'        => $workspace,
            'workspaces'       => $workspaces,
            'companiesCount'   => $companiesCount,
            'peopleCount'      => $peopleCount,
            'dealsCount'       => $dealsCount,
            'totalPipeline'    => $totalPipeline,
            'wonTotal'         => $wonTotal,
            'stageCounts'      => $stageCounts,
            'stages'           => Opportunity::STAGES,
            'tasks'            => $tasks,
            'recentActivities' => $recentActivities,
        ]);
    }
}
