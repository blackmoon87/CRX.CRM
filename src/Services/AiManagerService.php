<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Person;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\Note;
use App\Models\Interaction;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\BookingSetting;
use App\Models\EmailTracking;
use App\Models\ActivityLog;
use App\Models\User;

class AiManagerService
{
    /**
     * Autonomous Executive Daily Briefing for AI Manager & Leadership.
     */
    public function getExecutiveBriefing(int $workspaceId, ?int $userId): array
    {
        $today = date('Y-m-d');
        $sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
        $fourteenDaysAgo = date('Y-m-d H:i:s', strtotime('-14 days'));

        // 1. Leads overview
        $peopleTable = (new Person)->table()->where('workspace_id', $workspaceId)->whereNull('deleted_at');
        $totalContacts = $peopleTable->count();
        $recentLeads = (new Person)->table()
            ->where('workspace_id', $workspaceId)
            ->whereNull('deleted_at')
            ->where('created_at', '>=', $sevenDaysAgo)
            ->count();

        // 2. Opportunities & Pipeline
        $opps = (new Opportunity)->table()
            ->where('workspace_id', $workspaceId)
            ->whereNull('deleted_at')
            ->get();

        $openOpps = 0;
        $totalPipelineValue = 0.0;
        $wonValueThisMonth = 0.0;
        $rottingDeals = [];

        $startOfMonth = date('Y-m-01 00:00:00');

        foreach ($opps as $opp) {
            $val = (float)($opp['amount'] ?? 0);
            $stage = (string)($opp['stage'] ?? 'lead');

            if ($stage === 'won') {
                if (($opp['updated_at'] ?? '') >= $startOfMonth) {
                    $wonValueThisMonth += $val;
                }
            } elseif ($stage !== 'lost') {
                $openOpps++;
                $totalPipelineValue += $val;

                // Check rotting (not updated in > 14 days)
                $updatedAt = $opp['updated_at'] ?? $opp['created_at'] ?? '';
                if ($updatedAt && $updatedAt <= $fourteenDaysAgo) {
                    $daysStagnant = (int)floor((time() - strtotime($updatedAt)) / 86400);
                    $rottingDeals[] = [
                        'id'            => $opp['id'],
                        'name'          => $opp['name'],
                        'amount'        => $val,
                        'stage'         => $stage,
                        'days_stagnant' => $daysStagnant,
                    ];
                }
            }
        }

        // Sort rotting deals by amount desc
        usort($rottingDeals, fn($a, $b) => $b['amount'] <=> $a['amount']);
        $rottingDeals = array_slice($rottingDeals, 0, 5);

        // 3. Urgent Tasks
        $urgentTasks = (new Task)->table()
            ->where('workspace_id', $workspaceId)
            ->whereNull('deleted_at')
            ->where('status', '!=', 'completed')
            ->where('due_date', '<=', $today)
            ->get();

        // 4. Quotes & Proposals
        $quotes = (new Quote)->table()
            ->where('workspace_id', $workspaceId)
            ->get();

        $pendingQuotes = 0;
        $acceptedQuotes = 0;
        $pendingQuotesValue = 0.0;

        foreach ($quotes as $q) {
            $qVal = (float)($q['total_amount'] ?? 0);
            if ($q['status'] === 'accepted') {
                $acceptedQuotes++;
            } elseif ($q['status'] === 'sent' || $q['status'] === 'draft') {
                $pendingQuotes++;
                $pendingQuotesValue += $qVal;
            }
        }

        // 5. Engagement tracking metrics
        $trackings = (new EmailTracking)->table()
            ->where('workspace_id', $workspaceId)
            ->get();
        $totalEmailsTracked = count($trackings);
        $openedEmails = 0;
        $clickedEmails = 0;
        foreach ($trackings as $tr) {
            if ((int)($tr['open_count'] ?? 0) > 0) $openedEmails++;
            if ((int)($tr['click_count'] ?? 0) > 0) $clickedEmails++;
        }
        $openRatePct = $totalEmailsTracked > 0 ? round(($openedEmails / $totalEmailsTracked) * 100, 1) : 0.0;

        // Executive Urgency & Recommendations
        $urgency = 'normal';
        $recommendations = [];

        if (count($rottingDeals) > 0) {
            $urgency = 'high';
            $topRot = $rottingDeals[0];
            $recommendations[] = "Attention Needed: {$topRot['name']} ($" . number_format($topRot['amount'], 2) . ") has been stagnant for {$topRot['days_stagnant']} days. Recommend re-engagement.";
        }

        if (count($urgentTasks) > 0) {
            if ($urgency !== 'high') $urgency = 'moderate';
            $recommendations[] = "There are " . count($urgentTasks) . " urgent task(s) overdue or due today.";
        }

        if ($pendingQuotes > 0) {
            $recommendations[] = "{$pendingQuotes} active proposal(s) worth $" . number_format($pendingQuotesValue, 2) . " awaiting client sign-off.";
        }

        if ($recentLeads === 0) {
            $recommendations[] = "No new leads acquired in the last 7 days. Check web forms and marketing campaigns.";
        }

        return [
            'timestamp'           => date('Y-m-d H:i:s'),
            'urgency_level'       => $urgency,
            'kpis' => [
                'total_contacts'       => $totalContacts,
                'new_leads_7d'         => $recentLeads,
                'active_deals_count'   => $openOpps,
                'pipeline_value'       => round($totalPipelineValue, 2),
                'won_value_this_month' => round($wonValueThisMonth, 2),
                'urgent_tasks_count'   => count($urgentTasks),
                'pending_proposals'    => $pendingQuotes,
                'proposals_value'      => round($pendingQuotesValue, 2),
                'tracked_emails_sent'  => $totalEmailsTracked,
                'email_open_rate_pct'  => $openRatePct,
            ],
            'top_rotting_deals'    => $rottingDeals,
            'urgent_tasks'         => array_map(fn($t) => [
                'id'       => $t['id'],
                'title'    => $t['title'],
                'priority' => $t['priority'],
                'due_date' => $t['due_date'],
            ], array_slice($urgentTasks, 0, 5)),
            'executive_recommendations' => $recommendations,
        ];
    }

    /**
     * Predictive AI Lead Scoring (0 - 100).
     */
    public function calculateLeadScore(int $workspaceId, int $personId): array
    {
        $person = (new Person)->table()
            ->where('id', $personId)
            ->where('workspace_id', $workspaceId)
            ->first();

        if (!$person) {
            return ['error' => "Contact/Lead #{$personId} not found."];
        }

        $score = 0;
        $signals = [];

        // 1. Profile completeness (up to 20 pts)
        if (!empty($person['email'])) {
            $score += 10;
            $signals[] = ['factor' => 'Verified Email', 'points' => 10];
        }
        if (!empty($person['phone'])) {
            $score += 5;
            $signals[] = ['factor' => 'Direct Phone Available', 'points' => 5];
        }
        if (!empty($person['job_title'])) {
            $score += 5;
            $signals[] = ['factor' => 'Decision Maker Title: ' . $person['job_title'], 'points' => 5];
        }

        // 2. Company Association (15 pts)
        if (!empty($person['company_id'])) {
            $score += 15;
            $signals[] = ['factor' => 'Linked to Verified Account/Company', 'points' => 15];
        }

        // 3. Email Engagement Tracking (up to 25 pts)
        $trackings = (new EmailTracking)->table()
            ->where('workspace_id', $workspaceId)
            ->where('entity_type', 'people')
            ->where('entity_id', $personId)
            ->get();

        $openCount = 0;
        $clickCount = 0;
        foreach ($trackings as $tr) {
            $openCount += (int)($tr['open_count'] ?? 0);
            $clickCount += (int)($tr['click_count'] ?? 0);
        }

        if ($clickCount > 0) {
            $score += 25;
            $signals[] = ['factor' => "High Intent: Clicked links {$clickCount} times in sent emails", 'points' => 25];
        } elseif ($openCount > 0) {
            $score += 15;
            $signals[] = ['factor' => "Opened CRM emails {$openCount} times", 'points' => 15];
        }

        // 4. Omnichannel Interactions & Meetings (up to 20 pts)
        $interactions = (new Interaction)->table()
            ->where('workspace_id', $workspaceId)
            ->where('entity_type', 'people')
            ->where('entity_id', $personId)
            ->get();

        if (count($interactions) >= 3) {
            $score += 20;
            $signals[] = ['factor' => 'Active Dialogue: 3+ interactions logged', 'points' => 20];
        } elseif (count($interactions) > 0) {
            $score += 10;
            $signals[] = ['factor' => 'Initial Contact Established', 'points' => 10];
        }

        // 5. Active Pipeline Deal attached (20 pts)
        $opp = (new Opportunity)->table()
            ->where('workspace_id', $workspaceId)
            ->where('person_id', $personId)
            ->whereNull('deleted_at')
            ->first();

        if ($opp) {
            $score += 20;
            $signals[] = ['factor' => "Associated Deal in Pipeline: {$opp['name']} ($" . number_format((float)$opp['amount'], 2) . ")", 'points' => 20];
        }

        $finalScore = min(100, $score);

        $grade = match(true) {
            $finalScore >= 85 => 'A+ (Sales Ready / Hot Lead)',
            $finalScore >= 70 => 'A (High Interest / Warm)',
            $finalScore >= 50 => 'B (Qualified / Evaluating)',
            $finalScore >= 30 => 'C (Nurturing Required)',
            default           => 'D (Cold / Unqualified)',
        };

        $suggestedAction = match(true) {
            $finalScore >= 80 => 'Immediately schedule a proposal review or closing call.',
            $finalScore >= 60 => 'Send a personalized demo booking link or custom case study.',
            $finalScore >= 40 => 'Enroll in an automated email nurture sequence.',
            default           => 'Enrich contact info (phone/title) and verify email deliverability.',
        };

        return [
            'person_id'        => $personId,
            'name'             => trim("{$person['first_name']} {$person['last_name']}"),
            'email'            => $person['email'],
            'score'            => $finalScore,
            'grade'            => $grade,
            'suggested_action' => $suggestedAction,
            'signals'          => $signals,
        ];
    }

    /**
     * AI Prescriptive Next Best Action for Opportunities, Contacts, or Companies.
     */
    public function recommendNextAction(int $workspaceId, string $entityType, int $id): array
    {
        if ($entityType === 'opportunities') {
            $opp = (new Opportunity)->table()
                ->where('id', $id)
                ->where('workspace_id', $workspaceId)
                ->first();

            if (!$opp) return ['error' => "Deal #{$id} not found."];

            $quotes = (new Quote)->table()->where('opportunity_id', $id)->get();
            $tasks = (new Task)->table()
                ->where('workspace_id', $workspaceId)
                ->where('entity_type', 'opportunities')
                ->where('entity_id', $id)
                ->where('status', '!=', 'completed')
                ->get();

            // Check if proposal exists
            if (empty($quotes)) {
                return [
                    'opportunity_id' => $id,
                    'deal_name'      => $opp['name'],
                    'priority'       => 'high',
                    'action'         => 'generate_proposal',
                    'title'          => 'Create and Send CPQ Proposal',
                    'rationale'      => "Deal is in stage '{$opp['stage']}' with value $" . number_format((float)$opp['amount'], 2) . " but has no formal quote generated.",
                ];
            }

            // Check pending quote
            foreach ($quotes as $q) {
                if ($q['status'] === 'sent') {
                    return [
                        'opportunity_id' => $id,
                        'deal_name'      => $opp['name'],
                        'priority'       => 'urgent',
                        'action'         => 'follow_up_quote',
                        'title'          => "Follow up on Proposal #{$q['quote_number']}",
                        'rationale'      => "Proposal was dispatched. Provide a client reminder or offer a quick Q&A call.",
                        'quote_url'      => (function_exists('config') ? config('app.url') : '') . "/quote/{$q['public_token']}",
                    ];
                }
            }

            // Check open tasks
            if (empty($tasks)) {
                return [
                    'opportunity_id' => $id,
                    'deal_name'      => $opp['name'],
                    'priority'       => 'medium',
                    'action'         => 'create_task',
                    'title'          => 'Schedule Discovery / Next Milestone Task',
                    'rationale'      => "No active pending tasks for this deal. Never leave an open deal without a defined next step.",
                ];
            }

            return [
                'opportunity_id' => $id,
                'deal_name'      => $opp['name'],
                'priority'       => 'normal',
                'action'         => 'execute_scheduled_task',
                'title'          => "Complete next task: {$tasks[0]['title']}",
                'rationale'      => "An active task is scheduled for {$tasks[0]['due_date']}.",
            ];
        }

        if ($entityType === 'people') {
            return $this->calculateLeadScore($workspaceId, $id);
        }

        return ['error' => "Unsupported entity_type '{$entityType}'. Use 'opportunities' or 'people'."];
    }

    /**
     * AI Automated Duplicate Detection.
     */
    public function detectDuplicates(int $workspaceId, string $entityType): array
    {
        if ($entityType === 'people') {
            $people = (new Person)->table()
                ->where('workspace_id', $workspaceId)
                ->whereNull('deleted_at')
                ->get();

            $byEmail = [];
            $byPhone = [];

            foreach ($people as $p) {
                $email = strtolower(trim((string)($p['email'] ?? '')));
                $phone = preg_replace('/[^0-9]/', '', (string)($p['phone'] ?? ''));

                if (!empty($email)) {
                    $byEmail[$email][] = [
                        'id'         => $p['id'],
                        'name'       => trim("{$p['first_name']} {$p['last_name']}"),
                        'email'      => $p['email'],
                        'created_at' => $p['created_at'],
                    ];
                }

                if (!empty($phone) && strlen($phone) >= 7) {
                    $byPhone[$phone][] = [
                        'id'         => $p['id'],
                        'name'       => trim("{$p['first_name']} {$p['last_name']}"),
                        'phone'      => $p['phone'],
                        'created_at' => $p['created_at'],
                    ];
                }
            }

            $duplicateGroups = [];
            foreach ($byEmail as $email => $list) {
                if (count($list) > 1) {
                    $duplicateGroups[] = [
                        'match_type' => 'email',
                        'match_val'  => $email,
                        'records'    => $list,
                    ];
                }
            }

            foreach ($byPhone as $phone => $list) {
                if (count($list) > 1) {
                    $duplicateGroups[] = [
                        'match_type' => 'phone',
                        'match_val'  => $phone,
                        'records'    => $list,
                    ];
                }
            }

            return [
                'entity_type'      => 'people',
                'duplicate_groups' => $duplicateGroups,
                'total_duplicates' => count($duplicateGroups),
            ];
        }

        if ($entityType === 'companies') {
            $companies = (new Company)->table()
                ->where('workspace_id', $workspaceId)
                ->whereNull('deleted_at')
                ->get();

            $byName = [];
            $byDomain = [];

            foreach ($companies as $c) {
                $name = strtolower(trim((string)($c['name'] ?? '')));
                $domain = strtolower(trim((string)($c['domain'] ?? '')));

                if (!empty($name)) {
                    $byName[$name][] = [
                        'id'         => $c['id'],
                        'name'       => $c['name'],
                        'domain'     => $c['domain'] ?? null,
                        'created_at' => $c['created_at'],
                    ];
                }

                if (!empty($domain)) {
                    $byDomain[$domain][] = [
                        'id'         => $c['id'],
                        'name'       => $c['name'],
                        'domain'     => $c['domain'],
                        'created_at' => $c['created_at'],
                    ];
                }
            }

            $duplicateGroups = [];
            foreach ($byDomain as $domain => $list) {
                if (count($list) > 1) {
                    $duplicateGroups[] = [
                        'match_type' => 'domain',
                        'match_val'  => $domain,
                        'records'    => $list,
                    ];
                }
            }
            foreach ($byName as $name => $list) {
                if (count($list) > 1) {
                    $duplicateGroups[] = [
                        'match_type' => 'name',
                        'match_val'  => $name,
                        'records'    => $list,
                    ];
                }
            }

            return [
                'entity_type'      => 'companies',
                'duplicate_groups' => $duplicateGroups,
                'total_duplicates' => count($duplicateGroups),
            ];
        }

        return ['error' => "Invalid entity_type '{$entityType}'. Use 'people' or 'companies'."];
    }

    /**
     * AI Safe Merge for Duplicate Records (Migrating all Deals, Tasks, Notes, and Quotes).
     */
    public function mergeRecords(int $workspaceId, ?int $userId, string $entityType, int $primaryId, int $duplicateId): array
    {
        if ($primaryId === $duplicateId) {
            return ['error' => 'Primary ID and Duplicate ID must be distinct.'];
        }

        if ($entityType === 'people') {
            $primary = (new Person)->table()->where('id', $primaryId)->where('workspace_id', $workspaceId)->first();
            $duplicate = (new Person)->table()->where('id', $duplicateId)->where('workspace_id', $workspaceId)->first();

            if (!$primary || !$duplicate) {
                return ['error' => 'Primary or Duplicate contact not found in this workspace.'];
            }

            // 1. Transfer Opportunities
            (new Opportunity)->table()
                ->where('person_id', $duplicateId)
                ->where('workspace_id', $workspaceId)
                ->update(['person_id' => $primaryId]);

            // 2. Transfer Tasks
            (new Task)->table()
                ->where('entity_type', 'people')
                ->where('entity_id', $duplicateId)
                ->where('workspace_id', $workspaceId)
                ->update(['entity_id' => $primaryId]);

            // 3. Transfer Notes
            (new Note)->table()
                ->where('entity_type', 'people')
                ->where('entity_id', $duplicateId)
                ->where('workspace_id', $workspaceId)
                ->update(['entity_id' => $primaryId]);

            // 4. Transfer Interactions
            (new Interaction)->table()
                ->where('entity_type', 'people')
                ->where('entity_id', $duplicateId)
                ->where('workspace_id', $workspaceId)
                ->update(['entity_id' => $primaryId]);

            // 5. Transfer Quotes
            (new Quote)->table()
                ->where('person_id', $duplicateId)
                ->where('workspace_id', $workspaceId)
                ->update(['person_id' => $primaryId]);

            // 6. Soft delete duplicate
            (new Person)->table()
                ->where('id', $duplicateId)
                ->update(['deleted_at' => date('Y-m-d H:i:s')]);

            ActivityLogger::log($workspaceId, $userId, 'merged', 'people', $primaryId, "AI Merged contact #{$duplicateId} into primary #{$primaryId}");

            return [
                'success'      => true,
                'message'      => "Contact #{$duplicateId} successfully merged into #{$primaryId}.",
                'primary_id'   => $primaryId,
                'merged_id'    => $duplicateId,
            ];
        }

        if ($entityType === 'companies') {
            $primary = (new Company)->table()->where('id', $primaryId)->where('workspace_id', $workspaceId)->first();
            $duplicate = (new Company)->table()->where('id', $duplicateId)->where('workspace_id', $workspaceId)->first();

            if (!$primary || !$duplicate) {
                return ['error' => 'Primary or Duplicate company not found in this workspace.'];
            }

            // Transfer Contacts
            (new Person)->table()
                ->where('company_id', $duplicateId)
                ->where('workspace_id', $workspaceId)
                ->update(['company_id' => $primaryId]);

            // Transfer Opportunities
            (new Opportunity)->table()
                ->where('company_id', $duplicateId)
                ->where('workspace_id', $workspaceId)
                ->update(['company_id' => $primaryId]);

            // Transfer Tasks, Notes, Interactions, Quotes
            (new Task)->table()->where('entity_type', 'companies')->where('entity_id', $duplicateId)->update(['entity_id' => $primaryId]);
            (new Note)->table()->where('entity_type', 'companies')->where('entity_id', $duplicateId)->update(['entity_id' => $primaryId]);
            (new Interaction)->table()->where('entity_type', 'companies')->where('entity_id', $duplicateId)->update(['entity_id' => $primaryId]);
            (new Quote)->table()->where('company_id', $duplicateId)->update(['company_id' => $primaryId]);

            // Soft delete duplicate
            (new Company)->table()->where('id', $duplicateId)->update(['deleted_at' => date('Y-m-d H:i:s')]);

            ActivityLogger::log($workspaceId, $userId, 'merged', 'companies', $primaryId, "AI Merged company #{$duplicateId} into primary #{$primaryId}");

            return [
                'success'      => true,
                'message'      => "Company #{$duplicateId} successfully merged into #{$primaryId}.",
                'primary_id'   => $primaryId,
                'merged_id'    => $duplicateId,
            ];
        }

        return ['error' => "Invalid entity_type '{$entityType}'."];
    }

    /**
     * AI Create Quote & CPQ Proposal with Live Acceptance Link.
     */
    public function createAiQuote(int $workspaceId, ?int $userId, array $params): array
    {
        $title = trim((string)($params['title'] ?? 'AI Proposal'));
        $items = (array)($params['items'] ?? []);

        if (empty($items)) {
            return ['error' => 'At least one line item is required to generate a proposal.'];
        }

        $subtotal = 0.0;
        $processedItems = [];

        foreach ($items as $item) {
            $desc = trim((string)($item['description'] ?? $item['item_name'] ?? 'Service Item'));
            $qty = max(0.01, (float)($item['quantity'] ?? 1.0));
            $unitPrice = max(0.0, (float)($item['unit_price'] ?? 0.0));
            $lineTotal = round($qty * $unitPrice, 2);

            $subtotal += $lineTotal;
            $processedItems[] = [
                'description' => $desc,
                'quantity'    => $qty,
                'unit_price'  => $unitPrice,
                'total_price' => $lineTotal,
                'created_at'  => date('Y-m-d H:i:s'),
            ];
        }

        $taxPercent = max(0.0, (float)($params['tax_percent'] ?? 0.0));
        $taxAmount = round($subtotal * ($taxPercent / 100), 2);
        $discountAmount = max(0.0, (float)($params['discount_amount'] ?? 0.0));
        $totalAmount = max(0.0, round($subtotal + $taxAmount - $discountAmount, 2));

        $token = bin2hex(random_bytes(24));
        $quoteNumber = 'Q-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

        $validDays = (int)($params['valid_days'] ?? 30);
        $validUntil = date('Y-m-d', strtotime("+{$validDays} days"));

        $quoteId = (new Quote)->table()->insert([
            'workspace_id'    => $workspaceId,
            'opportunity_id'  => !empty($params['opportunity_id']) ? (int)$params['opportunity_id'] : null,
            'person_id'       => !empty($params['person_id']) ? (int)$params['person_id'] : null,
            'company_id'      => !empty($params['company_id']) ? (int)$params['company_id'] : null,
            'quote_number'    => $quoteNumber,
            'title'           => $title,
            'status'          => 'sent',
            'subtotal'        => $subtotal,
            'tax_percent'     => $taxPercent,
            'tax_amount'      => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount'    => $totalAmount,
            'currency'        => $params['currency'] ?? 'USD',
            'public_token'    => $token,
            'valid_until'     => $validUntil,
            'notes'           => $params['notes'] ?? 'Generated by AI Sales Manager.',
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        foreach ($processedItems as $pItem) {
            $pItem['quote_id'] = (int)$quoteId;
            (new QuoteItem)->table()->insert($pItem);
        }

        $baseUrl = function_exists('config') ? (config('app.url') ?? 'http://127.0.0.1:8000') : 'http://127.0.0.1:8000';
        $acceptanceUrl = rtrim($baseUrl, '/') . "/quote/{$token}";

        ActivityLogger::log($workspaceId, $userId, 'created', 'quotes', (int)$quoteId, "AI Generated Quote {$quoteNumber} for $" . number_format($totalAmount, 2));

        return [
            'quote_id'        => (int)$quoteId,
            'quote_number'    => $quoteNumber,
            'title'           => $title,
            'total_amount'    => $totalAmount,
            'currency'        => $params['currency'] ?? 'USD',
            'acceptance_url'  => $acceptanceUrl,
            'valid_until'     => $validUntil,
        ];
    }

    /**
     * AI Outbound Tracked Email Sender.
     */
    public function sendTrackedEmail(int $workspaceId, ?int $userId, array $params): array
    {
        $to = trim((string)($params['to'] ?? ''));
        $subject = trim((string)($params['subject'] ?? ''));
        $htmlBody = (string)($params['html_body'] ?? '');

        if (!$to || !$subject || !$htmlBody) {
            return ['error' => 'Fields to, subject, and html_body are required.'];
        }

        $entityType = $params['entity_type'] ?? 'people';
        $entityId = !empty($params['entity_id']) ? (int)$params['entity_id'] : null;

        $trackingMeta = [
            'workspace_id'    => $workspaceId,
            'entity_type'     => $entityType,
            'entity_id'       => $entityId,
            'recipient_email' => $to,
            'subject'         => $subject,
        ];

        $mailer = new MailService();
        $result = $mailer->send($to, $subject, $htmlBody, strip_tags($htmlBody), [], $trackingMeta);

        if ($entityId) {
            (new Interaction)->table()->insert([
                'workspace_id'     => $workspaceId,
                'user_id'          => $userId ?? 1,
                'entity_type'      => $entityType,
                'entity_id'        => $entityId,
                'type'             => 'email',
                'title'            => $subject,
                'description'      => $htmlBody,
                'outcome'          => 'sent',
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
        }

        return [
            'success'        => $result['success'] ?? true,
            'recipient'      => $to,
            'tracking_token' => $result['tracking_token'] ?? null,
            'message'        => 'Tracked email successfully dispatched.',
        ];
    }

    /**
     * AI Scheduler & Booking Link Fetcher.
     */
    public function getBookingLink(int $workspaceId, ?int $userId): array
    {
        $query = (new BookingSetting)->table()->where('workspace_id', $workspaceId)->where('is_active', 1);
        if ($userId) {
            $query->where('user_id', $userId);
        }
        $setting = $query->first();

        // Fallback to any active setting in workspace
        if (!$setting) {
            $setting = (new BookingSetting)->table()->where('workspace_id', $workspaceId)->where('is_active', 1)->first();
        }

        if (!$setting) {
            return ['error' => 'No active booking scheduler configured for this workspace. Configure in /settings/booking.'];
        }

        $baseUrl = function_exists('config') ? (config('app.url') ?? 'http://127.0.0.1:8000') : 'http://127.0.0.1:8000';
        $bookingUrl = rtrim($baseUrl, '/') . "/book/{$setting['slug']}";

        return [
            'title'            => $setting['title'],
            'duration_minutes' => (int)$setting['duration_minutes'],
            'booking_url'      => $bookingUrl,
            'working_hours'    => "{$setting['working_hours_start']} - {$setting['working_hours_end']}",
        ];
    }

    /**
     * AI Multi-Parameter Customer Grouping & Targeting Engine.
     * Segments contacts or companies across infinite parameters (Geographic, Firmographic, Activity, Deals)
     * without requiring any separate targeting tables.
     */
    public function segmentCustomers(int $workspaceId, array $criteria): array
    {
        $entityType = strtolower((string)($criteria['entity_type'] ?? 'people'));
        $groupBy = (string)($criteria['group_by'] ?? '');
        $limit = max(1, min((int)($criteria['limit'] ?? 50), 250));

        $filtersApplied = [];

        if ($entityType === 'companies') {
            $query = (new Company)->table()
                ->where('workspace_id', $workspaceId)
                ->whereNull('deleted_at');

            if (!empty($criteria['country'])) {
                $query->where('country', '%' . $criteria['country'] . '%', 'LIKE');
                $filtersApplied['country'] = $criteria['country'];
            }
            if (!empty($criteria['state'])) {
                $query->where('state', '%' . $criteria['state'] . '%', 'LIKE');
                $filtersApplied['state'] = $criteria['state'];
            }
            if (!empty($criteria['city'])) {
                $query->where('city', '%' . $criteria['city'] . '%', 'LIKE');
                $filtersApplied['city'] = $criteria['city'];
            }
            if (!empty($criteria['postal_code'])) {
                $query->where('postal_code', '%' . $criteria['postal_code'] . '%', 'LIKE');
                $filtersApplied['postal_code'] = $criteria['postal_code'];
            }
            if (!empty($criteria['industry'])) {
                $query->where('industry', '%' . $criteria['industry'] . '%', 'LIKE');
                $filtersApplied['industry'] = $criteria['industry'];
            }
            if (!empty($criteria['size'])) {
                $query->where('size', $criteria['size']);
                $filtersApplied['size'] = $criteria['size'];
            }
            if (isset($criteria['min_revenue'])) {
                $query->where('annual_revenue', (float)$criteria['min_revenue'], '>=');
                $filtersApplied['min_revenue'] = (float)$criteria['min_revenue'];
            }
            if (isset($criteria['max_revenue'])) {
                $query->where('annual_revenue', (float)$criteria['max_revenue'], '<=');
                $filtersApplied['max_revenue'] = (float)$criteria['max_revenue'];
            }

            $rawRows = $query->orderBy('name', 'ASC')->get();

            // Additional dynamic activity filters
            if (isset($criteria['has_deals']) || isset($criteria['has_open_tasks'])) {
                $filteredRows = [];
                foreach ($rawRows as $row) {
                    $cid = (int)$row['id'];
                    if (isset($criteria['has_deals'])) {
                        $dealCount = (new Opportunity)->table()->where('workspace_id', $workspaceId)->where('company_id', $cid)->whereNull('deleted_at')->count();
                        if ($criteria['has_deals'] && $dealCount === 0) continue;
                        if (!$criteria['has_deals'] && $dealCount > 0) continue;
                    }
                    if (isset($criteria['has_open_tasks'])) {
                        $taskCount = (new Task)->table()->where('workspace_id', $workspaceId)->where('company_id', $cid)->where('status', 'completed', '!=')->count();
                        if ($criteria['has_open_tasks'] && $taskCount === 0) continue;
                        if (!$criteria['has_open_tasks'] && $taskCount > 0) continue;
                    }
                    $filteredRows[] = $row;
                }
                $rawRows = $filteredRows;
                if (isset($criteria['has_deals'])) $filtersApplied['has_deals'] = $criteria['has_deals'];
                if (isset($criteria['has_open_tasks'])) $filtersApplied['has_open_tasks'] = $criteria['has_open_tasks'];
            }

            // Tag filtering
            if (!empty($criteria['tag'])) {
                $filteredRows = [];
                $tagService = new TagService();
                foreach ($rawRows as $row) {
                    $tags = $tagService->getEntityTags('companies', (int)$row['id']);
                    $hasTag = false;
                    foreach ($tags as $t) {
                        if (stripos($t['name'], $criteria['tag']) !== false) {
                            $hasTag = true; break;
                        }
                    }
                    if ($hasTag) $filteredRows[] = $row;
                }
                $rawRows = $filteredRows;
                $filtersApplied['tag'] = $criteria['tag'];
            }

            // Grouping logic
            $groupedSummary = [];
            if (!empty($groupBy)) {
                if ($groupBy === 'tags') {
                    $tagService = new TagService();
                    foreach ($rawRows as $row) {
                        $tags = $tagService->getEntityTags('companies', (int)$row['id']);
                        if (empty($tags)) {
                            $groupedSummary['🏷️ Untagged'] = ($groupedSummary['🏷️ Untagged'] ?? 0) + 1;
                        } else {
                            foreach ($tags as $t) {
                                $k = '🏷️ ' . $t['name'];
                                $groupedSummary[$k] = ($groupedSummary[$k] ?? 0) + 1;
                            }
                        }
                    }
                } else {
                    foreach ($rawRows as $row) {
                        $gVal = trim((string)($row[$groupBy] ?? '')) ?: 'Unassigned / Other';
                        $groupedSummary[$gVal] = ($groupedSummary[$gVal] ?? 0) + 1;
                    }
                }
                arsort($groupedSummary);
            }

            $sample = array_slice($rawRows, 0, $limit);
            $formattedSample = array_map(function($c) {
                $addressParts = array_filter([$c['address'] ?? '', $c['city'] ?? '', $c['state'] ?? '', $c['postal_code'] ?? '', $c['country'] ?? '']);
                return [
                    'id'             => $c['id'],
                    'name'           => $c['name'],
                    'industry'       => $c['industry'] ?? 'N/A',
                    'annual_revenue' => $c['annual_revenue'] ? '$' . number_format((float)$c['annual_revenue']) : 'N/A',
                    'formatted_addr' => !empty($addressParts) ? implode(', ', $addressParts) : 'None',
                    'phone'          => $c['phone'] ?? 'N/A',
                    'website'        => $c['website'] ?? 'N/A',
                ];
            }, $sample);

            return [
                'entity_type'      => 'companies',
                'total_matching'   => count($rawRows),
                'filters_applied'  => $filtersApplied,
                'group_by'         => $groupBy ?: 'None',
                'grouped_summary'  => $groupedSummary,
                'sample_count'     => count($formattedSample),
                'records'          => $formattedSample,
            ];
        }

        // Default: People / Contacts segmentation
        $query = (new Person)->table()
            ->where('workspace_id', $workspaceId)
            ->whereNull('deleted_at');

        if (!empty($criteria['country'])) {
            $query->where('country', '%' . $criteria['country'] . '%', 'LIKE');
            $filtersApplied['country'] = $criteria['country'];
        }
        if (!empty($criteria['state'])) {
            $query->where('state', '%' . $criteria['state'] . '%', 'LIKE');
            $filtersApplied['state'] = $criteria['state'];
        }
        if (!empty($criteria['city'])) {
            $query->where('city', '%' . $criteria['city'] . '%', 'LIKE');
            $filtersApplied['city'] = $criteria['city'];
        }
        if (!empty($criteria['postal_code'])) {
            $query->where('postal_code', '%' . $criteria['postal_code'] . '%', 'LIKE');
            $filtersApplied['postal_code'] = $criteria['postal_code'];
        }
        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
            $filtersApplied['status'] = $criteria['status'];
        }
        if (!empty($criteria['job_title'])) {
            $query->where('job_title', '%' . $criteria['job_title'] . '%', 'LIKE');
            $filtersApplied['job_title'] = $criteria['job_title'];
        }

        $rawRows = $query->orderBy('first_name', 'ASC')->get();

        // Join company details for industry or company name filtering
        if (!empty($rawRows)) {
            $companyIds = array_filter(array_unique(array_column($rawRows, 'company_id')));
            $companiesMap = [];
            if (!empty($companyIds)) {
                $companies = (new Company)->table()->where('workspace_id', $workspaceId)->get();
                foreach ($companies as $comp) {
                    $companiesMap[$comp['id']] = $comp;
                }
            }

            $filteredRows = [];
            foreach ($rawRows as $p) {
                $cid = (int)($p['company_id'] ?? 0);
                $comp = $companiesMap[$cid] ?? null;
                $p['company_name'] = $comp['name'] ?? null;
                $p['company_industry'] = $comp['industry'] ?? null;

                if (!empty($criteria['industry'])) {
                    if (!$comp || stripos((string)$comp['industry'], (string)$criteria['industry']) === false) {
                        continue;
                    }
                }

                if (isset($criteria['has_deals'])) {
                    $dealCount = (new Opportunity)->table()->where('workspace_id', $workspaceId)->where('person_id', (int)$p['id'])->whereNull('deleted_at')->count();
                    if ($criteria['has_deals'] && $dealCount === 0) continue;
                    if (!$criteria['has_deals'] && $dealCount > 0) continue;
                }

                if (isset($criteria['has_open_tasks'])) {
                    $taskCount = (new Task)->table()->where('workspace_id', $workspaceId)->where('person_id', (int)$p['id'])->where('status', 'completed', '!=')->count();
                    if ($criteria['has_open_tasks'] && $taskCount === 0) continue;
                    if (!$criteria['has_open_tasks'] && $taskCount > 0) continue;
                }

                $filteredRows[] = $p;
            }
            $rawRows = $filteredRows;
            if (!empty($criteria['industry'])) $filtersApplied['industry'] = $criteria['industry'];
            if (isset($criteria['has_deals'])) $filtersApplied['has_deals'] = $criteria['has_deals'];
            if (isset($criteria['has_open_tasks'])) $filtersApplied['has_open_tasks'] = $criteria['has_open_tasks'];
        }

        // Tag filtering for contacts
        if (!empty($criteria['tag'])) {
            $filteredRows = [];
            $tagService = new TagService();
            foreach ($rawRows as $p) {
                $tags = $tagService->getEntityTags('people', (int)$p['id']);
                $hasTag = false;
                foreach ($tags as $t) {
                    if (stripos($t['name'], $criteria['tag']) !== false) {
                        $hasTag = true; break;
                    }
                }
                if ($hasTag) $filteredRows[] = $p;
            }
            $rawRows = $filteredRows;
            $filtersApplied['tag'] = $criteria['tag'];
        }

        // Grouping logic
        $groupedSummary = [];
        if (!empty($groupBy)) {
            if ($groupBy === 'tags') {
                $tagService = new TagService();
                foreach ($rawRows as $p) {
                    $tags = $tagService->getEntityTags('people', (int)$p['id']);
                    if (empty($tags)) {
                        $groupedSummary['🏷️ Untagged'] = ($groupedSummary['🏷️ Untagged'] ?? 0) + 1;
                    } else {
                        foreach ($tags as $t) {
                            $k = '🏷️ ' . $t['name'];
                            $groupedSummary[$k] = ($groupedSummary[$k] ?? 0) + 1;
                        }
                    }
                }
            } else {
                foreach ($rawRows as $row) {
                    $val = $groupBy === 'company_name' ? ($row['company_name'] ?? '') : ($row[$groupBy] ?? '');
                    $gVal = trim((string)$val) ?: 'Unassigned / Other';
                    $groupedSummary[$gVal] = ($groupedSummary[$gVal] ?? 0) + 1;
                }
            }
            arsort($groupedSummary);
        }

        $sample = array_slice($rawRows, 0, $limit);
        $formattedSample = array_map(function($p) {
            $addressParts = array_filter([$p['address'] ?? '', $p['city'] ?? '', $p['state'] ?? '', $p['postal_code'] ?? '', $p['country'] ?? '']);
            return [
                'id'             => $p['id'],
                'name'           => trim("{$p['first_name']} " . ($p['last_name'] ?? '')),
                'job_title'      => $p['job_title'] ?? 'N/A',
                'company'        => $p['company_name'] ?? 'N/A',
                'email'          => $p['email'] ?? 'N/A',
                'phone'          => $p['phone'] ?? 'N/A',
                'status'         => $p['status'] ?? 'lead',
                'formatted_addr' => !empty($addressParts) ? implode(', ', $addressParts) : 'None',
            ];
        }, $sample);

        return [
            'entity_type'     => 'people',
            'total_matching'  => count($rawRows),
            'filters_applied' => $filtersApplied,
            'group_by'        => $groupBy ?: 'None',
            'grouped_summary' => $groupedSummary,
            'sample_count'    => count($formattedSample),
            'records'         => $formattedSample,
        ];
    }

    /**
     * AI Bulk Email Dispatcher for Target Groups / Segments.
     */
    public function bulkEmailSegment(int $workspaceId, ?int $userId, array $params): array
    {
        $criteria = (array)($params['segment_criteria'] ?? []);
        $subject  = trim((string)($params['subject'] ?? ''));
        $body     = trim((string)($params['html_body'] ?? ''));

        if (empty($subject) || empty($body)) {
            return ['error' => 'Subject and html_body are required.'];
        }

        $criteria['entity_type'] = 'people';
        $criteria['limit'] = max(1, min((int)($params['max_recipients'] ?? 100), 500));

        $segment = $this->segmentCustomers($workspaceId, $criteria);
        $records = $segment['records'] ?? [];

        if (empty($records)) {
            return [
                'success' => false,
                'message' => 'No contacts matched the specified segment criteria.',
                'segment' => $segment,
            ];
        }

        $personIds = array_column($records, 'id');
        $result = BulkActionService::bulkEmail($personIds, $subject, $body, $workspaceId, $userId ?? 1);

        return [
            'success'        => $result['sent'] > 0,
            'sent_count'     => $result['sent'],
            'failed_count'   => $result['failed'],
            'total_targeted' => count($personIds),
            'criteria'       => $criteria,
            'message'        => "Successfully dispatched tracked bulk emails to {$result['sent']} contacts in segment!",
        ];
    }
}
