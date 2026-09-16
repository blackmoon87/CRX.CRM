@extends('layouts.main')

@section('title', __('app.entities.tasks.title'))

@section('content')
@php use App\Helpers\QueryHelper; @endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.entities.tasks.title') }}</h1>
        <p class="page-subtitle">{{ __('app.entities.tasks.subtitle') }}</p>
    </div>
    <div style="display:flex;gap:0.75rem;">
        <a href="/export/tasks" class="btn btn-secondary" download>📤 {{ __('app.common.export_csv') }}</a>
        <a href="/import?entity=tasks" class="btn btn-secondary">📥 {{ __('app.common.import_csv') }}</a>
        <a href="/tasks/create" class="btn btn-primary">+ {{ __('app.entities.tasks.add_new') }}</a>
    </div>
</div>

<div class="detail-layout">
    {{-- Left: Create Task Form --}}
    <div>
        <div class="detail-card">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;border-bottom:1px solid var(--border);padding-bottom:0.5rem;">{{ __('app.entities.tasks.add_new') }}</h3>
            <form method="POST" action="/tasks">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="title">{{ __('app.entities.tasks.task_title') }} *</label>
                    <input type="text" id="title" name="title" class="form-control" required placeholder="e.g. Follow up on Q4 proposal">
                </div>

                <div class="form-group">
                    <label class="form-label" for="priority">{{ __('app.entities.tasks.priority') }}</label>
                    <select id="priority" name="priority" class="form-control">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="due_date">{{ __('app.entities.tasks.due_date') }}</label>
                    <input type="date" id="due_date" name="due_date" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">{{ __('app.common.title') }}</label>
                    <textarea id="description" name="description" class="form-control" placeholder="Context or action steps..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">{{ __('app.entities.tasks.add_new') }}</button>
            </form>
        </div>
    </div>

    {{-- Right: Filter Bar & Task List --}}
    <div>
        {{-- Spartan 3D Saved Views Tabs --}}
        @include('partials.saved_views_tabs', [
            'entityType'  => 'tasks',
            'workspaceId' => $workspace['id'] ?? 1,
        ])

        {{-- Spartan 3D Universal Column Filter Bar --}}
        @include('partials.column_filter_bar', [
            'action'               => '/tasks',
            'allowedColumns'       => $allowedFilterColumns,
            'activeFilters'        => $activeFilters,
            'search'               => $search,
            'sort'                 => $sort,
            'dir'                  => $dir,
            'perPage'              => $perPage,
            'currentFilterCol'     => $currentFilterCol ?? '',
            'currentFilterOp'      => $currentFilterOp ?? 'contains',
            'currentFilterVal'     => $currentFilterVal ?? '',
        ])

        <div class="detail-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:8px;">
                <h3 style="font-size:1rem;font-weight:700;margin:0;">Task List ({{ $pagination['total'] ?? count($tasks) }})</h3>
                <div style="display:flex;gap:6px;font-size:0.75rem;font-weight:700;">
                    <span style="color:var(--text-dim);align-self:center;">Sort:</span>
                    <a href="{{ QueryHelper::sortUrl('due_date', $sort ?? 'due_date', $dir ?? 'ASC') }}" class="btn btn-secondary btn-sm" style="padding:2px 8px;font-size:0.75rem;">
                        Due Date {!! QueryHelper::sortIndicator('due_date', $sort ?? 'due_date', $dir ?? 'ASC') !!}
                    </a>
                    <a href="{{ QueryHelper::sortUrl('priority', $sort ?? 'due_date', $dir ?? 'ASC') }}" class="btn btn-secondary btn-sm" style="padding:2px 8px;font-size:0.75rem;">
                        Priority {!! QueryHelper::sortIndicator('priority', $sort ?? 'due_date', $dir ?? 'ASC') !!}
                    </a>
                </div>
            </div>

            @if(empty($tasks))
                <div class="empty-box">
                    <div class="empty-box-icon">✅</div>
                    <div class="empty-box-title">No Tasks Found</div>
                    <p class="empty-box-desc">All caught up or no tasks matching the selected filter criteria.</p>
                </div>
            @else
                <div style="display:flex;flex-direction:column;gap:12px;">
                    @foreach($tasks as $t)
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:#ffffff;border:1px solid var(--border);border-radius:var(--r-sm);box-shadow:var(--shadow-xs);{{ $t['status'] === 'completed' ? 'opacity:0.75;' : '' }}">
                            <div style="display:flex;align-items:center;gap:14px;min-width:0;flex:1;">
                                <input type="checkbox" class="bulk-row-check" value="{{ $t['id'] }}" title="Select task" style="width:18px;height:18px;cursor:pointer;">
                                <form method="POST" action="/tasks/{{ $t['id'] }}/toggle" style="margin:0;">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary btn-sm" title="Toggle status" style="font-size:13px;padding:3px 8px;">
                                        {{ $t['status'] === 'completed' ? '☑ Done' : '☐ Open' }}
                                    </button>
                                </form>
                                <div style="min-width:0;">
                                    <div style="font-weight:600;font-size:14px;{{ $t['status'] === 'completed' ? 'text-decoration:line-through;color:var(--text-dim);' : 'color:var(--text-main);' }}">
                                        {{ $t['title'] }}
                                    </div>
                                    @if(!empty($t['description']))
                                        <div style="font-size:12.5px;color:var(--text-dim);margin-top:2px;font-weight:400;">{{ $t['description'] }}</div>
                                    @endif
                                    @if(!empty($t['due_date']))
                                        <div style="font-size:12px;color:var(--primary);margin-top:3px;font-weight:600;">📅 Due: {{ $t['due_date'] }}</div>
                                    @endif
                                </div>
                            </div>
                            <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
                                <span class="badge badge-{{ $t['priority'] ?? 'medium' }}">{{ strtoupper($t['priority'] ?? 'medium') }}</span>
                                <form method="POST" action="/tasks/{{ $t['id'] }}/delete" data-confirm="Are you sure you want to delete this task?" style="margin:0;">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm" style="padding:4px 8px;font-size:11px;" title="Delete task">✕</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(!empty($pagination))
                @include('partials.pagination', ['paginator' => $pagination])
            @endif
        </div>
    </div>
</div>

{{-- Spartan 3D Floating Bulk Action Toolbar --}}
@include('partials.bulk_action_bar', [
    'bulkRoute'  => '/tasks/bulk',
    'entityType' => 'tasks',
    'statuses'   => [
        'pending'   => 'Pending',
        'completed' => 'Completed',
    ]
])
<script src="/js/bulk-actions.js"></script>
@endsection

