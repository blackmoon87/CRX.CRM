<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Interaction;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\WorkspaceService;
use Spartan\Application;
use Spartan\Controller;

class ReportController extends Controller
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

    private function resolveDateRange(?string $period, ?string $dateFrom, ?string $dateTo): array
    {
        $now = new \DateTime();
        $period = $period ?? 'all';

        switch ($period) {
            case 'today':
                $start = $now->format('Y-m-d 00:00:00');
                $end   = $now->format('Y-m-d 23:59:59');
                $label = 'Today';
                break;
            case 'this_week':
                $start = (clone $now)->modify('monday this week')->format('Y-m-d 00:00:00');
                $end   = (clone $now)->modify('sunday this week')->format('Y-m-d 23:59:59');
                $label = 'This Week';
                break;
            case 'this_month':
                $start = $now->format('Y-m-01 00:00:00');
                $end   = $now->format('Y-m-t 23:59:59');
                $label = 'This Month';
                break;
            case 'last_30_days':
                $start = (clone $now)->modify('-30 days')->format('Y-m-d 00:00:00');
                $end   = $now->format('Y-m-d 23:59:59');
                $label = 'Last 30 Days';
                break;
            case 'this_quarter':
                $curQuarter = (int)ceil((int)$now->format('n') / 3);
                $startMonth = str_pad((string)(($curQuarter - 1) * 3 + 1), 2, '0', STR_PAD_LEFT);
                $start = $now->format("Y-{$startMonth}-01 00:00:00");
                $end   = $now->format('Y-m-d 23:59:59');
                $label = 'This Quarter';
                break;
            case 'this_year':
                $start = $now->format('Y-01-01 00:00:00');
                $end   = $now->format('Y-12-31 23:59:59');
                $label = 'This Year';
                break;
            case 'custom':
                $start = !empty($dateFrom) ? $dateFrom . ' 00:00:00' : null;
                $end   = !empty($dateTo) ? $dateTo . ' 23:59:59' : null;
                $label = 'Custom: ' . ($dateFrom ?: 'Start') . ' - ' . ($dateTo ?: 'Now');
                break;
            case 'all':
            default:
                $start = null;
                $end   = null;
                $label = 'All Time';
                break;
        }

        return [
            'period'    => $period,
            'startDate' => $start,
            'endDate'   => $end,
            'label'     => $label,
            'dateFrom'  => $dateFrom ?: ($start ? substr($start, 0, 10) : ''),
            'dateTo'    => $dateTo ?: ($end ? substr($end, 0, 10) : ''),
        ];
    }

    public function index(): string
    {
        $ctx = $this->getContext();
        $wsId = $ctx['workspaceId'];
        $db = Application::$app->db;

        // 1. Extract Filter Parameters
        $periodParam     = $this->request->get('period') ?? 'all';
        $dateFromParam   = $this->request->get('date_from');
        $dateToParam     = $this->request->get('date_to');
        $pipelineIdParam = !empty($this->request->get('pipeline_id')) ? (int)$this->request->get('pipeline_id') : null;
        $userIdParam     = !empty($this->request->get('user_id')) ? (int)$this->request->get('user_id') : null;
        $stageParam      = !empty($this->request->get('stage')) ? trim($this->request->get('stage')) : null;

        // If user entered dates manually but kept another period, treat as custom
        if (!empty($dateFromParam) || !empty($dateToParam)) {
            if ($periodParam === 'all') {
                $periodParam = 'custom';
            }
        }

        $dateInfo = $this->resolveDateRange($periodParam, $dateFromParam, $dateToParam);
        $startDate = $dateInfo['startDate'];
        $endDate   = $dateInfo['endDate'];

        // 2. Fetch Pipelines & Team Members for Filter Controls
        $pipelines = (new Pipeline)->table()
            ->where('workspace_id', $wsId)
            ->get();

        $teamMembers = $db->query("
            SELECT u.id, u.name, u.email
            FROM users u
            JOIN memberships m ON m.user_id = u.id
            WHERE m.workspace_id = {$wsId}
            ORDER BY u.name ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        // 3. Prepare Stage Definitions
        $stages = [
            'lead'        => ['label' => 'Lead In', 'color' => '#0284C7', 'count' => 0, 'value' => 0.0],
            'meeting'     => ['label' => 'Meeting Scheduled', 'color' => '#1E8E8E', 'count' => 0, 'value' => 0.0],
            'proposal'    => ['label' => 'Proposal Sent', 'color' => '#7C3AED', 'count' => 0, 'value' => 0.0],
            'negotiation' => ['label' => 'Negotiation', 'color' => '#EA580C', 'count' => 0, 'value' => 0.0],
            'closed_won'  => ['label' => 'Closed Won', 'color' => '#16A34A', 'count' => 0, 'value' => 0.0],
            'closed_lost' => ['label' => 'Closed Lost', 'color' => '#DC2626', 'count' => 0, 'value' => 0.0],
        ];

        if ($pipelineIdParam) {
            $pipeStages = (new PipelineStage)->table()
                ->where('pipeline_id', $pipelineIdParam)
                ->orderBy('order_column', 'ASC')
                ->get();

            if (!empty($pipeStages)) {
                $customStages = [];
                foreach ($pipeStages as $ps) {
                    $code = $ps['code'] ?? strtolower(str_replace(' ', '_', $ps['name']));
                    $customStages[$code] = [
                        'label' => $ps['name'],
                        'color' => $ps['color'] ?? '#4f46e5',
                        'count' => 0,
                        'value' => 0.0,
                    ];
                }
                $stages = $customStages;
            }
        }

        // 4. Query Filtered Opportunities
        $oppQuery = (new Opportunity)->table()
            ->where('workspace_id', $wsId)
            ->where('deleted_at', null);

        if ($pipelineIdParam) {
            $oppQuery->where('pipeline_id', $pipelineIdParam);
        }
        if ($userIdParam) {
            $oppQuery->where('assigned_user_id', $userIdParam);
        }
        if ($stageParam) {
            $oppQuery->where('stage', $stageParam);
        }
        if ($startDate && $endDate) {
            $oppQuery->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $oppQuery->where('created_at', $startDate, '>=');
        } elseif ($endDate) {
            $oppQuery->where('created_at', $endDate, '<=');
        }

        $opps = $oppQuery->orderBy('created_at', 'DESC')->get();

        // 5. Aggregate KPIs from Filtered Opportunities
        $totalWonValue      = 0.0;
        $totalPipelineValue = 0.0;
        $totalWonCount      = 0;
        $totalLostCount     = 0;
        $totalCycleDays     = 0;

        foreach ($opps as $o) {
            $st  = $o['stage'] ?? 'lead';
            $amt = (float)($o['amount'] ?? 0);

            if (!isset($stages[$st])) {
                $stages[$st] = [
                    'label' => ucfirst(str_replace('_', ' ', $st)),
                    'color' => '#64748B',
                    'count' => 0,
                    'value' => 0.0,
                ];
            }

            $stages[$st]['count']++;
            $stages[$st]['value'] += $amt;

            if ($st === 'closed_won') {
                $totalWonValue += $amt;
                $totalWonCount++;
                if (!empty($o['created_at']) && !empty($o['updated_at'])) {
                    $cDate = new \DateTime($o['created_at']);
                    $uDate = new \DateTime($o['updated_at']);
                    $totalCycleDays += max(1, $uDate->diff($cDate)->days);
                }
            } elseif ($st === 'closed_lost') {
                $totalLostCount++;
            } else {
                $totalPipelineValue += $amt;
            }
        }

        $winRate = ($totalWonCount + $totalLostCount) > 0
            ? round(($totalWonCount / ($totalWonCount + $totalLostCount)) * 100, 1)
            : 0;

        $avgCycleDays = $totalWonCount > 0
            ? round($totalCycleDays / $totalWonCount, 1)
            : 0;

        // 6. Dynamic Team Member Leaderboard with Filter Constraints
        $oppFilterSql = "workspace_id = {$wsId} AND deleted_at IS NULL";
        if ($pipelineIdParam) {
            $oppFilterSql .= " AND pipeline_id = {$pipelineIdParam}";
        }
        if ($stageParam) {
            $oppFilterSql .= " AND stage = " . $db->quote($stageParam);
        }
        if ($startDate && $endDate) {
            $oppFilterSql .= " AND created_at BETWEEN '{$startDate}' AND '{$endDate}'";
        } elseif ($startDate) {
            $oppFilterSql .= " AND created_at >= '{$startDate}'";
        } elseif ($endDate) {
            $oppFilterSql .= " AND created_at <= '{$endDate}'";
        }

        $actFilterSql = "workspace_id = {$wsId}";
        if ($startDate && $endDate) {
            $actFilterSql .= " AND created_at BETWEEN '{$startDate}' AND '{$endDate}'";
        } elseif ($startDate) {
            $actFilterSql .= " AND created_at >= '{$startDate}'";
        } elseif ($endDate) {
            $actFilterSql .= " AND created_at <= '{$endDate}'";
        }

        $memberFilterSql = "";
        if ($userIdParam) {
            $memberFilterSql = " AND u.id = {$userIdParam}";
        }

        $members = $db->query("
            SELECT u.id, u.name, u.email,
                (SELECT COUNT(*) FROM opportunities WHERE assigned_user_id = u.id AND {$oppFilterSql}) as total_deals,
                (SELECT COALESCE(SUM(amount), 0) FROM opportunities WHERE assigned_user_id = u.id AND stage = 'closed_won' AND {$oppFilterSql}) as won_revenue,
                (SELECT COUNT(*) FROM interactions WHERE user_id = u.id AND {$actFilterSql}) as activities_logged
            FROM users u
            JOIN memberships m ON m.user_id = u.id
            WHERE m.workspace_id = {$wsId} {$memberFilterSql}
            ORDER BY won_revenue DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        // 7. Dynamic Activity Breakdown (Calls, Meetings, Notes, Emails)
        $actUserSql = $userIdParam ? " AND user_id = {$userIdParam}" : "";
        $actBreakdown = $db->query("
            SELECT type, COUNT(*) as cnt 
            FROM interactions 
            WHERE {$actFilterSql} {$actUserSql}
            GROUP BY type
        ")->fetchAll(\PDO::FETCH_KEY_PAIR);

        // 8. Active Filters Meta & Human-readable summary
        $activeFilters = [
            'period'      => $dateInfo['period'],
            'date_from'   => $dateInfo['dateFrom'],
            'date_to'     => $dateInfo['dateTo'],
            'pipeline_id' => $pipelineIdParam,
            'user_id'     => $userIdParam,
            'stage'       => $stageParam,
        ];

        $summaryParts = [];
        $summaryParts[] = $dateInfo['label'];

        if ($pipelineIdParam) {
            $pipeName = 'Selected Pipeline';
            foreach ($pipelines as $p) {
                if ($p['id'] == $pipelineIdParam) {
                    $pipeName = $p['name'];
                    break;
                }
            }
            $summaryParts[] = $pipeName;
        } else {
            $summaryParts[] = 'All Pipelines';
        }

        if ($userIdParam) {
            $userName = 'Selected Rep';
            foreach ($teamMembers as $m) {
                if ($m['id'] == $userIdParam) {
                    $userName = $m['name'];
                    break;
                }
            }
            $summaryParts[] = $userName;
        } else {
            $summaryParts[] = 'Entire Team';
        }

        if ($stageParam) {
            $summaryParts[] = 'Stage: ' . ($stages[$stageParam]['label'] ?? $stageParam);
        }

        $activeScopeSummary = implode(' • ', $summaryParts);

        return $this->render('reports/index', [
            'title'              => 'Executive Analytics & Sales Velocity',
            'stages'             => $stages,
            'totalWonValue'      => $totalWonValue,
            'totalPipelineValue' => $totalPipelineValue,
            'winRate'            => $winRate,
            'avgCycleDays'       => $avgCycleDays,
            'totalDealsCount'    => count($opps),
            'totalWonCount'      => $totalWonCount,
            'totalLostCount'     => $totalLostCount,
            'members'            => $members,
            'actBreakdown'       => $actBreakdown,
            'pipelines'          => $pipelines,
            'teamMembers'        => $teamMembers,
            'filteredDeals'      => array_slice($opps, 0, 50),
            'activeFilters'      => $activeFilters,
            'activeScopeSummary' => $activeScopeSummary,
            'workspace'          => $ctx['workspace'],
            'workspaces'         => $ctx['workspaces'],
        ]);
    }

    public function export(): void
    {
        $ctx = $this->getContext();
        $wsId = $ctx['workspaceId'];

        $periodParam     = $this->request->get('period') ?? 'all';
        $dateFromParam   = $this->request->get('date_from');
        $dateToParam     = $this->request->get('date_to');
        $pipelineIdParam = !empty($this->request->get('pipeline_id')) ? (int)$this->request->get('pipeline_id') : null;
        $userIdParam     = !empty($this->request->get('user_id')) ? (int)$this->request->get('user_id') : null;
        $stageParam      = !empty($this->request->get('stage')) ? trim($this->request->get('stage')) : null;

        $dateInfo = $this->resolveDateRange($periodParam, $dateFromParam, $dateToParam);
        $startDate = $dateInfo['startDate'];
        $endDate   = $dateInfo['endDate'];

        $oppQuery = (new Opportunity)->table()
            ->where('workspace_id', $wsId)
            ->where('deleted_at', null);

        if ($pipelineIdParam) {
            $oppQuery->where('pipeline_id', $pipelineIdParam);
        }
        if ($userIdParam) {
            $oppQuery->where('assigned_user_id', $userIdParam);
        }
        if ($stageParam) {
            $oppQuery->where('stage', $stageParam);
        }
        if ($startDate && $endDate) {
            $oppQuery->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $oppQuery->where('created_at', $startDate, '>=');
        } elseif ($endDate) {
            $oppQuery->where('created_at', $endDate, '<=');
        }

        $opps = $oppQuery->orderBy('created_at', 'DESC')->get();

        $filename = 'crm_report_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Deal Name', 'Amount', 'Currency', 'Stage', 'Assigned User ID', 'Pipeline ID', 'Expected Close', 'Created Date']);

        foreach ($opps as $row) {
            fputcsv($out, [
                $row['id'] ?? '',
                $row['title'] ?? $row['name'] ?? '',
                $row['amount'] ?? 0,
                $row['currency'] ?? 'USD',
                $row['stage'] ?? '',
                $row['assigned_user_id'] ?? '',
                $row['pipeline_id'] ?? '',
                $row['expected_close'] ?? '',
                $row['created_at'] ?? '',
            ]);
        }

        fclose($out);
        exit;
    }
}
