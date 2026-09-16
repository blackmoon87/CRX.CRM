@extends('layouts.main')

@section('title', __('app.nav.overview'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.nav.overview') }}</h1>
        <p class="page-subtitle">{{ __('app.tagline') }}</p>
    </div>
    <div style="display:flex;gap:0.5rem;">
        <a href="/opportunities/create" class="btn btn-primary">+ {{ __('app.entities.opportunities.add_new') }}</a>
        <a href="/tasks" class="btn btn-secondary">{{ __('app.entities.tasks.title') }}</a>
    </div>
</div>

{{-- Top KPI Cards --}}
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-label">{{ __('app.entities.opportunities.title') }}</div>
        <div class="metric-value">${{ number_format($totalPipeline, 0) }}</div>
        <div class="metric-subtitle">{{ $dealsCount }} {{ __('app.entities.opportunities.title') }}</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">{{ __('app.entities.opportunities.stage') }} (Won)</div>
        <div class="metric-value">${{ number_format($wonTotal, 0) }}</div>
        <div class="metric-subtitle">{{ $stageCounts['closed_won'] ?? 0 }}</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">{{ __('app.entities.companies.title') }}</div>
        <div class="metric-value">{{ $companiesCount }}</div>
        <div class="metric-subtitle">{{ __('app.entities.companies.subtitle') }}</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">{{ __('app.entities.people.title') }}</div>
        <div class="metric-value">{{ $peopleCount }}</div>
        <div class="metric-subtitle">{{ __('app.entities.people.subtitle') }}</div>
    </div>
</div>

{{-- Pipeline Progression Section --}}
<div class="detail-card">
    <h3 style="font-size:18px;font-weight:800;color:var(--ink);margin:0 0 16px 0;">Deal Flow by Stage</h3>
    <div class="stage-pills-grid">
        @foreach($stages as $key => $title)
            <div class="stage-pill-block">
                <div class="stage-pill-title">{{ $title }}</div>
                <div class="stage-pill-count">
                    {{ $stageCounts[$key] ?? 0 }}
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- Two-column section: Tasks & Recent Activity --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(min(100%, 450px), 1fr));gap:1.5rem;">
    {{-- Pending Tasks --}}
    <div class="detail-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <h3 style="font-size:1.05rem;font-weight:700;">Open Tasks</h3>
            <a href="/tasks" style="font-size:0.8rem;color:var(--primary);font-weight:600;">View All &rarr;</a>
        </div>

        @if(empty($tasks))
            <p style="color:var(--text-dim);font-size:0.88rem;">No open tasks. Enjoy your day!</p>
        @else
            <div style="display:flex;flex-direction:column;gap:10px;">
                @foreach($tasks as $t)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#FFFFFF;border:1px solid var(--border);border-radius:var(--r-sm);box-shadow:var(--shadow-xs);">
                        <div style="min-width:0;flex:1;">
                            <div style="font-weight:600;font-size:14px;color:var(--text-main);">{{ $t['title'] }}</div>
                            @if(!empty($t['due_date']))
                                <div style="font-size:12px;color:var(--primary);margin-top:2px;font-weight:600;">📅 Due: {{ $t['due_date'] }}</div>
                            @endif
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span class="badge badge-{{ $t['priority'] }}">{{ strtoupper($t['priority']) }}</span>
                            <form method="POST" action="/tasks/{{ $t['id'] }}/toggle" style="margin:0;">
                                @csrf
                                <button type="submit" class="btn btn-secondary btn-sm" style="padding:4px 10px;font-weight:800;" title="Mark Done">✓</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Activity Feed --}}
    <div class="detail-card">
        <h3 style="font-size:1.05rem;font-weight:700;margin-bottom:1rem;">Recent Workspace Activity</h3>
        @if(empty($recentActivities))
            <p style="color:var(--text-dim);font-size:0.88rem;">No activity logged yet.</p>
        @else
            <div class="activity-feed">
                @foreach($recentActivities as $act)
                    <div class="activity-item">
                        <div class="activity-icon">⚡</div>
                        <div class="activity-content">
                            <div class="activity-desc">{{ $act['description'] }}</div>
                            <div class="activity-time">{{ $act['created_at'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
