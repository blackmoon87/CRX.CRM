@extends('layouts.main')

@section('title', __('app.reports.title'))

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
    <div>
        <h1 class="page-title">{{ __('app.reports.title') }}</h1>
        <p class="page-subtitle">{{ __('app.reports.subtitle') }}</p>
    </div>
    <div>
        @php
            $exportParams = http_build_query(array_filter([
                'period'      => $activeFilters['period'] ?? null,
                'date_from'   => $activeFilters['date_from'] ?? null,
                'date_to'     => $activeFilters['date_to'] ?? null,
                'pipeline_id' => $activeFilters['pipeline_id'] ?? null,
                'user_id'     => $activeFilters['user_id'] ?? null,
                'stage'       => $activeFilters['stage'] ?? null,
            ]));
        @endphp
        <a href="/reports/export?{{ $exportParams }}" class="btn btn-secondary" download>
            📥 {{ __('app.reports.export_report') }}
        </a>
    </div>
</div>

{{-- ─────────────────────────────────────────────────────────────────────────
     DYNAMIC REPORT FILTERS & PARAMETERS CONTROL DECK
────────────────────────────────────────────────────────────────────────── --}}
<div style="background:#ffffff; border:1px solid var(--border); border-radius:12px; box-shadow:var(--shadow-sm); padding:1.25rem 1.5rem; margin-bottom:1.5rem;">
    <!-- Quick Time Horizon Presets -->
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.75rem; margin-bottom:1.25rem; padding-bottom:1rem; border-bottom:1px solid var(--border-subtle);">
        <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
            <span style="font-size:0.8rem; font-weight:700; color:var(--text-dim); text-transform:uppercase; letter-spacing:0.04em; margin-inline-end:0.25rem;">
                ⏱️ {{ __('app.reports.period') }}:
            </span>
            @php
                $currentPeriod = $activeFilters['period'] ?? 'all';
                $periods = [
                    'all'          => __('app.reports.all_time'),
                    'today'        => __('app.reports.today'),
                    'this_week'    => __('app.reports.this_week'),
                    'this_month'   => __('app.reports.this_month'),
                    'last_30_days' => __('app.reports.last_30_days'),
                    'this_quarter' => __('app.reports.this_quarter'),
                    'this_year'    => __('app.reports.this_year'),
                ];
            @endphp
            @foreach($periods as $pKey => $pLabel)
                @php
                    $isActive = ($currentPeriod === $pKey);
                    $query = array_merge($_GET, ['period' => $pKey, 'date_from' => '', 'date_to' => '']);
                @endphp
                <a href="/reports?{{ http_build_query($query) }}" 
                   class="btn btn-sm {{ $isActive ? 'btn-primary' : 'btn-secondary' }}"
                   style="border-radius:20px; font-size:0.78rem; padding:4px 12px; {{ $isActive ? 'font-weight:700;' : '' }}">
                    {{ $pLabel }}
                </a>
            @endforeach
        </div>

        <!-- Active Scope Summary Pill -->
        <div style="display:flex; align-items:center; gap:0.5rem; font-size:0.82rem; background:var(--bg-main); padding:0.35rem 0.85rem; border-radius:20px; border:1px solid var(--border); color:var(--text-muted);">
            <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--success);"></span>
            <strong>{{ __('app.reports.showing') }}:</strong> {{ $activeScopeSummary }}
        </div>
    </div>

    <!-- Parameter Controls Form -->
    <form method="GET" action="/reports" id="reportFilterForm" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) 120px 80px; gap:0.85rem; align-items:flex-end;">
        <input type="hidden" name="period" value="custom">

        <!-- Date From -->
        <div>
            <label style="display:block; font-size:0.78rem; font-weight:700; color:var(--text-dim); margin-bottom:0.35rem;">
                📅 {{ __('app.reports.date_from') }}
            </label>
            <input type="date" name="date_from" value="{{ $activeFilters['date_from'] }}" 
                   style="width:100%; padding:0.6rem 0.75rem; border:1px solid var(--border); border-radius:8px; font-size:0.85rem; background:var(--bg-main); color:var(--text-main);">
        </div>

        <!-- Date To -->
        <div>
            <label style="display:block; font-size:0.78rem; font-weight:700; color:var(--text-dim); margin-bottom:0.35rem;">
                📅 {{ __('app.reports.date_to') }}
            </label>
            <input type="date" name="date_to" value="{{ $activeFilters['date_to'] }}" 
                   style="width:100%; padding:0.6rem 0.75rem; border:1px solid var(--border); border-radius:8px; font-size:0.85rem; background:var(--bg-main); color:var(--text-main);">
        </div>

        <!-- Pipeline Parameter -->
        <div>
            <label style="display:block; font-size:0.78rem; font-weight:700; color:var(--text-dim); margin-bottom:0.35rem;">
                💼 {{ __('app.reports.pipeline') }}
            </label>
            <select name="pipeline_id" style="width:100%; padding:0.6rem 0.75rem; border:1px solid var(--border); border-radius:8px; font-size:0.85rem; background:var(--bg-main); color:var(--text-main);">
                <option value="">{{ __('app.reports.all_pipelines') }}</option>
                @foreach($pipelines as $p)
                    <option value="{{ $p['id'] }}" @selected($activeFilters['pipeline_id'] == $p['id'])>
                        {{ $p['name'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Team Member Parameter -->
        <div>
            <label style="display:block; font-size:0.78rem; font-weight:700; color:var(--text-dim); margin-bottom:0.35rem;">
                👤 {{ __('app.reports.team_member') }}
            </label>
            <select name="user_id" style="width:100%; padding:0.6rem 0.75rem; border:1px solid var(--border); border-radius:8px; font-size:0.85rem; background:var(--bg-main); color:var(--text-main);">
                <option value="">{{ __('app.reports.all_members') }}</option>
                @foreach($teamMembers as $tm)
                    <option value="{{ $tm['id'] }}" @selected($activeFilters['user_id'] == $tm['id'])>
                        {{ $tm['name'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Deal Stage Parameter -->
        <div>
            <label style="display:block; font-size:0.78rem; font-weight:700; color:var(--text-dim); margin-bottom:0.35rem;">
                🎯 {{ __('app.reports.stage') }}
            </label>
            <select name="stage" style="width:100%; padding:0.6rem 0.75rem; border:1px solid var(--border); border-radius:8px; font-size:0.85rem; background:var(--bg-main); color:var(--text-main);">
                <option value="">{{ __('app.reports.all_stages') }}</option>
                @foreach($stages as $stKey => $stData)
                    <option value="{{ $stKey }}" @selected($activeFilters['stage'] === $stKey)>
                        {{ $stData['label'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Submit Filter -->
        <div>
            <button type="submit" class="btn btn-primary" style="width:100%; padding:0.6rem 0.75rem; font-size:0.85rem; font-weight:600;">
                🔍 {{ __('app.reports.apply_filters') }}
            </button>
        </div>

        <!-- Reset Filter -->
        <div>
            <a href="/reports" class="btn btn-secondary" style="width:100%; padding:0.6rem 0.75rem; font-size:0.85rem; text-align:center;">
                ✕ {{ __('app.reports.reset_filters') }}
            </a>
        </div>
    </form>
</div>

{{-- Top 4 KPI Metric Cards --}}
<div class="metrics-grid" style="margin-bottom:1.5rem;">
    <div class="metric-card metric-card-revenue">
        <span class="metric-label">{{ __('app.reports.won_revenue') }}</span>
        <span class="metric-value">${{ number_format($totalWonValue, 2) }}</span>
        <span class="metric-trend">{{ $totalWonCount }} deals closed won</span>
    </div>

    <div class="metric-card metric-card-pipeline">
        <span class="metric-label">{{ __('app.reports.open_pipeline') }}</span>
        <span class="metric-value">${{ number_format($totalPipelineValue, 2) }}</span>
        <span class="metric-trend">{{ $totalDealsCount - $totalWonCount - $totalLostCount }} active in pipeline</span>
    </div>

    <div class="metric-card metric-card-deals">
        <span class="metric-label">{{ __('app.reports.win_rate') }}</span>
        <span class="metric-value">{{ $winRate }}%</span>
        <span class="metric-trend">{{ $totalWonCount }} won / {{ $totalWonCount + $totalLostCount }} closed</span>
    </div>

    <div class="metric-card metric-card-companies">
        <span class="metric-label">{{ __('app.reports.velocity') }}</span>
        <span class="metric-value">{{ $avgCycleDays }} Days</span>
        <span class="metric-trend">Avg days to win</span>
    </div>
</div>

<div class="detail-layout" style="grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
    {{-- Stage Conversion Funnel --}}
    <div class="detail-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; border-bottom:1px solid var(--border); padding-bottom:0.75rem;">
            <h3 style="font-size:1.1rem; font-weight:700; margin:0; color:var(--text-main);">
                📊 {{ __('app.entities.opportunities.title') }} ({{ $totalDealsCount }})
            </h3>
            <span style="font-size:0.8rem; color:var(--text-dim); font-weight:600;">Conversion Flow</span>
        </div>

        @if($totalDealsCount === 0)
            <div style="text-align:center; padding:2rem; color:var(--text-dim); font-size:0.9rem;">
                {{ __('app.reports.no_data') }}
            </div>
        @else
            <div style="display:flex; flex-direction:column; gap:1rem;">
                @php
                    $maxCount = 1;
                    foreach ($stages as $s) {
                        if (($s['count'] ?? 0) > $maxCount) {
                            $maxCount = $s['count'];
                        }
                    }
                @endphp
                @foreach($stages as $stKey => $st)
                    @php $pct = round(($st['count'] / $maxCount) * 100); @endphp
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.85rem; margin-bottom:0.35rem;">
                            <span style="font-weight:700; color:var(--text-main); display:flex; align-items:center; gap:0.4rem;">
                                <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:{{ $st['color'] }};"></span>
                                {{ $st['label'] }}
                            </span>
                            <span style="font-weight:600; color:var(--text-muted);">
                                {{ $st['count'] }} deals (${{ number_format($st['value'], 0) }})
                            </span>
                        </div>
                        <div style="background:var(--bg-main); border:1px solid var(--border); height:12px; border-radius:6px; overflow:hidden;">
                            <div style="width:{{ max(3, $pct) }}%; height:100%; background:{{ $st['color'] }}; transition:width 0.3s ease;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Activity Communications Deck --}}
    <div class="detail-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; border-bottom:1px solid var(--border); padding-bottom:0.75rem;">
            <h3 style="font-size:1.1rem; font-weight:700; margin:0; color:var(--text-main);">
                📞 {{ __('app.reports.velocity') }} & Activity Breakdown
            </h3>
            <span style="font-size:0.8rem; color:var(--text-dim); font-weight:600;">In Period</span>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
            <div style="background:var(--bg-main); border:1px solid var(--border); padding:1rem; box-shadow:var(--shadow-xs); border-radius:8px;">
                <div style="font-size:1.5rem;">📞</div>
                <div style="font-size:1.5rem; font-weight:700; color:var(--primary); margin:0.25rem 0;">{{ $actBreakdown['call'] ?? 0 }}</div>
                <div style="font-size:0.8rem; font-weight:600; color:var(--text-dim);">Calls Logged</div>
            </div>

            <div style="background:var(--bg-main); border:1px solid var(--border); padding:1rem; box-shadow:var(--shadow-xs); border-radius:8px;">
                <div style="font-size:1.5rem;">📅</div>
                <div style="font-size:1.5rem; font-weight:700; color:#0ea5e9; margin:0.25rem 0;">{{ $actBreakdown['meeting'] ?? 0 }}</div>
                <div style="font-size:0.8rem; font-weight:600; color:var(--text-dim);">Meetings Held</div>
            </div>

            <div style="background:var(--bg-main); border:1px solid var(--border); padding:1rem; box-shadow:var(--shadow-xs); border-radius:8px;">
                <div style="font-size:1.5rem;">✉️</div>
                <div style="font-size:1.5rem; font-weight:700; color:#8b5cf6; margin:0.25rem 0;">{{ $actBreakdown['email'] ?? 0 }}</div>
                <div style="font-size:0.8rem; font-weight:600; color:var(--text-dim);">Emails Dispatched</div>
            </div>

            <div style="background:var(--bg-main); border:1px solid var(--border); padding:1rem; box-shadow:var(--shadow-xs); border-radius:8px;">
                <div style="font-size:1.5rem;">📝</div>
                <div style="font-size:1.5rem; font-weight:700; color:#f59e0b; margin:0.25rem 0;">{{ $actBreakdown['note'] ?? 0 }}</div>
                <div style="font-size:0.8rem; font-weight:600; color:var(--text-dim);">Notes & Logs</div>
            </div>
        </div>
    </div>
</div>

{{-- Team Leaderboard (Filtered) --}}
<div class="detail-card" style="margin-bottom:1.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid var(--border); padding-bottom:0.75rem;">
        <h3 style="font-size:1.1rem; font-weight:700; margin:0; color:var(--text-main);">
            🏆 {{ __('app.settings.workspace.team_members') }} Leaderboard
        </h3>
        <span style="font-size:0.8rem; color:var(--text-dim); font-weight:600;">Ranked by Won Revenue</span>
    </div>

    @if(empty($members))
        <div style="text-align:center; padding:2rem; color:var(--text-dim); font-size:0.9rem;">
            {{ __('app.reports.no_data') }}
        </div>
    @else
        <div class="table-container" style="border:none; background:transparent;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('app.common.name') }}</th>
                        <th>{{ __('app.common.email') }}</th>
                        <th>{{ __('app.entities.opportunities.title') }}</th>
                        <th>Activities</th>
                        <th style="text-align:right;">{{ __('app.reports.won_revenue') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($members as $m)
                        <tr>
                            <td>
                                <div style="font-weight:600; color:var(--text-main); display:flex; align-items:center; gap:0.5rem;">
                                    <div style="width:28px; height:28px; background:linear-gradient(135deg, #4f46e5, #6366f1); color:#fff; display:flex; align-items:center; justify-content:center; font-size:0.8rem; border-radius:6px; box-shadow:0 2px 6px rgba(79,70,229,0.25);">
                                        {{ strtoupper(substr($m['name'], 0, 1)) }}
                                    </div>
                                    {{ $m['name'] }}
                                </div>
                            </td>
                            <td>{{ $m['email'] }}</td>
                            <td><span class="badge badge-primary">{{ $m['total_deals'] }} Deals</span></td>
                            <td><span class="badge badge-secondary">{{ $m['activities_logged'] }} Logs</span></td>
                            <td style="text-align:right; font-weight:800; color:var(--success); font-size:1rem;">
                                ${{ number_format((float)$m['won_revenue'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Filtered Opportunities Breakdown (NEW!) --}}
<div class="detail-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid var(--border); padding-bottom:0.75rem;">
        <div>
            <h3 style="font-size:1.1rem; font-weight:700; margin:0; color:var(--text-main);">
                📋 {{ __('app.reports.filtered_deals') }} (Showing top {{ count($filteredDeals) }} of {{ $totalDealsCount }})
            </h3>
            <span style="font-size:0.8rem; color:var(--text-dim);">Individual opportunities contributing to this report</span>
        </div>
        @if(count($filteredDeals) > 0)
            <a href="/reports/export?{{ $exportParams }}" class="btn btn-secondary btn-sm" download>
                📥 Export CSV
            </a>
        @endif
    </div>

    @if(empty($filteredDeals))
        <div style="text-align:center; padding:2.5rem; color:var(--text-dim);">
            <div style="font-size:2rem; margin-bottom:0.5rem;">🔍</div>
            <div style="font-weight:700; color:var(--text-main); margin-bottom:0.25rem;">{{ __('app.reports.no_data') }}</div>
            <div style="font-size:0.85rem;">Try widening your date range, switching pipeline, or resetting filters.</div>
            <div style="margin-top:1rem;">
                <a href="/reports" class="btn btn-secondary btn-sm">Reset All Filters</a>
            </div>
        </div>
    @else
        <div class="table-container" style="border:none; background:transparent; overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Deal Name</th>
                        <th>Stage</th>
                        <th>Amount</th>
                        <th>Created Date</th>
                        <th>Expected Close</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($filteredDeals as $fd)
                        <tr>
                            <td>
                                <a href="/opportunities/{{ $fd['id'] }}" style="font-weight:600; color:var(--primary); text-decoration:none;">
                                    💼 {{ $fd['title'] ?? $fd['name'] ?? 'Untitled Deal' }}
                                </a>
                            </td>
                            <td>
                                @php
                                    $stObj = $stages[$fd['stage'] ?? 'lead'] ?? null;
                                @endphp
                                <span class="badge" style="background:{{ $stObj['color'] ?? '#64748B' }}15; color:{{ $stObj['color'] ?? '#64748B' }}; border:1px solid {{ $stObj['color'] ?? '#64748B' }}30;">
                                    {{ $stObj['label'] ?? ucfirst($fd['stage'] ?? 'lead') }}
                                </span>
                            </td>
                            <td style="font-weight:700; color:var(--text-main);">
                                ${{ number_format((float)($fd['amount'] ?? 0), 2) }}
                            </td>
                            <td style="font-size:0.85rem; color:var(--text-dim);">
                                {{ !empty($fd['created_at']) ? date('M j, Y', strtotime($fd['created_at'])) : '—' }}
                            </td>
                            <td style="font-size:0.85rem; color:var(--text-dim);">
                                {{ !empty($fd['expected_close']) ? date('M j, Y', strtotime($fd['expected_close'])) : '—' }}
                            </td>
                            <td style="text-align:right;">
                                <a href="/opportunities/{{ $fd['id'] }}" class="btn btn-secondary btn-sm" style="font-size:0.75rem; padding:3px 8px;">
                                    View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
