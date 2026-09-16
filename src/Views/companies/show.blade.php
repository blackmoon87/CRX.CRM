@extends('layouts.main')

@section('title', $company['name'])

@section('content')
{{-- Company Hero Card --}}
<div class="entity-hero">
    <div class="entity-hero-main">
        <div class="entity-avatar" style="background-color:var(--c2);">
            {{ strtoupper(substr($company['name'], 0, 1)) }}
        </div>
        <div>
            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                <h1 class="entity-title" style="margin:0;">{{ $company['name'] }}</h1>
                @if(!empty($tags))
                    @foreach($tags as $tg)
                        <span class="badge" style="background:{{ $tg['color'] }};color:#fff;font-weight:700;font-size:0.75rem;">
                            #{{ $tg['name'] }}
                        </span>
                    @endforeach
                @endif
            </div>

            <div class="entity-badges" style="margin-top:0.4rem;">
                @if(!empty($company['domain']))
                    <span class="entity-chip">
                        🌐 <a href="https://{{ $company['domain'] }}" target="_blank" rel="noopener" style="color:inherit;text-decoration:none;">{{ $company['domain'] }}</a>
                    </span>
                @endif
                @if(!empty($company['industry']))
                    <span class="entity-chip">🏢 {{ $company['industry'] }}</span>
                @endif
                @if(!empty($company['city']) || !empty($company['country']))
                    <span class="entity-chip">📍 {{ implode(', ', array_filter([$company['city'] ?? '', $company['country'] ?? ''])) }}</span>
                @endif
            </div>
        </div>
    </div>
    <div class="entity-actions">
        {{-- Tag Attacher Dropdown --}}
        @if(!empty($allTags))
            <form method="POST" action="/settings/tags/attach" style="display:inline-flex;gap:4px;margin:0;">
                @csrf
                <input type="hidden" name="entity_type" value="companies">
                <input type="hidden" name="entity_id" value="{{ $company['id'] }}">
                <select name="tag_id" class="form-control" style="padding:4px 8px;font-size:0.75rem;height:auto;" onchange="this.form.submit()">
                    <option value="">+ Add Tag...</option>
                    @foreach($allTags as $at)
                        <option value="{{ $at['id'] }}">#{{ $at['name'] }}</option>
                    @endforeach
                </select>
            </form>
        @endif

        <a href="/opportunities/create?company_id={{ $company['id'] }}" class="btn btn-primary btn-sm">+ {{ __('app.common.new_deal') }}</a>
        <a href="/people/create?company_id={{ $company['id'] }}" class="btn btn-secondary btn-sm">+ {{ __('app.common.new_contact') }}</a>
        <form method="POST" action="/companies/{{ $company['id'] }}/delete" data-confirm="Move this company to the Workspace Recycle Bin?" style="margin:0;">
            @csrf
            <button type="submit" class="btn btn-danger btn-sm" title="Move to Recycle Bin">{{ __('app.common.delete') }}</button>
        </form>
    </div>
</div>

@php
    $totalDealVal = array_sum(array_map(fn($o) => (float)($o['amount'] ?? 0), $opportunities));
@endphp

{{-- Mini KPI Grid --}}
<div class="mini-kpi-grid">
    <div class="mini-kpi-card">
        <div class="mini-kpi-label">{{ __('app.entities.opportunities.weighted_value') }}</div>
        <div class="mini-kpi-val" style="color:var(--c2);">${{ number_format($totalDealVal, 0) }}</div>
    </div>
    <div class="mini-kpi-card">
        <div class="mini-kpi-label">{{ __('app.entities.companies.revenue') }}</div>
        <div class="mini-kpi-val" style="color:var(--c4);">
            {{ !empty($company['annual_revenue']) ? '$' . number_format((float)$company['annual_revenue'], 0) : '—' }}
        </div>
    </div>
    <div class="mini-kpi-card">
        <div class="mini-kpi-label">{{ __('app.entities.people.title') }}</div>
        <div class="mini-kpi-val">{{ count($people) }}</div>
    </div>
    <div class="mini-kpi-card">
        <div class="mini-kpi-label">{{ __('app.entities.tasks.title') }}</div>
        <div class="mini-kpi-val">{{ count(array_filter($tasks, fn($t) => ($t['status'] ?? '') !== 'completed')) }}</div>
    </div>
</div>

{{-- Detail Deck (2 Column 3D Layout) --}}
<div class="detail-layout">
    {{-- Left Sidebar: Firmographics & Custom Fields --}}
    <div>
        <div class="detail-card">
            <h3 style="font-size:16px;font-weight:700;color:var(--ink);margin:0 0 16px 0;border-bottom:1px solid var(--border);padding-bottom:10px;">{{ __('app.common.overview') }}</h3>
            
            <div class="detail-row">
                <span class="detail-label">{{ __('app.entities.companies.domain') }}</span>
                <span class="detail-val">
                    @if(!empty($company['domain']))
                        <a href="https://{{ $company['domain'] }}" target="_blank" rel="noopener">{{ $company['domain'] }} ↗</a>
                    @else
                        <span style="color:var(--text-dim);">—</span>
                    @endif
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">{{ __('app.entities.companies.industry') }}</span>
                <span class="detail-val">{{ $company['industry'] ?? '—' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">{{ __('app.entities.companies.revenue') }}</span>
                <span class="detail-val" style="color:var(--c4);font-weight:800;">
                    @if(!empty($company['annual_revenue']))
                        ${{ number_format((float)$company['annual_revenue'], 0) }}
                    @else
                        <span style="color:var(--text-dim);">—</span>
                    @endif
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">{{ __('app.entities.companies.phone') }}</span>
                <span class="detail-val">
                    @if(!empty($company['phone']))
                        <a href="tel:{{ $company['phone'] }}">{{ $company['phone'] }}</a>
                    @else
                        <span style="color:var(--text-dim);">—</span>
                    @endif
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">{{ __('app.common.email') }}</span>
                <span class="detail-val">
                    @if(!empty($company['email']))
                        <a href="mailto:{{ $company['email'] }}">{{ $company['email'] }}</a>
                    @else
                        <span style="color:var(--text-dim);">—</span>
                    @endif
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">{{ __('app.entities.companies.domain') }}</span>
                <span class="detail-val">
                    @if(!empty($company['website']))
                        <a href="{{ $company['website'] }}" target="_blank" rel="noopener">{{ __('app.common.view') }} ↗</a>
                    @else
                        <span style="color:var(--text-dim);">—</span>
                    @endif
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">{{ __('app.entities.companies.address') }}</span>
                <span class="detail-val">{{ $company['address'] ?? '—' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">{{ __('app.entities.companies.city') }} & {{ __('app.entities.companies.state') }}</span>
                <span class="detail-val">
                    {{ implode(', ', array_filter([$company['city'] ?? '', $company['state'] ?? ''])) ?: '—' }}
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">{{ __('app.entities.companies.postal_code') }}</span>
                <span class="detail-val">{{ $company['postal_code'] ?? '—' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">{{ __('app.entities.companies.country') }}</span>
                <span class="detail-val">{{ $company['country'] ?? '—' }}</span>
            </div>

            {{-- Custom Fields --}}
            @if(!empty($customFields))
                <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border);">
                    <h4 style="font-size:12px;font-weight:800;color:var(--text-dim);text-transform:uppercase;letter-spacing:0.06em;margin:0 0 12px 0;">{{ __('app.settings.custom_fields.title') }}</h4>
                    @foreach($customFields as $cf)
                        <div class="detail-row">
                            <span class="detail-label">{{ $cf['name'] }}</span>
                            <span class="detail-val">
                                @php
                                    $rawVal = $customValues[$cf['id']] ?? null;
                                    $displayVal = '—';
                                    if ($rawVal !== null && $rawVal !== '') {
                                        if (is_array($rawVal)) {
                                            $displayVal = implode(', ', array_map(fn($v) => is_scalar($v) ? (string)$v : json_encode($v), $rawVal));
                                        } elseif (($cf['type'] ?? '') === 'boolean') {
                                            $displayVal = $rawVal ? 'Yes' : 'No';
                                        } else {
                                            $displayVal = (string)$rawVal;
                                        }
                                    }
                                @endphp
                                {{ $displayVal }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Right Deck: Deals, Contacts & Tactile Quick-Action Feed --}}
    <div style="display:flex;flex-direction:column;gap:20px;">
        {{-- Associated People (Contacts) --}}
        <div class="detail-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <h3 style="font-size:16px;font-weight:800;color:var(--ink);margin:0;">{{ __('app.entities.people.title') }} ({{ count($people) }})</h3>
                <a href="/people/create?company_id={{ $company['id'] }}" class="btn btn-secondary btn-sm">+ {{ __('app.common.new_contact') }}</a>
            </div>

            @if(empty($people))
                <div class="empty-box">
                    <div class="empty-box-icon">👥</div>
                    <div class="empty-box-title">{{ __('app.entities.people.title') }}</div>
                    <p class="empty-box-desc">{{ __('app.entities.people.subtitle') }}</p>
                    <a href="/people/create?company_id={{ $company['id'] }}" class="btn btn-primary btn-sm" style="margin-top:8px;">+ {{ __('app.entities.people.add_new') }}</a>
                </div>
            @else
                <div class="table-container" style="border:none;background:transparent;box-shadow:none;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('app.common.name') }}</th>
                                <th>{{ __('app.entities.people.title_role') }}</th>
                                <th>{{ __('app.common.email') }}</th>
                                <th>{{ __('app.common.phone') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($people as $p)
                                <tr>
                                    <td>
                                        <a href="/people/{{ $p['id'] }}" style="font-weight:800;color:var(--primary);text-decoration:none;">
                                            {{ $p['first_name'] }} {{ $p['last_name'] ?? '' }}
                                        </a>
                                    </td>
                                    <td>{{ $p['job_title'] ?? '—' }}</td>
                                    <td><a href="mailto:{{ $p['email'] }}">{{ $p['email'] }}</a></td>
                                    <td>{{ $p['phone'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Open Deals & Opportunities --}}
        <div class="detail-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <h3 style="font-size:16px;font-weight:800;color:var(--ink);margin:0;">{{ __('app.entities.opportunities.title') }} ({{ count($opportunities) }})</h3>
                <a href="/opportunities/create?company_id={{ $company['id'] }}" class="btn btn-secondary btn-sm">+ {{ __('app.common.new_deal') }}</a>
            </div>

            @if(empty($opportunities))
                <div class="empty-box">
                    <div class="empty-box-icon">💼</div>
                    <div class="empty-box-title">{{ __('app.entities.opportunities.title') }}</div>
                    <p class="empty-box-desc">{{ __('app.entities.opportunities.subtitle') }}</p>
                    <a href="/opportunities/create?company_id={{ $company['id'] }}" class="btn btn-primary btn-sm" style="margin-top:8px;">+ {{ __('app.entities.opportunities.add_new') }}</a>
                </div>
            @else
                <div class="table-container" style="border:none;background:transparent;box-shadow:none;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('app.entities.opportunities.name') }}</th>
                                <th>{{ __('app.entities.opportunities.amount') }}</th>
                                <th>{{ __('app.entities.opportunities.stage') }}</th>
                                <th>{{ __('app.entities.opportunities.close_date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($opportunities as $opp)
                                <tr>
                                    <td>
                                        <a href="/opportunities/{{ $opp['id'] }}" style="font-weight:800;color:var(--primary);text-decoration:none;">
                                            {{ $opp['name'] }}
                                        </a>
                                    </td>
                                    <td style="font-weight:800;color:var(--c4);">${{ number_format((float)$opp['amount'], 0) }}</td>
                                    <td><span class="badge badge-{{ $opp['stage'] }}">{{ str_replace('_', ' ', $opp['stage']) }}</span></td>
                                    <td style="font-weight:600;color:var(--text-dim);">{{ $opp['expected_close_date'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Pro CRM: Tactile Quick-Action Deck & Unified Interactions Feed --}}
        <div class="detail-card">
            <h3 style="font-size:16px;font-weight:800;color:var(--ink);margin:0 0 16px 0;">
                {{ __('app.common.notes') }}
            </h3>

            {{-- Tabbed Quick Deck --}}
            <div style="background:#f8fafc;border:1px solid var(--border);box-shadow:var(--shadow-xs);border-radius:var(--r-sm);padding:14px;margin-bottom:20px;">
                <div style="display:flex;gap:6px;margin-bottom:12px;border-bottom:1px solid var(--border);padding-bottom:8px;">
                    <button type="button" class="btn btn-sm btn-primary" onclick="setDeckTab('note', this)">📝 Note</button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="setDeckTab('call', this)">📞 Log Call</button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="setDeckTab('meeting', this)">📅 Meeting</button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="setDeckTab('email', this)">✉️ Email</button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="setDeckTab('task', this)">✅ {{ __('app.entities.tasks.title') }}</button>
                </div>

                {{-- Note Form --}}
                <form id="deckFormNote" method="POST" action="/interactions" style="display:block;">
                    @csrf
                    <input type="hidden" name="entity_type" value="companies">
                    <input type="hidden" name="entity_id" value="{{ $company['id'] }}">
                    <input type="hidden" name="type" value="note">
                    <input type="hidden" name="_redirect" value="/companies/{{ $company['id'] }}">
                    <input type="text" name="title" class="form-control" placeholder="{{ __('app.common.title') }} ({{ __('app.common.optional') }})" style="margin-bottom:8px;">
                    <textarea name="description" class="form-control" required placeholder="{{ __('app.common.notes') }}..." style="margin-bottom:8px;"></textarea>
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('app.common.save') }}</button>
                </form>

                {{-- Call Log Form --}}
                <form id="deckFormCall" method="POST" action="/interactions" style="display:none;">
                    @csrf
                    <input type="hidden" name="entity_type" value="companies">
                    <input type="hidden" name="entity_id" value="{{ $company['id'] }}">
                    <input type="hidden" name="type" value="call">
                    <input type="hidden" name="_redirect" value="/companies/{{ $company['id'] }}">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px;">
                        <div>
                            <label class="form-label" style="font-size:0.75rem;">Call Outcome</label>
                            <select name="outcome" class="form-control" style="padding:6px;">
                                <option value="connected">Connected & Discussed</option>
                                <option value="voicemail">Left Voicemail</option>
                                <option value="busy">Busy / No Answer</option>
                                <option value="wrong_number">Wrong Number</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" style="font-size:0.75rem;">Duration (Minutes)</label>
                            <input type="number" name="duration_minutes" class="form-control" placeholder="15" style="padding:6px;">
                        </div>
                    </div>
                    <textarea name="description" class="form-control" required placeholder="Summary of phone conversation..." style="margin-bottom:8px;"></textarea>
                    <button type="submit" class="btn btn-primary btn-sm">Log Call Interaction</button>
                </form>

                {{-- Meeting Form --}}
                <form id="deckFormMeeting" method="POST" action="/interactions" style="display:none;">
                    @csrf
                    <input type="hidden" name="entity_type" value="companies">
                    <input type="hidden" name="entity_id" value="{{ $company['id'] }}">
                    <input type="hidden" name="type" value="meeting">
                    <input type="hidden" name="_redirect" value="/companies/{{ $company['id'] }}">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px;">
                        <div>
                            <label class="form-label" style="font-size:0.75rem;">Meeting Title</label>
                            <input type="text" name="title" class="form-control" placeholder="Quarterly Review Meeting" style="padding:6px;">
                        </div>
                        <div>
                            <label class="form-label" style="font-size:0.75rem;">Scheduled Date & Time</label>
                            <input type="datetime-local" name="scheduled_at" class="form-control" style="padding:6px;">
                        </div>
                    </div>
                    <textarea name="description" class="form-control" required placeholder="Meeting agenda, attendees, and key decisions..." style="margin-bottom:8px;"></textarea>
                    <button type="submit" class="btn btn-primary btn-sm">Record Meeting</button>
                </form>

                {{-- Email Form --}}
                <form id="deckFormEmail" method="POST" action="/interactions" style="display:none;">
                    @csrf
                    <input type="hidden" name="entity_type" value="companies">
                    <input type="hidden" name="entity_id" value="{{ $company['id'] }}">
                    <input type="hidden" name="type" value="email">
                    <input type="hidden" name="_redirect" value="/companies/{{ $company['id'] }}">
                    <input type="text" name="title" class="form-control" placeholder="Email Subject Line" style="margin-bottom:8px;">
                    <textarea name="description" class="form-control" required placeholder="Draft or logged email message body..." style="margin-bottom:8px;"></textarea>
                    <button type="submit" class="btn btn-primary btn-sm">Log Sent Email</button>
                </form>

                {{-- Task Form --}}
                <form id="deckFormTask" method="POST" action="/tasks" style="display:none;">
                    @csrf
                    <input type="hidden" name="entity_type" value="companies">
                    <input type="hidden" name="entity_id" value="{{ $company['id'] }}">
                    <input type="hidden" name="_redirect" value="/companies/{{ $company['id'] }}">
                    <div style="display:grid;grid-template-columns:2fr 1fr;gap:8px;margin-bottom:8px;">
                        <input type="text" name="title" class="form-control" required placeholder="Task title..." style="padding:6px;">
                        <input type="date" name="due_date" class="form-control" style="padding:6px;">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">+ Add Action Task</button>
                </form>
            </div>

            {{-- Unified Reverse-Chronological Timeline --}}
            @if(empty($interactions))
                <div class="empty-box">
                    <div class="empty-box-icon">⚡</div>
                    <div class="empty-box-title">No Timeline Activity Logged</div>
                    <p class="empty-box-desc">Use the action deck above to log calls, meetings, notes, or emails.</p>
                </div>
            @else
                <div style="display:flex;flex-direction:column;gap:12px;">
                    @foreach($interactions as $act)
                        @php
                            $icon = match($act['type']) {
                                'call' => '📞',
                                'meeting' => '📅',
                                'email' => '✉️',
                                'stage_change' => '🔄',
                                default => '📝'
                            };
                            $badgeColor = match($act['type']) {
                                'call' => 'var(--c2)',
                                'meeting' => 'var(--c1)',
                                'email' => 'var(--c6)',
                                'stage_change' => 'var(--c3)',
                                default => 'var(--c4)'
                            };
                        @endphp
                        <div style="background:#ffffff;border:1px solid var(--border);box-shadow:var(--shadow-xs);border-radius:var(--r-sm);padding:14px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <span style="font-size:1.1rem;">{{ $icon }}</span>
                                    <span class="badge" style="background:{{ $badgeColor }};color:#fff;font-size:0.7rem;text-transform:uppercase;">{{ $act['type'] }}</span>
                                    <strong style="font-size:0.9rem;color:var(--text-main);">{{ $act['title'] ?? ucfirst($act['type']) }}</strong>
                                    @if(!empty($act['outcome']))
                                        <span class="badge badge-secondary" style="font-size:0.7rem;">{{ ucfirst($act['outcome']) }}</span>
                                    @endif
                                    @if(!empty($act['duration_minutes']))
                                        <span style="font-size:0.75rem;color:var(--text-dim);">({{ $act['duration_minutes'] }} mins)</span>
                                    @endif
                                </div>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <span style="font-size:0.75rem;color:var(--text-dim);">{{ date('M j, Y g:ia', strtotime($act['created_at'])) }}</span>
                                    <form method="POST" action="/interactions/{{ $act['id'] }}/delete" data-confirm="Are you sure you want to delete this activity entry?" style="margin:0;">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm" style="padding:1px 6px;font-size:10px;line-height:1;" title="Delete activity">✕</button>
                                    </form>
                                </div>
                            </div>
                            @if(!empty($act['scheduled_at']))
                                @php
                                    $meetingTime = strtotime($act['scheduled_at']);
                                    $isUpcoming = $meetingTime > (time() - 3600);
                                    $gCalUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' . urlencode($act['title'] ?? 'Meeting') . '&dates=' . gmdate('Ymd\THis\Z', $meetingTime) . '/' . gmdate('Ymd\THis\Z', $meetingTime + 1800) . '&details=' . urlencode($act['description'] ?? '');
                                @endphp
                                <div style="display:flex;align-items:center;justify-content:space-between;background:var(--bg);border:1px solid var(--border);border-radius:4px;padding:6px 10px;margin-bottom:8px;gap:8px;flex-wrap:wrap;">
                                    <div style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:700;">
                                        <span>⏰ Scheduled Meeting:</span>
                                        <span style="color:var(--primary);">{{ date('l, M j, Y @ g:i A', $meetingTime) }}</span>
                                        @if($isUpcoming)
                                            <span class="badge badge-success" style="font-size:10px;">🔔 Active Alarm Task</span>
                                        @else
                                            <span class="badge" style="background:#64748b;color:#fff;font-size:10px;">Past</span>
                                        @endif
                                    </div>
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <a href="{{ $gCalUrl }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm" style="padding:2px 8px;font-size:11px;text-decoration:none;">
                                            📅 Google Cal
                                        </a>
                                        <a href="/interactions/{{ $act['id'] }}/ics" class="btn btn-secondary btn-sm" style="padding:2px 8px;font-size:11px;text-decoration:none;" title="Download Outlook / Apple Calendar .ics file">
                                            📥 iCal (.ics)
                                        </a>
                                    </div>
                                </div>
                            @endif
                            <p style="margin:0;font-size:0.85rem;color:var(--text-main);line-height:1.5;white-space:pre-wrap;">{{ $act['description'] }}</p>
                        </div>
                    @endforeach

                </div>
            @endif
        </div>
    </div>
</div>

<script>
function setDeckTab(tab, btn) {
    const tabs = ['note', 'call', 'meeting', 'email', 'task'];
    tabs.forEach(t => {
        const f = document.getElementById('deckForm' + t.charAt(0).toUpperCase() + t.slice(1));
        if (f) f.style.display = (t === tab) ? 'block' : 'none';
    });
    btn.parentNode.querySelectorAll('button').forEach(b => {
        b.className = 'btn btn-sm btn-secondary';
    });
    btn.className = 'btn btn-sm btn-primary';
}
</script>
@endsection
