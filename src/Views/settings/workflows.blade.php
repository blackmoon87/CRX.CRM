@extends('layouts.main')

@section('title', 'Workflow Automations & Visual Rules Engine')

@section('content')
<style>
/* ─────────────────────────────────────────────────────────────────────────────
   SPARTAN VISUAL WORKFLOW AUTOMATIONS STUDIO (FULL MULTI-ACTION & MULTI-TRIGGER)
───────────────────────────────────────────────────────────────────────────── */

.workflow-header-container {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.75rem;
    gap: 1rem;
    flex-wrap: wrap;
}

.workflow-title-wrap h1 {
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--text-main);
    letter-spacing: -0.025em;
    margin: 0 0 0.35rem 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.workflow-title-wrap p {
    font-size: 0.95rem;
    color: var(--text-dim);
    margin: 0;
    max-width: 760px;
}

/* 1-Click Recipe Cards Carousel */
.recipes-section {
    margin-bottom: 2rem;
}
.recipes-label {
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-dim);
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.recipes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 1rem;
}
.recipe-card {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1rem 1.15rem;
    box-shadow: var(--shadow-xs);
    cursor: pointer;
    transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.recipe-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    border-color: #cbd5e1;
}
.recipe-top {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}
.recipe-icon {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
    border: 1.5px solid rgba(0,0,0,0.08);
}
.recipe-title {
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--text-main);
    line-height: 1.25;
}
.recipe-desc {
    font-size: 0.8rem;
    color: var(--text-dim);
    line-height: 1.4;
    margin-bottom: 0.75rem;
}
.recipe-btn {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--primary);
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: #F1F5F9;
    padding: 0.35rem 0.65rem;
    border-radius: 6px;
    border: 1px solid #CBD5E1;
    align-self: flex-start;
}
.recipe-card:hover .recipe-btn {
    background: var(--primary);
    color: #FFFFFF;
    border-color: var(--primary);
}

/* Two-column Studio Layout */
.workflow-studio-layout {
    display: grid;
    grid-template-columns: 420px 1fr;
    gap: 1.75rem;
    align-items: start;
}
@media (max-width: 1080px) {
    .workflow-studio-layout {
        grid-template-columns: 1fr;
    }
}

/* Left Studio Configurator */
.studio-card {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
    padding: 1.5rem;
}
.studio-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 1rem;
    margin-bottom: 1.25rem;
    border-bottom: 1px solid var(--border);
}
.studio-card-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--text-main);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.step-badge {
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.2rem 0.5rem;
    border-radius: 5px;
    background: #E2E8F0;
    color: #334155;
}

.studio-group {
    margin-bottom: 1.2rem;
}
.studio-label {
    display: block;
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--text-main);
    margin-bottom: 0.45rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.studio-input, .studio-select, .studio-textarea {
    width: 100%;
    padding: 0.65rem 0.85rem;
    font-size: 0.9rem;
    font-family: inherit;
    font-weight: 500;
    color: var(--text-main);
    background: #F8FAFC;
    border: 1px solid var(--border);
    border-radius: 8px;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.studio-textarea {
    resize: vertical;
    min-height: 70px;
}
.studio-input:focus, .studio-select:focus, .studio-textarea:focus {
    border-color: var(--primary);
    background: #FFFFFF;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
}

/* Action Specific Panels */
.action-param-panel {
    background: #F8FAFC;
    border: 1.5px solid #E2E8F0;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1.2rem;
}

.deploy-btn {
    width: 100%;
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    color: #FFFFFF;
    border: 1px solid #4338ca;
    border-radius: 8px;
    padding: 0.75rem 1.25rem;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
    transition: all 0.15s ease;
}
.deploy-btn:hover {
    background: linear-gradient(135deg, #4338ca, #4f46e5);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
}
.deploy-btn:active {
    transform: translateY(0);
}

/* Right Main Automation Canvas */
.canvas-card {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
    padding: 1.5rem;
}
.canvas-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border);
}
.canvas-title {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--text-main);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}
.rules-count-pill {
    background: var(--ink);
    color: #FFFFFF;
    font-size: 0.75rem;
    font-weight: 800;
    padding: 0.2rem 0.6rem;
    border-radius: 20px;
}

/* Visual Automation Rule Flow Card */
.workflow-flow-card {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 1.25rem;
    margin-bottom: 1.25rem;
    box-shadow: var(--shadow-xs);
    transition: all 0.15s ease;
    position: relative;
}
.workflow-flow-card:hover {
    border-color: #cbd5e1;
    box-shadow: var(--shadow-sm);
}
.workflow-flow-card.paused {
    opacity: 0.72;
    background: #F1F5F9;
}

/* Top bar inside flow card */
.flow-card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.15rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #E2E8F0;
}
.flow-meta {
    display: flex;
    align-items: center;
    gap: 0.65rem;
}
.flow-rule-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--text-main);
    margin: 0;
}
.flow-status-pill {
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 0.25rem 0.55rem;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}
.flow-status-pill.active {
    background: #DCFCE7;
    color: #15803D;
    border: 1px solid #86EFAC;
}
.flow-status-pill.paused {
    background: #E2E8F0;
    color: #475569;
    border: 1px solid #CBD5E1;
}
.status-pulse {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #16A34A;
    box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.3);
}

/* Visual Node Pipeline */
.flow-pipeline {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    background: #FFFFFF;
    border: 1.5px solid #E2E8F0;
    border-radius: 10px;
    padding: 1rem 1.25rem;
    margin-bottom: 1rem;
    overflow-x: auto;
}
.flow-node {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
    min-width: 190px;
}
.node-icon-box {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
    border: 2px solid rgba(0,0,0,0.06);
}
.node-content {
    display: flex;
    flex-direction: column;
}
.node-type {
    font-size: 0.68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--text-dim);
    margin-bottom: 0.15rem;
}
.node-title {
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--text-main);
    line-height: 1.25;
}
.node-tag {
    font-size: 0.72rem;
    font-family: monospace;
    color: #0284C7;
    background: #F0F9FF;
    padding: 0.15rem 0.4rem;
    border-radius: 4px;
    display: inline-block;
    margin-top: 0.25rem;
    width: fit-content;
}

/* Visual Connector Arrow */
.flow-connector {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94A3B8;
    flex-shrink: 0;
    padding: 0 0.35rem;
}
.flow-arrow-svg {
    width: 24px;
    height: 24px;
    stroke: #64748B;
}

/* Card Controls & Footer */
.flow-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 0.65rem;
    border-top: 1px solid #E2E8F0;
    font-size: 0.78rem;
    color: var(--text-dim);
}
.flow-stats-wrap {
    display: flex;
    align-items: center;
    gap: 0.85rem;
}
.flow-actions-wrap {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-flow-action {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.65rem;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 600;
    border: 1px solid var(--border);
    background: #FFFFFF;
    color: var(--text-main);
    cursor: pointer;
    box-shadow: var(--shadow-xs);
    transition: all 0.15s ease;
}
.btn-flow-action:hover {
    background: #F8FAFC;
    border-color: #cbd5e1;
    box-shadow: var(--shadow-xs);
}
.btn-flow-action.btn-test {
    color: #0284C7;
    background: #F0F9FF;
    border-color: #BAE6FD;
}
.btn-flow-action.btn-test:hover {
    background: #0284C7;
    color: #FFFFFF;
    border-color: #0284C7;
}
.btn-flow-action.btn-del {
    color: #DC2626;
    background: #FEF2F2;
    border-color: #FECACA;
}
.btn-flow-action.btn-del:hover {
    background: #DC2626;
    color: #FFFFFF;
    border-color: #DC2626;
}

/* 3D Switch Toggle */
.switch-3d {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}
.switch-3d input {
    opacity: 0;
    width: 0;
    height: 0;
}
.switch-slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #CBD5E1;
    border: 2px solid #94A3B8;
    border-radius: 24px;
    transition: .2s;
}
.switch-slider:before {
    position: absolute;
    content: "";
    height: 14px;
    width: 14px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    border-radius: 50%;
    box-shadow: 1px 1px 2px rgba(0,0,0,0.25);
    transition: .2s;
}
input:checked + .switch-slider {
    background-color: #16A34A;
    border-color: #15803D;
}
input:checked + .switch-slider:before {
    transform: translateX(20px);
}

/* Empty State Canvas */
.empty-workflow-canvas {
    text-align: center;
    padding: 3.5rem 2rem;
    border: 2px dashed #CBD5E1;
    border-radius: 12px;
    background: #FAFAFA;
}
.empty-icon {
    font-size: 2.8rem;
    margin-bottom: 1rem;
    display: inline-block;
}
</style>

<!-- Page Header -->
<div class="workflow-header-container">
    <div class="workflow-title-wrap">
        <h1>
            <span>⚡</span>
            <span>Workflow Automations & Event Rules Engine</span>
        </h1>
        <p>Trigger automated tasks, account reassignments, lifecycle stage advances, tag assignments, and webhook payloads across deals, leads, and accounts.</p>
    </div>
</div>

<!-- 1-Click Starter Recipes -->
<div class="recipes-section">
    <div class="recipes-label">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
        <span>Production Starter Recipes (Click Any to Load into Studio)</span>
    </div>
    <div class="recipes-grid">
        <!-- Recipe 1: Deal Won -> Create Task -->
        <div class="recipe-card" onclick="loadRecipe('Enterprise Deal Won Kickoff', 'deal.won', 'create_task', {task_title: 'Schedule Executive Onboarding Call', priority: 'urgent', due_days: 1})">
            <div>
                <div class="recipe-top">
                    <div class="recipe-icon" style="background: #FEF3C7; color: #D97706;">🏆</div>
                    <div class="recipe-title">Closed-Won Kickoff</div>
                </div>
                <div class="recipe-desc">When a deal reaches Closed Won, immediately create an urgent onboarding kickoff task for the account team.</div>
            </div>
            <div class="recipe-btn">Use Task Recipe &rarr;</div>
        </div>

        <!-- Recipe 2: High Value Deal -> Tag VIP -->
        <div class="recipe-card" onclick="loadRecipe('Auto-Tag High Value VIP Deal', 'deal.high_value', 'tag_entity', {tag_name: 'VIP-Account'})">
            <div>
                <div class="recipe-top">
                    <div class="recipe-icon" style="background: #F3E8FF; color: #7C3AED;">💎</div>
                    <div class="recipe-title">VIP Deal Tagging</div>
                </div>
                <div class="recipe-desc">When a high-value deal (> $50k) is detected, automatically attach the VIP-Account label across the record.</div>
            </div>
            <div class="recipe-btn">Use Tag Recipe &rarr;</div>
        </div>

        <!-- Recipe 3: Stagnant Deal -> Log Audit Note -->
        <div class="recipe-card" onclick="loadRecipe('Rotting Stagnant Inactivity Warning', 'deal.stagnant', 'log_note', {note_title: '⚠️ Rotting Deal Inactivity Alert', note_body: 'Deal has stalled past SLA threshold. Account flagged for executive review.'})">
            <div>
                <div class="recipe-top">
                    <div class="recipe-icon" style="background: #FEE2E2; color: #DC2626;">⏳</div>
                    <div class="recipe-title">Stagnant Deal Log</div>
                </div>
                <div class="recipe-desc">When deal rotting threshold is triggered, post an internal audit note to the activity timeline.</div>
            </div>
            <div class="recipe-btn">Use Note Recipe &rarr;</div>
        </div>

        <!-- Recipe 4: Deal Won -> Send Slack/Zapier Webhook -->
        <div class="recipe-card" onclick="loadRecipe('Slack Notification on Won Deal', 'deal.won', 'send_webhook', {webhook_url: 'https://hooks.slack.com/services/CRX/DEALS/WON'})">
            <div>
                <div class="recipe-top">
                    <div class="recipe-icon" style="background: #E0F2FE; color: #0284C7;">🌐</div>
                    <div class="recipe-title">Webhook / Slack Alert</div>
                </div>
                <div class="recipe-desc">When a contract closes, dispatch real-time JSON payload to external webhooks (Slack, Zapier, Make).</div>
            </div>
            <div class="recipe-btn">Use Webhook Recipe &rarr;</div>
        </div>

        <!-- Recipe 5: High Value Deal -> Email Alert via PHPMailer -->
        <div class="recipe-card" onclick="loadRecipe('High Value Deal Email Alert', 'deal.high_value', 'send_email', {email_to: '', email_subject: '💎 High Value Deal Alert: @{{name}}', email_message: 'High value deal @{{name}} worth $@{{amount}} has been created or updated.'})">
            <div>
                <div class="recipe-top">
                    <div class="recipe-icon" style="background: #EEF2FF; color: #4338CA;">✉️</div>
                    <div class="recipe-title">PHPMailer Email Alert</div>
                </div>
                <div class="recipe-desc">Instant branded HTML alert sent to executive team when key revenue thresholds are breached.</div>
            </div>
            <div class="recipe-btn">Use Email Recipe &rarr;</div>
        </div>
    </div>
</div>

<!-- Studio & Canvas Layout -->
<div class="workflow-studio-layout">
    
    <!-- LEFT: Studio Configurator Panel -->
    <div>
        <div class="studio-card">
            <div class="studio-card-header">
                <h3 class="studio-card-title">
                    <span>🛠️</span>
                    <span>Automation Studio</span>
                </h3>
                <span class="step-badge">Configurator</span>
            </div>

            <form method="POST" action="/settings/workflows" id="workflow-form">
                @csrf
                
                <!-- 1. Rule Name -->
                <div class="studio-group">
                    <label class="studio-label" for="rule_name">1. Rule Name *</label>
                    <input type="text" id="rule_name" name="name" class="studio-input" required placeholder="e.g. Kickoff Task on Deal Won">
                </div>

                <!-- 2. Event Trigger -->
                <div class="studio-group">
                    <label class="studio-label" for="trigger_event">2. Event Trigger (WHEN)</label>
                    <select id="trigger_event" name="trigger_event" class="studio-select">
                        <optgroup label="💼 Deals & Opportunities">
                            <option value="deal.won" selected>🏆 Deal Moved to Closed Won</option>
                            <option value="deal.lost">❌ Deal Moved to Closed Lost</option>
                            <option value="deal.stagnant">⏳ Deal Health Rotting / Stagnant</option>
                            <option value="deal.stage_changed">🔄 Deal Moved to Next Stage</option>
                            <option value="deal.created">💼 New Opportunity Opened</option>
                            <option value="deal.high_value">💎 High-Value Deal (> $50,000)</option>
                        </optgroup>
                        <optgroup label="👤 Leads & Contacts">
                            <option value="lead.created">👤 New Inbound Lead Registered</option>
                            <option value="contact.status_customer">⭐ Contact Promoted to Customer</option>
                            <option value="contact.status_churned">📉 Contact Marked as Churned</option>
                        </optgroup>
                        <optgroup label="🏢 Companies & Accounts">
                            <option value="company.created">🏢 New Enterprise Account Added</option>
                        </optgroup>
                        <optgroup label="✅ Tasks & Milestones">
                            <option value="task.completed">✅ Task Marked as Completed</option>
                            <option value="task.overdue">⚠️ Task Overdue (SLA Breached)</option>
                        </optgroup>
                        <optgroup label="📅 Calendar & Meetings">
                            <option value="meeting.scheduled">📅 Meeting Interaction Booked</option>
                        </optgroup>
                    </select>
                </div>

                <!-- 3. Action Type -->
                <div class="studio-group">
                    <label class="studio-label" for="action_type">3. Action to Execute (THEN)</label>
                    <select id="action_type" name="action_type" class="studio-select" onchange="toggleActionFields(this.value)">
                        <option value="create_task" selected>📋 Create Automated Task</option>
                        <option value="tag_entity">🏷️ Attach Tag / Segment Label</option>
                        <option value="update_stage">🔄 Move Deal Stage / Lifecycle Status</option>
                        <option value="reassign_owner">👤 Reassign Account / Deal Owner</option>
                        <option value="log_note">📝 Log Internal Timeline Audit Note</option>
                        <option value="send_webhook">🌐 Dispatch Outgoing Webhook (Slack / Zapier)</option>
                        <option value="send_email">✉️ Send Instant Email Alert (PHPMailer)</option>
                    </select>
                </div>

                <!-- 4. Dynamic Parameter Panels Based on Action Selected -->

                <!-- Panel A: Create Task -->
                <div id="panel_create_task" class="action-param-panel">
                    <div style="font-size:0.75rem;font-weight:800;text-transform:uppercase;color:var(--primary);margin-bottom:0.6rem;">
                        📋 Task Configuration
                    </div>
                    <div class="studio-group" style="margin-bottom:0.75rem;">
                        <label class="studio-label" for="task_title" style="font-size:0.75rem;">Task Title Template *</label>
                        <input type="text" id="task_title" name="task_title" class="studio-input" value="Schedule Onboarding Kickoff">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:0.75rem;">
                        <div>
                            <label class="studio-label" for="priority" style="font-size:0.75rem;">Priority</label>
                            <select id="priority" name="priority" class="studio-select">
                                <option value="urgent">🔴 Urgent</option>
                                <option value="high" selected>🟠 High</option>
                                <option value="medium">🔵 Medium</option>
                            </select>
                        </div>
                        <div>
                            <label class="studio-label" for="due_days" style="font-size:0.75rem;">Due In</label>
                            <select id="due_days" name="due_days" class="studio-select">
                                <option value="0">Immediately</option>
                                <option value="1" selected>In 1 Day</option>
                                <option value="3">In 3 Days</option>
                                <option value="7">In 7 Days</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="studio-label" for="task_assigned_user_id" style="font-size:0.75rem;">Assign Task To</label>
                        <select id="task_assigned_user_id" name="task_assigned_user_id" class="studio-select">
                            <option value="">Auto (Record Owner / Current User)</option>
                            @foreach($users as $u)
                                <option value="{{ $u['id'] }}">{{ $u['name'] }} ({{ $u['email'] }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Panel B: Tag Entity -->
                <div id="panel_tag_entity" class="action-param-panel" style="display:none;">
                    <div style="font-size:0.75rem;font-weight:800;text-transform:uppercase;color:var(--primary);margin-bottom:0.6rem;">
                        🏷️ Tag / Segment Configuration
                    </div>
                    <div class="studio-group" style="margin-bottom:0.75rem;">
                        <label class="studio-label" for="tag_id" style="font-size:0.75rem;">Select Existing Tag</label>
                        <select id="tag_id" name="tag_id" class="studio-select">
                            <option value="0">-- Choose Tag --</option>
                            @foreach($tags as $t)
                                <option value="{{ $t['id'] }}">{{ $t['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="studio-label" for="tag_name" style="font-size:0.75rem;">Or Create / Attach Custom Tag Label</label>
                        <input type="text" id="tag_name" name="tag_name" class="studio-input" placeholder="e.g. VIP-Account, High-Priority">
                    </div>
                </div>

                <!-- Panel C: Update Stage -->
                <div id="panel_update_stage" class="action-param-panel" style="display:none;">
                    <div style="font-size:0.75rem;font-weight:800;text-transform:uppercase;color:var(--primary);margin-bottom:0.6rem;">
                        🔄 Pipeline Stage / Status Transition
                    </div>
                    <div>
                        <label class="studio-label" for="target_stage" style="font-size:0.75rem;">Advance Target Record To</label>
                        <select id="target_stage" name="target_stage" class="studio-select">
                            <option value="proposal">Move Deal to Proposal Stage</option>
                            <option value="negotiation">Move Deal to Negotiation Stage</option>
                            <option value="closed_won">Move Deal to Closed Won 🏆</option>
                            <option value="closed_lost">Move Deal to Closed Lost ❌</option>
                            <option value="customer">Mark Contact Status as Customer ⭐</option>
                            <option value="churned">Mark Contact Status as Churned 📉</option>
                        </select>
                    </div>
                </div>

                <!-- Panel D: Reassign Owner -->
                <div id="panel_reassign_owner" class="action-param-panel" style="display:none;">
                    <div style="font-size:0.75rem;font-weight:800;text-transform:uppercase;color:var(--primary);margin-bottom:0.6rem;">
                        👤 Account Owner Reassignment
                    </div>
                    <div>
                        <label class="studio-label" for="reassign_user_id" style="font-size:0.75rem;">Reassign Record To Team Member *</label>
                        <select id="reassign_user_id" name="reassign_user_id" class="studio-select">
                            @foreach($users as $u)
                                <option value="{{ $u['id'] }}">{{ $u['name'] }} ({{ $u['email'] }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Panel E: Log Audit Note -->
                <div id="panel_log_note" class="action-param-panel" style="display:none;">
                    <div style="font-size:0.75rem;font-weight:800;text-transform:uppercase;color:var(--primary);margin-bottom:0.6rem;">
                        📝 Internal Timeline Audit Note
                    </div>
                    <div class="studio-group" style="margin-bottom:0.75rem;">
                        <label class="studio-label" for="note_title" style="font-size:0.75rem;">Note Header</label>
                        <input type="text" id="note_title" name="note_title" class="studio-input" value="Automated Workflow Log">
                    </div>
                    <div>
                        <label class="studio-label" for="note_body" style="font-size:0.75rem;">Note Body / Audit Trail</label>
                        <textarea id="note_body" name="note_body" class="studio-textarea" placeholder="Automated event logged to activity timeline."></textarea>
                    </div>
                </div>

                <!-- Panel F: Webhook -->
                <div id="panel_send_webhook" class="action-param-panel" style="display:none;">
                    <div style="font-size:0.75rem;font-weight:800;text-transform:uppercase;color:var(--primary);margin-bottom:0.6rem;">
                        🌐 Outgoing Webhook Dispatch
                    </div>
                    <div class="studio-group" style="margin-bottom:0.75rem;">
                        <label class="studio-label" for="webhook_url" style="font-size:0.75rem;">Endpoint URL (HTTPS) *</label>
                        <input type="url" id="webhook_url" name="webhook_url" class="studio-input" placeholder="https://hooks.slack.com/services/...">
                    </div>
                    <div>
                        <label class="studio-label" for="webhook_secret" style="font-size:0.75rem;">Optional Secret Token</label>
                        <input type="text" id="webhook_secret" name="webhook_secret" class="studio-input" placeholder="Bearer crx_wh_...">
                    </div>
                </div>

                <!-- Panel G: Email Alert (PHPMailer) -->
                <div id="panel_send_email" class="action-param-panel" style="display:none;">
                    <div style="font-size:0.75rem;font-weight:800;text-transform:uppercase;color:var(--primary);margin-bottom:0.6rem;">
                        ✉️ Email Alert Configuration (PHPMailer)
                    </div>
                    <div class="studio-group" style="margin-bottom:0.75rem;">
                        <label class="studio-label" for="email_to" style="font-size:0.75rem;">Recipient Email (Leave blank for Record Owner)</label>
                        <input type="email" id="email_to" name="email_to" class="studio-input" placeholder="e.g. notifications@company.com">
                    </div>
                    <div class="studio-group" style="margin-bottom:0.75rem;">
                        <label class="studio-label" for="email_subject" style="font-size:0.75rem;">Email Subject Template</label>
                        <input type="text" id="email_subject" name="email_subject" class="studio-input" value="[CRX Alert] Deal @{{name}} Update">
                    </div>
                    <div>
                        <label class="studio-label" for="email_message" style="font-size:0.75rem;">Email Message Body</label>
                        <textarea id="email_message" name="email_message" class="studio-textarea" placeholder="Opportunity @{{name}} has been updated. Amount: $@{{amount}}. Stage: @{{stage}}."></textarea>
                    </div>
                </div>

                <button type="submit" class="deploy-btn" id="submit-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                    <span>Deploy Automation Rule</span>
                </button>
            </form>
        </div>
    </div>

    <!-- RIGHT: Visual Workflow Canvas -->
    <div>
        <div class="canvas-card">
            <div class="canvas-header">
                <h3 class="canvas-title">
                    <span>🔄</span>
                    <span>Live Automation Pipelines</span>
                </h3>
                <span class="rules-count-pill">{{ count($rules) }} Active</span>
            </div>

            @if(empty($rules))
                <div class="empty-workflow-canvas">
                    <div class="empty-icon">⚡</div>
                    <h4 style="font-size:1.1rem;font-weight:800;color:var(--text-main);margin:0 0 0.5rem 0;">No Active Workflow Rules</h4>
                    <p style="color:var(--text-dim);font-size:0.9rem;max-width:400px;margin:0 auto 1.5rem auto;">
                        Click on any of the Production Starter Recipes above to deploy your first automated deal, lead, or webhook rule in seconds.
                    </p>
                    <button type="button" class="btn btn-primary" onclick="loadRecipe('Enterprise Deal Won Kickoff', 'deal.won', 'create_task', {task_title: 'Schedule Executive Onboarding Call', priority: 'urgent', due_days: 1})">
                        🚀 Load Recommended Starter Rule
                    </button>
                </div>
            @else
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    @foreach($rules as $r)
                        @php 
                            $payload = json_decode((string)$r['action_payload'], true) ?? [];
                            $isActive = (bool)$r['is_active'];
                            $ev = $r['trigger_event'];
                            $act = $r['action_type'];
                            
                            // Visual styling by event
                            $evMeta = match($ev) {
                                'deal.won'          => ['icon' => '🏆', 'bg' => '#FEF3C7', 'border' => '#FCD34D', 'label' => 'Deal Won', 'type' => 'Closed Won Event'],
                                'deal.lost'         => ['icon' => '❌', 'bg' => '#FEE2E2', 'border' => '#FCA5A5', 'label' => 'Deal Lost', 'type' => 'Closed Lost Event'],
                                'deal.stagnant'     => ['icon' => '⏳', 'bg' => '#FEF2F2', 'border' => '#FECACA', 'label' => 'Deal Stagnant', 'type' => 'Rotting Trigger'],
                                'deal.stage_changed'=> ['icon' => '🔄', 'bg' => '#EFF6FF', 'border' => '#BFDBFE', 'label' => 'Stage Changed', 'type' => 'Pipeline Advance'],
                                'deal.high_value'   => ['icon' => '💎', 'bg' => '#F3E8FF', 'border' => '#D8B4FE', 'label' => 'High Value Deal', 'type' => 'Enterprise Alert'],
                                'deal.created'      => ['icon' => '💼', 'bg' => '#F8FAFC', 'border' => '#CBD5E1', 'label' => 'Deal Created', 'type' => 'New Opportunity'],
                                'lead.created'      => ['icon' => '👤', 'bg' => '#F3E8FF', 'border' => '#D8B4FE', 'label' => 'Lead Ingested', 'type' => 'Inbound Contact'],
                                'contact.status_customer'=>['icon'=>'⭐', 'bg' => '#FEF3C7', 'border' => '#FCD34D', 'label' => 'Customer Promoted', 'type' => 'Lifecycle Transition'],
                                'contact.status_churned' =>['icon'=>'📉', 'bg' => '#FEE2E2', 'border' => '#FCA5A5', 'label' => 'Contact Churned', 'type' => 'Churn Risk'],
                                'company.created'   => ['icon' => '🏢', 'bg' => '#E0F2FE', 'border' => '#7DD3FC', 'label' => 'Company Created', 'type' => 'Account Ingestion'],
                                'task.completed'    => ['icon' => '✅', 'bg' => '#DCFCE7', 'border' => '#86EFAC', 'label' => 'Task Completed', 'type' => 'Milestone Complete'],
                                'task.overdue'      => ['icon' => '⚠️', 'bg' => '#FEF2F2', 'border' => '#FECACA', 'label' => 'Task Overdue', 'type' => 'SLA Breach'],
                                'meeting.scheduled' => ['icon' => '📅', 'bg' => '#F0FDF4', 'border' => '#BBF7D0', 'label' => 'Meeting Booked', 'type' => 'Calendar Event'],
                                default             => ['icon' => '⚡', 'bg' => '#F1F5F9', 'border' => '#CBD5E1', 'label' => $ev, 'type' => 'Custom Trigger'],
                            };

                            // Visual styling by action
                            $actMeta = match($act) {
                                'create_task'    => [
                                    'icon'  => '📋', 
                                    'bg'    => '#F0F9FF', 
                                    'border'=> '#BAE6FD', 
                                    'title' => $payload['title'] ?? 'Create Task', 
                                    'badge' => ucfirst($payload['priority'] ?? 'medium') . ' Priority',
                                    'color' => '#0284C7'
                                ],
                                'tag_entity'     => [
                                    'icon'  => '🏷️', 
                                    'bg'    => '#F3E8FF', 
                                    'border'=> '#D8B4FE', 
                                    'title' => 'Attach Tag: ' . ($payload['tag_name'] ?: ('Tag #' . ($payload['tag_id'] ?? ''))), 
                                    'badge' => 'Tag Label',
                                    'color' => '#7C3AED'
                                ],
                                'update_stage'   => [
                                    'icon'  => '🔄', 
                                    'bg'    => '#FEF3C7', 
                                    'border'=> '#FCD34D', 
                                    'title' => 'Move to: ' . ucfirst(str_replace('_', ' ', $payload['target_stage'] ?? 'next')), 
                                    'badge' => 'Lifecycle Transition',
                                    'color' => '#D97706'
                                ],
                                'reassign_owner' => [
                                    'icon'  => '👤', 
                                    'bg'    => '#DCFCE7', 
                                    'border'=> '#86EFAC', 
                                    'title' => 'Reassign to User #' . ($payload['assigned_user_id'] ?? 1), 
                                    'badge' => 'Owner Assignment',
                                    'color' => '#16A34A'
                                ],
                                'log_note'       => [
                                    'icon'  => '📝', 
                                    'bg'    => '#F1F5F9', 
                                    'border'=> '#CBD5E1', 
                                    'title' => $payload['note_title'] ?? 'Internal Audit Note', 
                                    'badge' => 'Timeline Log',
                                    'color' => '#475569'
                                ],
                                'send_webhook'   => [
                                    'icon'  => '🌐', 
                                    'bg'    => '#EFF6FF', 
                                    'border'=> '#BFDBFE', 
                                    'title' => 'Dispatch Webhook Payload', 
                                    'badge' => 'External Integration',
                                    'color' => '#2563EB'
                                ],
                                'send_email'     => [
                                    'icon'  => '✉️', 
                                    'bg'    => '#EEF2FF', 
                                    'border'=> '#C7D2FE', 
                                    'title' => 'Dispatch Email Alert (PHPMailer)', 
                                    'badge' => 'Instant Alert',
                                    'color' => '#4338CA'
                                ],
                                default          => [
                                    'icon'  => '⚡', 
                                    'bg'    => '#F8FAFC', 
                                    'border'=> '#E2E8F0', 
                                    'title' => $act, 
                                    'badge' => 'Action',
                                    'color' => '#0F172A'
                                ],
                            };
                        @endphp

                        <div class="workflow-flow-card {{ $isActive ? '' : 'paused' }}" id="rule-card-{{ $r['id'] }}">
                            <!-- Card Header -->
                            <div class="flow-card-head">
                                <div class="flow-meta">
                                    <span class="flow-status-pill {{ $isActive ? 'active' : 'paused' }}">
                                        @if($isActive)
                                            <span class="status-pulse"></span>
                                            <span>Active</span>
                                        @else
                                            <span>Paused</span>
                                        @endif
                                    </span>
                                    <h4 class="flow-rule-title">{{ $r['name'] }}</h4>
                                </div>

                                <!-- Toggle Switch -->
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <form method="POST" action="/settings/workflows/{{ $r['id'] }}/toggle" id="toggle-form-{{ $r['id'] }}" style="margin:0;">
                                        @csrf
                                        <label class="switch-3d" title="{{ $isActive ? 'Pause Workflow' : 'Activate Workflow' }}">
                                            <input type="checkbox" {{ $isActive ? 'checked' : '' }} onchange="document.getElementById('toggle-form-{{ $r['id'] }}').submit();">
                                            <span class="switch-slider"></span>
                                        </label>
                                    </form>
                                </div>
                            </div>

                            <!-- Visual Flow Pipeline: Trigger -> Arrow -> Condition -> Arrow -> Action -->
                            <div class="flow-pipeline">
                                <!-- Trigger Node -->
                                <div class="flow-node">
                                    <div class="node-icon-box" style="background: {{ $evMeta['bg'] }}; border-color: {{ $evMeta['border'] }};">
                                        {{ $evMeta['icon'] }}
                                    </div>
                                    <div class="node-content">
                                        <span class="node-type">When Trigger Fires</span>
                                        <span class="node-title">{{ $evMeta['label'] }}</span>
                                        <span class="node-tag">{{ $ev }}</span>
                                    </div>
                                </div>

                                <!-- Visual Arrow -->
                                <div class="flow-connector">
                                    <svg class="flow-arrow-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="4" y1="12" x2="20" y2="12"></line>
                                        <polyline points="14 6 20 12 14 18"></polyline>
                                    </svg>
                                </div>

                                <!-- Condition Node -->
                                <div class="flow-node" style="max-width: 140px; min-width: 110px;">
                                    <div class="node-content">
                                        <span class="node-type">Condition</span>
                                        <span class="node-title" style="font-size:0.8rem; color:#475569;">Matching Entity</span>
                                        <span style="font-size:0.7rem; color:var(--text-dim); margin-top:0.2rem;">Rule Evaluated</span>
                                    </div>
                                </div>

                                <!-- Visual Arrow -->
                                <div class="flow-connector">
                                    <svg class="flow-arrow-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="4" y1="12" x2="20" y2="12"></line>
                                        <polyline points="14 6 20 12 14 18"></polyline>
                                    </svg>
                                </div>

                                <!-- Action Node -->
                                <div class="flow-node">
                                    <div class="node-icon-box" style="background: {{ $actMeta['bg'] }}; border-color: {{ $actMeta['border'] }}; color: {{ $actMeta['color'] }};">
                                        {{ $actMeta['icon'] }}
                                    </div>
                                    <div class="node-content">
                                        <span class="node-type">Then Execute Action</span>
                                        <span class="node-title">{{ $actMeta['title'] }}</span>
                                        <span style="font-size:0.72rem; font-weight:700; background:{{ $actMeta['bg'] }}; color:{{ $actMeta['color'] }}; padding:0.15rem 0.45rem; border-radius:4px; margin-top:0.25rem; width:fit-content; border:1px solid {{ $actMeta['border'] }};">
                                            {{ $actMeta['badge'] }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Footer: Stats & Direct Actions -->
                            <div class="flow-card-footer">
                                <div class="flow-stats-wrap">
                                    <span>⚡ <strong>Action Type:</strong> <code>{{ $act }}</code></span>
                                    <span>•</span>
                                    <span>🛡️ <strong>Execution:</strong> Real-time Synchronous</span>
                                </div>

                                <div class="flow-actions-wrap">
                                    <!-- Test Run Simulation -->
                                    <form method="POST" action="/settings/workflows/{{ $r['id'] }}/test" style="margin:0;">
                                        @csrf
                                        <button type="submit" class="btn-flow-action btn-test" title="Dispatch simulated test event">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                                            <span>Test Run</span>
                                        </button>
                                    </form>

                                     <!-- Delete Rule -->
                                     <form method="POST" action="/settings/workflows/{{ $r['id'] }}/delete" data-confirm="Permanently delete workflow rule '{{ $r['name'] }}'?" style="margin:0;">
                                         @csrf
                                        <button type="submit" class="btn-flow-action btn-del" title="Delete Rule">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</div>

<!-- Interactive JS to Toggle Action Fields & Pre-Populate Recipes -->
<script>
function toggleActionFields(actionType) {
    const panels = [
        'panel_create_task',
        'panel_tag_entity',
        'panel_update_stage',
        'panel_reassign_owner',
        'panel_log_note',
        'panel_send_webhook',
        'panel_send_email'
    ];

    panels.forEach(pId => {
        const el = document.getElementById(pId);
        if (el) {
            el.style.display = (pId === 'panel_' + actionType) ? 'block' : 'none';
        }
    });
}

function loadRecipe(name, trigger, action, params = {}) {
    document.getElementById('rule_name').value = name;
    document.getElementById('trigger_event').value = trigger;
    document.getElementById('action_type').value = action;
    toggleActionFields(action);

    if (action === 'create_task') {
        if (params.task_title) document.getElementById('task_title').value = params.task_title;
        if (params.priority) document.getElementById('priority').value = params.priority;
        if (params.due_days !== undefined) document.getElementById('due_days').value = params.due_days;
    } else if (action === 'tag_entity') {
        if (params.tag_name) document.getElementById('tag_name').value = params.tag_name;
    } else if (action === 'update_stage') {
        if (params.target_stage) document.getElementById('target_stage').value = params.target_stage;
    } else if (action === 'log_note') {
        if (params.note_title) document.getElementById('note_title').value = params.note_title;
        if (params.note_body) document.getElementById('note_body').value = params.note_body;
    } else if (action === 'send_webhook') {
        if (params.webhook_url) document.getElementById('webhook_url').value = params.webhook_url;
    } else if (action === 'send_email') {
        if (params.email_to !== undefined) document.getElementById('email_to').value = params.email_to;
        if (params.email_subject) document.getElementById('email_subject').value = params.email_subject;
        if (params.email_message) document.getElementById('email_message').value = params.email_message;
    }
    
    // Smooth scroll to builder
    document.getElementById('rule_name').scrollIntoView({ behavior: 'smooth', block: 'center' });
    document.getElementById('rule_name').focus();
    
    // Pulse highlight effect
    const card = document.querySelector('.studio-card');
    card.style.transition = 'box-shadow 0.3s ease, border-color 0.3s ease';
    card.style.borderColor = 'var(--primary)';
    card.style.boxShadow = '0 0 0 4px rgba(79, 70, 229, 0.2), var(--shadow-md)';
    setTimeout(() => {
        card.style.borderColor = 'var(--border)';
        card.style.boxShadow = 'var(--shadow-sm)';
    }, 1200);
}
</script>
@endsection
