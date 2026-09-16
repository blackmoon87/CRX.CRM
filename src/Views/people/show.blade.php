@extends('layouts.main')

@section('title', $person['first_name'] . ' ' . ($person['last_name'] ?? ''))

@section('content')
{{-- Person Hero Card --}}
<div class="entity-hero">
    <div class="entity-hero-main">
        <div class="entity-avatar" style="background-color:var(--c1);">
            {{ strtoupper(substr($person['first_name'], 0, 1)) }}
        </div>
        <div>
            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                <h1 class="entity-title" style="margin:0;">{{ $person['first_name'] }} {{ $person['last_name'] ?? '' }}</h1>
                @if(!empty($tags))
                    @foreach($tags as $tg)
                        <span class="badge" style="background:{{ $tg['color'] }};color:#fff;font-weight:700;font-size:0.75rem;">
                            #{{ $tg['name'] }}
                        </span>
                    @endforeach
                @endif
            </div>

            <div class="entity-badges" style="margin-top:0.4rem;">
                <span class="badge badge-{{ $person['status'] }}">{{ strtoupper($person['status']) }}</span>
                @if(!empty($person['job_title']))
                    <span class="entity-chip">💼 {{ $person['job_title'] }}</span>
                @endif
                @if(!empty($person['company_name']))
                    <span class="entity-chip">
                        🏢 <a href="/companies/{{ $person['company_id'] }}" style="color:inherit;text-decoration:none;font-weight:700;">{{ $person['company_name'] }}</a>
                    </span>
                @endif
            </div>
        </div>
    </div>
    <div class="entity-actions">
        {{-- Tag Attacher Dropdown --}}
        @if(!empty($allTags))
            <form method="POST" action="/settings/tags/attach" style="display:inline-flex;gap:4px;margin:0;">
                @csrf
                <input type="hidden" name="entity_type" value="people">
                <input type="hidden" name="entity_id" value="{{ $person['id'] }}">
                <select name="tag_id" class="form-control" style="padding:4px 8px;font-size:0.75rem;height:auto;" onchange="this.form.submit()">
                    <option value="">+ Add Tag...</option>
                    @foreach($allTags as $at)
                        <option value="{{ $at['id'] }}">#{{ $at['name'] }}</option>
                    @endforeach
                </select>
            </form>
        @endif

        <a href="/opportunities/create?person_id={{ $person['id'] }}&company_id={{ $person['company_id'] ?? '' }}" class="btn btn-primary btn-sm">+ {{ __('app.common.new_deal') }}</a>
        <form method="POST" action="/people/{{ $person['id'] }}/delete" data-confirm="Move this contact to the Workspace Recycle Bin?" style="margin:0;">
            @csrf
            <button type="submit" class="btn btn-danger btn-sm" title="Move to Recycle Bin">{{ __('app.common.delete') }}</button>
        </form>
    </div>
</div>

<div class="detail-layout">
    {{-- Left Sidebar: Contact Profile --}}
    <div>
        <div class="detail-card">
            <h3 style="font-size:16px;font-weight:700;color:var(--ink);margin:0 0 16px 0;border-bottom:1px solid var(--border);padding-bottom:10px;">{{ __('app.common.overview') }}</h3>
            
            <div class="detail-row">
                <span class="detail-label">{{ __('app.common.status') }}</span>
                <span class="detail-val">
                    <span class="badge badge-{{ $person['status'] }}">{{ $person['status'] }}</span>
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">{{ __('app.entities.companies.title') }}</span>
                <span class="detail-val">
                    @if(!empty($person['company_name']))
                        <a href="/companies/{{ $person['company_id'] }}">{{ $person['company_name'] }} ↗</a>
                    @else
                        <span style="color:var(--text-dim);">{{ __('app.common.none') ?? 'None' }}</span>
                    @endif
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">{{ __('app.entities.people.title_role') }}</span>
                <span class="detail-val">{{ $person['job_title'] ?? '—' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">{{ __('app.common.email') }}</span>
                <span class="detail-val">
                    @if(!empty($person['email']))
                        <a href="mailto:{{ $person['email'] }}">{{ $person['email'] }}</a>
                    @else
                        <span style="color:var(--text-dim);">—</span>
                    @endif
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">{{ __('app.common.phone') }}</span>
                <span class="detail-val">
                    @if(!empty($person['phone']))
                        <a href="tel:{{ $person['phone'] }}">{{ $person['phone'] }}</a>
                    @else
                        <span style="color:var(--text-dim);">—</span>
                    @endif
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">{{ __('app.entities.people.address') }}</span>
                <span class="detail-val">{{ $person['address'] ?? '—' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">{{ __('app.entities.people.city') }} & {{ __('app.entities.people.state') }}</span>
                <span class="detail-val">
                    {{ implode(', ', array_filter([$person['city'] ?? '', $person['state'] ?? ''])) ?: '—' }}
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">{{ __('app.entities.people.postal_code') }}</span>
                <span class="detail-val">{{ $person['postal_code'] ?? '—' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">{{ __('app.entities.people.country') }}</span>
                <span class="detail-val">{{ $person['country'] ?? '—' }}</span>
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

    {{-- Right Column: Deals & Quick Action Deck --}}
    <div style="display:flex;flex-direction:column;gap:20px;">
        {{-- Linked Deals --}}
        <div class="detail-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <h3 style="font-size:16px;font-weight:800;color:var(--ink);margin:0;">{{ __('app.entities.opportunities.title') }} ({{ count($opportunities) }})</h3>
                <a href="/opportunities/create?person_id={{ $person['id'] }}&company_id={{ $person['company_id'] ?? '' }}" class="btn btn-secondary btn-sm">+ {{ __('app.common.new_deal') }}</a>
            </div>

            @if(empty($opportunities))
                <div class="empty-box">
                    <div class="empty-box-icon">💼</div>
                    <div class="empty-box-title">{{ __('app.entities.opportunities.title') }}</div>
                    <p class="empty-box-desc">{{ __('app.entities.opportunities.subtitle') }}</p>
                    <a href="/opportunities/create?person_id={{ $person['id'] }}&company_id={{ $person['company_id'] ?? '' }}" class="btn btn-primary btn-sm" style="margin-top:8px;">+ {{ __('app.entities.opportunities.add_new') }}</a>
                </div>
            @else
                <div class="table-container" style="border:none;background:transparent;box-shadow:none;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('app.entities.opportunities.name') }}</th>
                                <th>{{ __('app.entities.opportunities.amount') }}</th>
                                <th>{{ __('app.entities.opportunities.stage') }}</th>
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
                    <button type="button" class="btn btn-sm btn-primary" onclick="setPersonDeckTab('note', this)">📝 Note</button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="setPersonDeckTab('call', this)">📞 Log Call</button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="setPersonDeckTab('meeting', this)">📅 Meeting</button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="setPersonDeckTab('email', this)">✉️ Email</button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="setPersonDeckTab('task', this)">✅ {{ __('app.entities.tasks.title') }}</button>
                </div>

                {{-- Note Form --}}
                <form id="personDeckFormNote" method="POST" action="/interactions" style="display:block;">
                    @csrf
                    <input type="hidden" name="entity_type" value="people">
                    <input type="hidden" name="entity_id" value="{{ $person['id'] }}">
                    <input type="hidden" name="type" value="note">
                    <input type="hidden" name="_redirect" value="/people/{{ $person['id'] }}">
                    <input type="text" name="title" class="form-control" placeholder="{{ __('app.common.title') }} ({{ __('app.common.optional') }})" style="margin-bottom:8px;">
                    <textarea name="description" class="form-control" required placeholder="{{ __('app.common.notes') }}..." style="margin-bottom:8px;"></textarea>
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('app.common.save') }}</button>
                </form>

                {{-- Call Form --}}
                <form id="personDeckFormCall" method="POST" action="/interactions" style="display:none;">
                    @csrf
                    <input type="hidden" name="entity_type" value="people">
                    <input type="hidden" name="entity_id" value="{{ $person['id'] }}">
                    <input type="hidden" name="type" value="call">
                    <input type="hidden" name="_redirect" value="/people/{{ $person['id'] }}">
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
                            <input type="number" name="duration_minutes" class="form-control" placeholder="10" style="padding:6px;">
                        </div>
                    </div>
                    <textarea name="description" class="form-control" required placeholder="Phone conversation summary..." style="margin-bottom:8px;"></textarea>
                    <button type="submit" class="btn btn-primary btn-sm">Log Call Interaction</button>
                </form>

                {{-- Meeting Form --}}
                <form id="personDeckFormMeeting" method="POST" action="/interactions" style="display:none;">
                    @csrf
                    <input type="hidden" name="entity_type" value="people">
                    <input type="hidden" name="entity_id" value="{{ $person['id'] }}">
                    <input type="hidden" name="type" value="meeting">
                    <input type="hidden" name="_redirect" value="/people/{{ $person['id'] }}">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px;">
                        <div>
                            <label class="form-label" style="font-size:0.75rem;">Meeting Title</label>
                            <input type="text" name="title" class="form-control" placeholder="Discovery Session" style="padding:6px;">
                        </div>
                        <div>
                            <label class="form-label" style="font-size:0.75rem;">Scheduled Date & Time</label>
                            <input type="datetime-local" name="scheduled_at" class="form-control" style="padding:6px;">
                        </div>
                    </div>
                    <textarea name="description" class="form-control" required placeholder="Meeting agenda and discussion topics..." style="margin-bottom:8px;"></textarea>
                    <button type="submit" class="btn btn-primary btn-sm">Record Meeting</button>
                </form>

                {{-- Email Form --}}
                <form id="personDeckFormEmail" method="POST" action="/interactions" style="display:none;">
                    @csrf
                    <input type="hidden" name="entity_type" value="people">
                    <input type="hidden" name="entity_id" value="{{ $person['id'] }}">
                    <input type="hidden" name="type" value="email">
                    <input type="hidden" name="_redirect" value="/people/{{ $person['id'] }}">
                    <input type="text" name="title" class="form-control" placeholder="Email Subject" style="margin-bottom:8px;">
                    <textarea name="description" class="form-control" required placeholder="Email body or outreach dispatch..." style="margin-bottom:8px;"></textarea>
                    <button type="submit" class="btn btn-primary btn-sm">Log Sent Email</button>
                </form>

                {{-- Task Form --}}
                <form id="personDeckFormTask" method="POST" action="/tasks" style="display:none;">
                    @csrf
                    <input type="hidden" name="entity_type" value="people">
                    <input type="hidden" name="entity_id" value="{{ $person['id'] }}">
                    <input type="hidden" name="_redirect" value="/people/{{ $person['id'] }}">
                    <div style="display:grid;grid-template-columns:2fr 1fr;gap:8px;margin-bottom:8px;">
                        <input type="text" name="title" class="form-control" required placeholder="Task title..." style="padding:6px;">
                        <input type="date" name="due_date" class="form-control" style="padding:6px;">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">+ Add Follow-up Task</button>
                </form>
            </div>

            {{-- Unified Timeline --}}
            @if(empty($interactions))
                <div class="empty-box">
                    <div class="empty-box-icon">⚡</div>
                    <div class="empty-box-title">No Timeline Activity Logged</div>
                    <p class="empty-box-desc">Use the action deck above to log calls, meetings, notes, or emails with {{ $person['first_name'] }}.</p>
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
function setPersonDeckTab(tab, btn) {
    const tabs = ['note', 'call', 'meeting', 'email', 'task'];
    tabs.forEach(t => {
        const f = document.getElementById('personDeckForm' + t.charAt(0).toUpperCase() + t.slice(1));
        if (f) f.style.display = (t === tab) ? 'block' : 'none';
    });
    btn.parentNode.querySelectorAll('button').forEach(b => {
        b.className = 'btn btn-sm btn-secondary';
    });
    btn.className = 'btn btn-sm btn-primary';
}
</script>
@endsection
