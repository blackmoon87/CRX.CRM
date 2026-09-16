<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Interaction;
use App\Models\Task;

class DealHealthService
{
    /**
     * Compute a real-time health score (0–100) for an opportunity.
     */
    public static function calculate(array $deal, int $workspaceId): array
    {
        $stage = strtolower($deal['stage'] ?? 'lead');
        if ($stage === 'closed_won') {
            return [
                'score'   => 100,
                'status'  => 'strong',
                'badge'   => '🏆 Won (100)',
                'factors' => ['Deal successfully closed won.'],
            ];
        }

        if ($stage === 'closed_lost') {
            return [
                'score'   => 0,
                'status'  => 'critical',
                'badge'   => '❌ Lost (0)',
                'factors' => ['Deal marked as closed lost.'],
            ];
        }

        $score = 85; // Base starting score for active pipeline deal
        $factors = [];

        // 1. Inactivity & Stagnation Penalty
        $lastActivity = $deal['updated_at'] ?? $deal['created_at'] ?? date('Y-m-d H:i:s');
        $daysInactive = max(0, (int)floor((time() - strtotime($lastActivity)) / 86400));
        if ($daysInactive > 7) {
            $penalty = min(40, ($daysInactive - 7) * 4);
            $score -= $penalty;
            $factors[] = "-{$penalty} pts: Inactive for {$daysInactive} days.";
        }

        // 2. Expected Close Date Hygiene
        if (!empty($deal['expected_close_date'])) {
            $closeTime = strtotime($deal['expected_close_date']);
            if ($closeTime < time()) {
                $score -= 20;
                $factors[] = "-20 pts: Expected close date is in the past.";
            }
        }

        // 3. Interaction Recency Bonus / Penalty
        try {
            $recentInteraction = (new Interaction)->table()
                ->where('workspace_id', $workspaceId)
                ->where('entity_type', 'opportunities')
                ->where('entity_id', (int)$deal['id'])
                ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-7 days')))
                ->first();

            if ($recentInteraction) {
                $score += 15;
                $factors[] = "+15 pts: Meeting or communication logged within last 7 days.";
            } else {
                $score -= 10;
                $factors[] = "-10 pts: No communication logged in the last 7 days.";
            }
        } catch (\Throwable $e) {}

        // 4. Overdue Tasks Penalty
        try {
            $overdueCount = (new Task)->table()
                ->where('workspace_id', $workspaceId)
                ->where('entity_type', 'opportunities')
                ->where('entity_id', (int)$deal['id'])
                ->where('status', 'pending')
                ->where('due_date', '<', date('Y-m-d'))
                ->count();

            if ($overdueCount > 0) {
                $taskPenalty = min(30, $overdueCount * 10);
                $score -= $taskPenalty;
                $factors[] = "-{$taskPenalty} pts: {$overdueCount} overdue task(s).";
            }
        } catch (\Throwable $e) {}

        // Clamp between 0 and 100
        $score = max(0, min(100, $score));

        if ($score >= 70) {
            $status = 'strong';
            $badge = "🟢 Strong ({$score})";
        } elseif ($score >= 40) {
            $status = 'at_risk';
            $badge = "🟡 At Risk ({$score})";
        } else {
            $status = 'critical';
            $badge = "🔴 Critical ({$score})";
        }

        return [
            'score'   => $score,
            'status'  => $status,
            'badge'   => $badge,
            'factors' => $factors,
        ];
    }
}
