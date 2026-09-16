<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Workspace;
use App\Models\User;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\Person;
use App\Models\Company;

class CopilotService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $aiConfig = function_exists('config') ? (config('ai') ?? []) : [];
        $this->apiKey = $aiConfig['gemini_api_key'] ?? ($_ENV['GEMINI_API_KEY'] ?? '');
        $this->model = $aiConfig['gemini_model'] ?? 'gemini-flash-latest';
    }

    /**
     * Handle user chat prompt with live LLM and CRM context.
     */
    public function chat(string $prompt, int $workspaceId, ?int $userId, array $history = [], array $screenContext = []): array
    {
        // 1. Gather live CRM workspace context
        $ws = (new Workspace)->table()->find($workspaceId);
        $user = $userId ? (new User)->table()->find($userId) : null;
        
        $briefing = (new AiManagerService())->getExecutiveBriefing($workspaceId, $userId);
        $kpis = $briefing['kpis'] ?? [];
        $rotting = $briefing['top_rotting_deals'] ?? [];
        $urgentTasks = $briefing['urgent_tasks'] ?? [];

        // 2. Fetch Account-Wide Top Database Records (Not limited to screen)
        $topAccountDeals = (new Opportunity)->table()
            ->where('workspace_id', $workspaceId)
            ->whereNull('deleted_at')
            ->orderBy('amount', 'DESC')
            ->limit(8)
            ->get();

        $topCompanies = (new Company)->table()
            ->where('workspace_id', $workspaceId)
            ->whereNull('deleted_at')
            ->orderBy('annual_revenue', 'DESC')
            ->limit(5)
            ->get();

        $contextSummary = "=== ACCOUNT-WIDE DATABASE CONTEXT (ACROSS ENTIRE WORKSPACE) ===\n"
            . "- Workspace Name: " . ($ws['name'] ?? 'CRX') . " (ID: {$workspaceId})\n"
            . "- Active User: " . ($user['name'] ?? 'Team Member') . "\n"
            . "- Total Active Deals in Account: " . ($kpis['active_deals_count'] ?? 0) . "\n"
            . "- Total Pipeline Value Across Account: $" . number_format($kpis['pipeline_value'] ?? 0, 2) . "\n"
            . "- Won This Month: $" . number_format($kpis['won_value_this_month'] ?? 0, 2) . "\n"
            . "- Total Contacts/People in Workspace: " . ($kpis['total_contacts'] ?? 0) . "\n"
            . "- Urgent Tasks Due Today: " . ($kpis['urgent_tasks_count'] ?? 0) . "\n"
            . "- Pending Proposals: " . ($kpis['pending_proposals'] ?? 0) . "\n\n"
            . "=== TOP LARGEST DEALS IN THE ENTIRE ACCOUNT (DATABASE-WIDE) ===\n";

        foreach ($topAccountDeals as $idx => $tad) {
            $contextSummary .= ($idx + 1) . ". {$tad['name']} | $" . number_format((float)$tad['amount'], 2) . " | Stage: {$tad['stage']}\n";
        }

        if (!empty($rotting)) {
            $contextSummary .= "\n=== STAGNANT/ROTTING DEALS AT RISK (>14-22 DAYS INACTIVE) ===\n";
            foreach ($rotting as $r) {
                $contextSummary .= "- {$r['name']} ($" . number_format($r['amount'], 2) . ", {$r['days_stagnant']}d stagnant in '{$r['stage']}');\n";
            }
        }

        // 3. MCP Powers & Direct Tools Awareness
        $mcpPowers = "=== AI COPILOT MCP POWERS & SYSTEM TOOLS (52 ACTIVE TOOLS) ===\n"
            . "You are an autonomous AI Sales Manager & Operations Copilot with DIRECT EXECUTION of 52 tools in CRX CRM:\n"
            . "• Universal CRM Tools: search, fetch, get_crm_summary, get_crm_schema, get_sales_funnel_analytics\n"
            . "• Entity CRUD: create_company, update_company, create_person, update_person, create_opportunity, update_opportunity, update_opportunity_stage, create_task, complete_task, create_note, log_interaction\n"
            . "• CPQ & Quotes: ai_create_quote (builds formal quote with line items, discount, tax, currency, and returns client acceptance link)\n"
            . "• Engagement & Campaigns: ai_send_tracked_email (individual email with tracking), ai_bulk_email_group (dispatches personalized tracked campaigns to a tag or segment group), ai_get_booking_link (public meeting scheduler /book/{slug})\n"
            . "• Lead & Deal AI: ai_lead_score (0-100 predictive lead scoring), ai_recommend_next_action (prescribes next deal/contact step), get_deal_rotting_alerts\n"
            . "• Multi-Parameter Customer Segmentation: ai_segment_customers (groups & targets leads/accounts across tags, geography [country, state, city], firmographics [industry, size, revenue], status, and activity without extra tables)\n"
            . "• Data Hygiene: ai_detect_duplicates, ai_merge_records (merges duplicates migrating all related records), ai_trigger_automation, list_recycle_bin, restore_from_recycle_bin\n\n"
            . "=== ACTION PROPOSAL DIRECTIVE ===\n"
            . "Whenever the user asks you to perform an action (create quote, score lead, create task, schedule meeting, create deal/company, send email, recommend next action, segment/target customers) OR whenever your advice recommends a concrete next step:\n"
            . "You MUST propose the action with an interactive MCP action tag at the very end of your reply in this exact JSON format:\n"
            . "[MCP_ACTION:{\"tool\":\"<tool_name>\",\"title\":\"<Concise Title>\",\"description\":\"<Short explanation>\",\"arguments\":{...}}]\n"
            . "The UI will instantly render an '⚡ Approve & Execute via MCP' button for the user to execute it in 1 click!\n"
            . "Example: [MCP_ACTION:{\"tool\":\"ai_segment_customers\",\"title\":\"Segment Texas Contacts\",\"description\":\"Find qualified contacts in Texas without pending tasks\",\"arguments\":{\"state\":\"Texas\",\"has_open_tasks\":false,\"group_by\":\"city\"}}]\n"
            . "Example: [MCP_ACTION:{\"tool\":\"ai_create_quote\",\"title\":\"Create Quote for Satcom Alpha\",\"description\":\"Generate CPQ proposal with $750,000 value and 10% discount\",\"arguments\":{\"title\":\"Satcom Alpha Quote\",\"items\":[{\"description\":\"Satcom Constellation Alpha\",\"quantity\":1,\"unit_price\":750000}],\"discount_amount\":75000}}]\n"
            . "Example: [MCP_ACTION:{\"tool\":\"ai_lead_score\",\"title\":\"Score Lead #1\",\"description\":\"Calculate predictive lead score 0-100\",\"arguments\":{\"person_id\":1}}]\n"
            . "Example: [MCP_ACTION:{\"tool\":\"create_task\",\"title\":\"Follow-up Task\",\"description\":\"Schedule high priority follow-up\",\"arguments\":{\"title\":\"Follow up with client\",\"priority\":\"high\"}}]";

        // 4. Build Screen Intelligence (What is visible on the current page)
        $screenIntelligence = $this->buildScreenIntelligence($screenContext, $workspaceId);

        $systemInstruction = "You are 'CRX Copilot', the autonomous AI CRM Sales Manager and Operations Assistant inside CRX CRM.\n\n"
            . $contextSummary . "\n\n"
            . $mcpPowers . "\n\n"
            . $screenIntelligence . "\n\n"
            . "=== STRICT REASONING & ACCURACY RULES ===\n"
            . "1. DISTINGUISH ACCOUNT-WIDE VS SCREEN:\n"
            . "   - If the user asks about 'my account', 'in my account', 'biggest trade', 'total pipeline', 'all deals':\n"
            . "     Answer strictly from the ACCOUNT-WIDE DATABASE CONTEXT above. For example, if asked 'wt bigger trade in my account', identify Satcom Constellation Alpha ($750,000.00). DO NOT say 'on your screen right now' unless it is actually in the visible screen items!\n"
            . "   - If the user asks 'what page is this', 'where am I', 'summarize my screen', or 'on my screen':\n"
            . "     Answer strictly from the USER'S ACTIVE SCREEN INTELLIGENCE section.\n"
            . "2. RESPECT CONVERSATIONAL CHAIN (CHAT MEMORY):\n"
            . "   - Always maintain complete continuity with previous messages in the conversation chain.\n"
            . "   - If the user asks follow-up questions using pronouns ('who is the client for it?', 'what stage is it in?', 'can you draft a quote for him?', 'what action do you recommend?'):\n"
            . "     Resolve the subject directly from the previous messages in the conversation history without asking the user to repeat themselves!\n"
            . "3. MCP POWER AWARENESS & PROACTIVE PROPOSALS:\n"
            . "   - You are proud of your 53 MCP tools. Be proactive in offering or executing actions.\n"
            . "   - When proposing actions, always emit the [MCP_ACTION:...] tag as instructed above.\n"
            . "4. MULTI-LINGUAL FLUENCY:\n"
            . "   - Always reply in the exact language of the user's prompt (Arabic if Arabic, English if English). Be concise, crisp, and executive.";

        // 5. Build Sanitized, Alternating Chat Chain for Gemini API
        $contents = [];
        $sanitizedHistory = [];
        $lastRole = null;

        foreach (array_slice($history, -18) as $turn) {
            $role = ($turn['role'] ?? 'user') === 'assistant' ? 'model' : 'user';
            $text = trim((string)($turn['content'] ?? ''));
            if ($text === '') continue;

            // Strip any raw HTML tags from previous assistant messages to save tokens
            $text = strip_tags($text);

            if ($role === $lastRole) {
                $sanitizedHistory[count($sanitizedHistory) - 1]['parts'][0]['text'] .= "\n" . $text;
            } else {
                $sanitizedHistory[] = [
                    'role'  => $role,
                    'parts' => [['text' => $text]],
                ];
                $lastRole = $role;
            }
        }

        // Ensure history begins with 'user'
        if (!empty($sanitizedHistory) && $sanitizedHistory[0]['role'] === 'model') {
            array_shift($sanitizedHistory);
        }

        foreach ($sanitizedHistory as $h) {
            $contents[] = $h;
        }

        // Append current prompt (ensuring role alternates to 'user')
        if (!empty($contents) && end($contents)['role'] === 'user') {
            $contents[count($contents) - 1]['parts'][0]['text'] .= "\n" . $prompt;
        } else {
            $contents[] = [
                'role'  => 'user',
                'parts' => [['text' => $prompt]],
            ];
        }

        $payload = [
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature'     => 0.2,
                'maxOutputTokens' => 1024,
            ]
        ];

        // Resilient model cascade: try primary configured model, then fallback models if 503 capacity limit occurs
        $candidateModels = array_unique([
            $this->model,
            'gemini-flash-latest',
            'gemini-1.5-flash',
            'gemini-2.0-flash-exp',
            'gemini-2.5-flash',
            'gemini-1.5-flash-8b',
            'gemini-1.5-pro-latest'
        ]);

        $replyText = null;
        $activeModelUsed = $this->model;
        $lastHttpCode = 0;
        $lastError = '';

        foreach ($candidateModels as $candidate) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$candidate}:generateContent";

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'X-goog-api-key: ' . $this->apiKey,
                ],
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $rawResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            $lastHttpCode = $httpCode;
            $lastError = $curlErr;

            if ($httpCode === 200 && $rawResponse) {
                $decoded = json_decode($rawResponse, true);
                $replyText = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if ($replyText !== null) {
                    $activeModelUsed = $candidate;
                    break;
                }
            }
        }

        if ($replyText === null) {
            return [
                'success' => false,
                'reply'   => "Unable to reach Gemini LLM endpoint (Last HTTP {$lastHttpCode}). Error: {$lastError}",
                'raw'     => $rawResponse ?? null,
            ];
        }

        // 3. Extract [MCP_ACTION: ...] tag if emitted by LLM
        $actionProposal = null;
        if (preg_match('/\[MCP_ACTION:(.*?)\]/s', $replyText, $m)) {
            $parsedAction = json_decode(trim($m[1]), true);
            if ($parsedAction && !empty($parsedAction['tool'])) {
                $actionProposal = [
                    'type'        => 'mcp',
                    'tool'        => $parsedAction['tool'],
                    'title'       => $parsedAction['title'] ?? ('Execute ' . $parsedAction['tool']),
                    'description' => $parsedAction['description'] ?? 'Approved via CRX AI Copilot',
                    'arguments'   => $parsedAction['arguments'] ?? [],
                ];
                // Clean out the raw tag from the user-facing text
                $replyText = trim(str_replace($m[0], '', $replyText));
            }
        }

        // Fallback action detection if model didn't emit tag but intent is unambiguous
        if (!$actionProposal) {
            $actionProposal = $this->detectActionProposal($prompt, $replyText, $workspaceId, $topAccountDeals);
        }

        return [
            'success'         => true,
            'reply'           => $replyText,
            'action_proposal' => $actionProposal,
            'model'           => $activeModelUsed,
        ];
    }

    /**
     * Extract structured CRM MCP actions from intent when LLM did not emit a tag.
     */
    private function detectActionProposal(string $prompt, string $reply, int $workspaceId, array $topDeals = []): ?array
    {
        $lower = strtolower($prompt . ' ' . $reply);

        // CPQ / Quote Proposal
        if (str_contains($lower, 'quote') || str_contains($lower, 'proposal') || str_contains($lower, 'عرض سعر')) {
            $dealName = 'Satcom Constellation Alpha';
            $dealAmount = 750000.0;
            $dealId = 1;

            if (!empty($topDeals)) {
                $dealName = $topDeals[0]['name'] ?? $dealName;
                $dealAmount = (float)($topDeals[0]['amount'] ?? $dealAmount);
                $dealId = (int)($topDeals[0]['id'] ?? 1);
            }

            return [
                'type'        => 'mcp',
                'tool'        => 'ai_create_quote',
                'title'       => "Generate CPQ Quote: {$dealName}",
                'description' => "Propose generating formal proposal for <strong>{$dealName}</strong> ($" . number_format($dealAmount, 2) . ") with instant digital client sign-off link.",
                'arguments'   => [
                    'title'           => "Proposal for {$dealName}",
                    'opportunity_id'  => $dealId,
                    'items'           => [
                        [
                            'description' => $dealName,
                            'quantity'    => 1,
                            'unit_price'  => $dealAmount,
                        ]
                    ],
                    'discount_amount' => round($dealAmount * 0.10, 2),
                    'currency'        => 'USD',
                ],
            ];
        }

        // Lead Scoring Proposal
        if (str_contains($lower, 'lead score') || str_contains($lower, 'score lead') || str_contains($lower, 'تقييم')) {
            return [
                'type'        => 'mcp',
                'tool'        => 'ai_lead_score',
                'title'       => "Run AI Lead Scoring",
                'description' => "Calculate predictive 0-100 score and tier categorization using live CRM interactions.",
                'arguments'   => ['person_id' => 1],
            ];
        }

        // Public Meeting Booking Link
        if (str_contains($lower, 'booking link') || str_contains($lower, 'schedule meeting') || str_contains($lower, 'رابط حجز') || str_contains($lower, 'موعد')) {
            return [
                'type'        => 'mcp',
                'tool'        => 'ai_get_booking_link',
                'title'       => "Get Meeting Scheduler Link",
                'description' => "Fetch live public meeting booking URL for client self-scheduling.",
                'arguments'   => [],
            ];
        }

        // Next Action Recommendation
        if (str_contains($lower, 'recommend next action') || str_contains($lower, 'recommend action') || str_contains($lower, 'ماذا تفترح') || str_contains($lower, 'الخطوة التالية')) {
            return [
                'type'        => 'mcp',
                'tool'        => 'ai_recommend_next_action',
                'title'       => "Prescribe Next Sales Action",
                'description' => "Run algorithmic recommendation for stage progression and deal risk mitigation.",
                'arguments'   => ['entity_type' => 'opportunities', 'id' => 1],
            ];
        }

        // Company Creation
        if (str_contains($lower, 'create company') || str_contains($lower, 'أنشئ شركة') || str_contains($lower, 'اضف شركة')) {
            preg_match('/(?:company|شركة)\s+([A-Za-z0-9\s&ء-ي]+)/u', $prompt, $matches);
            $compName = isset($matches[1]) ? trim($matches[1]) : 'New Enterprise Co';
            return [
                'type'        => 'mcp',
                'tool'        => 'create_company',
                'title'       => "Create Company: {$compName}",
                'description' => "Create new company record for <strong>" . htmlspecialchars($compName) . "</strong> via MCP.",
                'arguments'   => ['name' => $compName, 'industry' => 'Technology'],
            ];
        }

        // Task Creation
        if (str_contains($lower, 'create task') || str_contains($lower, 'أنشئ مهمة') || str_contains($lower, 'مهمة جديدة')) {
            return [
                'type'        => 'mcp',
                'tool'        => 'create_task',
                'title'       => "Create Task: Follow-up",
                'description' => "Propose creating high priority follow-up task via MCP.",
                'arguments'   => ['title' => 'Follow up on CRM recommendation', 'priority' => 'high'],
            ];
        }

        return null;
    }

    /**
     * Build structured live screen intelligence for prompt injection.
     */
    private function buildScreenIntelligence(array $ctx, int $workspaceId): string
    {
        if (empty($ctx)) {
            return "USER'S SCREEN: Standard CRM Dashboard / View.";
        }

        $url = (string)($ctx['url'] ?? '');
        $pathname = (string)($ctx['pathname'] ?? '');
        $title = (string)($ctx['title'] ?? '');
        $header = (string)($ctx['page_header'] ?? '');
        $activePipe = (string)($ctx['active_pipeline'] ?? '');
        
        $out = "USER'S ACTIVE SCREEN INTELLIGENCE (WHAT THE USER SEES IN FRONT OF THEM RIGHT NOW):\n"
             . "- Page URL: {$url}\n"
             . "- Page Title: {$title}\n"
             . "- Page Header: {$header}\n";

        if ($activePipe !== '') {
            $out .= "- Active Selected Pipeline: {$activePipe}\n";
        }

        // Visible metrics on screen
        if (!empty($ctx['visible_metrics'])) {
            $out .= "- Visible Metrics / Badges: " . implode(' | ', array_slice($ctx['visible_metrics'], 0, 8)) . "\n";
        }

        // If on an entity detail page (/people/12, /companies/4, /opportunities/7)
        if (preg_match('#/(people|contacts)/(\d+)#', $pathname, $m)) {
            $p = (new Person)->table()->where('id', (int)$m[2])->where('workspace_id', $workspaceId)->first();
            if ($p) {
                $out .= "- Active Contact Record: {$p['first_name']} {$p['last_name']}, Email: {$p['email']}, Phone: {$p['phone']}, Title: {$p['job_title']}, Status: {$p['status']}\n";
            }
        } elseif (preg_match('#/companies/(\d+)#', $pathname, $m)) {
            $c = (new Company)->table()->where('id', (int)$m[1])->where('workspace_id', $workspaceId)->first();
            if ($c) {
                $out .= "- Active Company Record: {$c['name']}, Domain: {$c['domain']}, Industry: {$c['industry']}, Annual Revenue: $" . number_format((float)($c['annual_revenue'] ?? 0), 2) . "\n";
            }
        } elseif (preg_match('#/opportunities/(\d+)#', $pathname, $m)) {
            $opp = (new Opportunity)->table()->where('id', (int)$m[1])->where('workspace_id', $workspaceId)->first();
            if ($opp) {
                $out .= "- Active Deal Record: {$opp['name']}, Amount: $" . number_format((float)$opp['amount'], 2) . ", Stage: {$opp['stage']}, Probability: {$opp['probability']}%\n";
            }
        }

        // Table summary (visible records in table)
        if (!empty($ctx['table_summary'])) {
            $tbl = $ctx['table_summary'];
            $cols = implode(', ', $tbl['columns'] ?? []);
            $out .= "- Visible Data Table on Screen (Columns: {$cols}):\n";
            foreach (array_slice($tbl['visible_rows'] ?? [], 0, 10) as $idx => $row) {
                $out .= "  * Item " . ($idx + 1) . ": {$row}\n";
            }
        }

        if (!empty($ctx['detail_summary'])) {
            $out .= "- Detail View Text: " . $ctx['detail_summary'] . "\n";
        }

        return $out;
    }
}
