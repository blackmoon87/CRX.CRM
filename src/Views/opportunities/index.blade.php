@extends('layouts.main')

@section('title', __('app.entities.opportunities.title'))

@section('content')
@php use App\Helpers\QueryHelper; @endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.entities.opportunities.title') }}</h1>
        <p class="page-subtitle">{{ __('app.entities.opportunities.subtitle') }}</p>
    </div>
    <div style="display:flex;gap:0.75rem;align-items:center;">
        @if(user_can('deals.export') || in_array(active_user_role(), ['owner', 'admin'], true))
            <a href="/export/opportunities" class="btn btn-secondary btn-sm" download>📤 {{ __('app.common.export_csv') }}</a>
        @endif
        @if(user_can('deals.create') || in_array(active_user_role(), ['owner', 'admin'], true))
            <a href="/import?entity=opportunities" class="btn btn-secondary btn-sm">📥 {{ __('app.common.import_csv') }}</a>
        @endif
        <div style="display:flex;background:#ffffff;border:1px solid var(--border);border-radius:var(--r-sm);overflow:hidden;box-shadow:var(--shadow-xs);">
            <a href="/opportunities?view=kanban&pipeline_id={{ $activePipeId }}" class="btn btn-sm {{ $viewMode === 'kanban' ? 'btn-primary' : 'btn-secondary' }}" style="border-radius:0;border:none;">Kanban</a>
            <a href="/opportunities?view=table&pipeline_id={{ $activePipeId }}" class="btn btn-sm {{ $viewMode === 'table' ? 'btn-primary' : 'btn-secondary' }}" style="border-radius:0;border:none;">Table</a>
        </div>
        @if(user_can('deals.create') || in_array(active_user_role(), ['owner', 'admin'], true))
            <a href="/opportunities/create" class="btn btn-primary">+ {{ __('app.entities.opportunities.add_new') }}</a>
        @endif
    </div>
</div>

{{-- Pipeline Selector Tabs & Forecasting Bar --}}
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;flex-wrap:wrap;gap:1rem;">
    {{-- Pipeline Tabs --}}
    <div style="display:flex;gap:0.5rem;align-items:center;">
        <span style="font-size:0.85rem;font-weight:700;color:var(--text-muted);margin-right:0.25rem;">Pipeline:</span>
        @foreach($pipelines as $p)
            <a href="/opportunities?pipeline_id={{ $p['id'] }}&view={{ $viewMode }}" 
               class="btn btn-sm {{ $activePipeId == $p['id'] ? 'btn-primary' : 'btn-secondary' }}"
               style="font-size:0.8rem;padding:4px 12px;">
                {{ $p['name'] }}
            </a>
        @endforeach
    </div>

    {{-- Forecast & Rotting Badges --}}
    <div style="display:flex;gap:0.75rem;align-items:center;">
        <div style="background:#ffffff;border:1px solid var(--border);padding:6px 12px;border-radius:var(--r-sm);box-shadow:var(--shadow-xs);font-size:0.85rem;">
            <span style="color:var(--text-dim);font-weight:600;">Nominal:</span>
            <strong style="color:var(--text-main);margin-left:4px;">${{ number_format($nominalTotal, 0) }}</strong>
        </div>

        <div style="background:#ecfdf5;border:1px solid #a7f3d0;padding:6px 12px;border-radius:var(--r-sm);box-shadow:var(--shadow-xs);font-size:0.85rem;">
            <span style="color:#047857;font-weight:600;">Weighted Forecast:</span>
            <strong style="color:#047857;margin-left:4px;">${{ number_format($weightedTotal, 0) }}</strong>
        </div>

        @if($rottingCount > 0)
            <div style="background:#fef2f2;border:1px solid #fecaca;color:var(--danger);padding:6px 12px;border-radius:var(--r-sm);box-shadow:var(--shadow-xs);font-size:0.85rem;font-weight:600;">
                ⏳ {{ $rottingCount }} Stale Deals
            </div>
        @endif
    </div>
</div>

@if($viewMode === 'kanban')
    {{-- Kanban Board View --}}
    <div class="kanban-board">
        @foreach($columns as $colKey => $col)
            <div class="kanban-column">
                <div class="column-header">
                    <div class="column-title-group">
                        <span class="column-title">{{ $col['title'] }}</span>
                        <span class="badge" style="background:#eef2ff;color:var(--primary);border:1px solid #c7d2fe;font-size:0.7rem;padding:2px 6px;">{{ $col['probability'] }}%</span>
                    </div>
                    <span class="column-sum">${{ number_format($col['total'], 0) }}</span>
                </div>

                <div class="column-body" data-stage="{{ $colKey }}">
                    @foreach($col['deals'] as $deal)
                        <div class="deal-card {{ $deal['is_rotting'] ? 'deal-card-rotting' : '' }}" draggable="true" data-id="{{ $deal['id'] }}" onclick="window.location='/opportunities/{{ $deal['id'] }}'" style="{{ $deal['is_rotting'] ? 'border: 1px solid var(--danger);' : '' }}">
                            @if($deal['is_rotting'])
                                <div style="display:inline-block;background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;font-size:0.7rem;font-weight:700;padding:2px 6px;border-radius:4px;margin-bottom:6px;">
                                    ⏳ Stale ({{ $deal['rotting_days'] }}d inactive)
                                </div>
                            @endif

                            <div class="deal-name">{{ $deal['name'] }}</div>
                            <div class="deal-amount">${{ number_format((float)$deal['amount'], 0) }}</div>

                            <div class="deal-meta" style="display: flex; align-items: center; justify-content: space-between; gap: 6px; flex-wrap: wrap;">
                                <span>{{ $deal['company_name'] ?? 'Direct' }}</span>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span style="font-weight:600;color:var(--primary);">{{ $deal['probability'] }}%</span>
                                    @if(!empty($deal['health_badge']))
                                        <span style="font-size:10px;font-weight:700;padding:2px 6px;background:var(--bg);border:1px solid var(--border);border-radius:3px;">
                                            {{ $deal['health_badge'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
    @include('partials.deal_loss_modal')

@else
    {{-- Table View --}}
    @include('partials.saved_views_tabs', [
        'entityType'  => 'opportunities',
        'workspaceId' => $workspace['id'] ?? 1,
    ])

    @include('partials.column_filter_bar', [
        'action'               => '/opportunities',
        'allowedColumns'       => $allowedFilterColumns,
        'activeFilters'        => $activeFilters,
        'search'               => $search,
        'sort'                 => $sort,
        'dir'                  => $dir,
        'perPage'              => $perPage,
        'currentFilterCol'     => $currentFilterCol ?? '',
        'currentFilterOp'      => $currentFilterOp ?? 'contains',
        'currentFilterVal'     => $currentFilterVal ?? '',
        'extraHidden'          => ['view' => 'table', 'pipeline_id' => $activePipeId],
    ])

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 36px; text-align: center;">
                        <input type="checkbox" id="select-all-rows" title="Select all rows">
                    </th>
                    <th>
                        <a href="{{ QueryHelper::sortUrl('name', $sort ?? 'id', $dir ?? 'DESC') }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;">
                            Deal Name {!! QueryHelper::sortIndicator('name', $sort ?? 'id', $dir ?? 'DESC') !!}
                        </a>
                    </th>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>
                        <a href="{{ QueryHelper::sortUrl('amount', $sort ?? 'id', $dir ?? 'DESC') }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;">
                            Amount {!! QueryHelper::sortIndicator('amount', $sort ?? 'id', $dir ?? 'DESC') !!}
                        </a>
                    </th>
                    <th>
                        <a href="{{ QueryHelper::sortUrl('stage', $sort ?? 'id', $dir ?? 'DESC') }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;">
                            Stage {!! QueryHelper::sortIndicator('stage', $sort ?? 'id', $dir ?? 'DESC') !!}
                        </a>
                    </th>
                    <th>
                        <a href="{{ QueryHelper::sortUrl('probability', $sort ?? 'id', $dir ?? 'DESC') }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;">
                            Probability {!! QueryHelper::sortIndicator('probability', $sort ?? 'id', $dir ?? 'DESC') !!}
                        </a>
                    </th>
                    <th>
                        <a href="{{ QueryHelper::sortUrl('expected_close_date', $sort ?? 'id', $dir ?? 'DESC') }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;">
                            Expected Close {!! QueryHelper::sortIndicator('expected_close_date', $sort ?? 'id', $dir ?? 'DESC') !!}
                        </a>
                    </th>
                    <th>Health</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @php $dealsToDisplay = !empty($tableDeals) ? $tableDeals : $allDeals; @endphp
                @if(empty($dealsToDisplay))
                    <tr>
                        <td colspan="10" style="text-align:center;padding:2.5rem;color:var(--text-dim);">
                            No deals found matching the criteria in this pipeline. <a href="/opportunities/create" style="color:var(--primary);font-weight:600;">Open a new deal</a>
                        </td>
                    </tr>
                @else
                    @foreach($dealsToDisplay as $d)
                        <tr>
                            <td style="text-align: center;">
                                <input type="checkbox" class="bulk-row-check" value="{{ $d['id'] }}">
                            </td>
                            <td>
                                <a href="/opportunities/{{ $d['id'] }}" style="font-weight:600;color:var(--primary);">
                                    {{ $d['name'] }}
                                </a>
                            </td>
                            <td>{{ $d['company_name'] ?? '—' }}</td>
                            <td>
                                @if(!empty($d['person_first_name']))
                                    {{ $d['person_first_name'] }} {{ $d['person_last_name'] ?? '' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td style="font-weight:700;color:var(--success);" class="editable-cell" data-entity="opportunities" data-id="{{ $d['id'] }}" data-field="amount" data-type="number" data-raw="{{ $d['amount'] }}">${{ number_format((float)$d['amount'], 0) }}</td>
                            <td class="editable-cell" data-entity="opportunities" data-id="{{ $d['id'] }}" data-field="stage" data-type="select" data-options='{"lead":"Lead","meeting":"Meeting","proposal":"Proposal","negotiation":"Negotiation","closed_won":"Closed Won","closed_lost":"Closed Lost"}' data-raw="{{ $d['stage'] }}"><span class="badge badge-{{ $d['stage'] }}">{{ str_replace('_', ' ', $d['stage']) }}</span></td>
                            <td class="editable-cell" data-entity="opportunities" data-id="{{ $d['id'] }}" data-field="probability" data-type="number" data-raw="{{ $d['probability'] }}">{{ $d['probability'] }}%</td>
                            <td class="editable-cell" data-entity="opportunities" data-id="{{ $d['id'] }}" data-field="expected_close_date" data-raw="{{ $d['expected_close_date'] ?? '' }}">{{ $d['expected_close_date'] ?? '—' }}</td>
                            <td>
                                @if(!empty($d['health_badge']))
                                    <span class="badge badge-{{ ($d['health_status'] ?? '') === 'critical' ? 'danger' : (($d['health_status'] ?? '') === 'at_risk' ? 'warning' : 'success') }}">
                                        {{ $d['health_badge'] }}
                                    </span>
                                @elseif(!empty($d['is_rotting']))
                                    <span class="badge badge-danger">⏳ Stale ({{ $d['rotting_days'] }}d)</span>
                                @else
                                    <span class="badge badge-success">Active</span>
                                @endif
                            </td>

                            <td style="text-align:right;">
                                <a href="/opportunities/{{ $d['id'] }}" class="btn btn-secondary btn-sm">{{ __('app.common.view') ?? 'View' }}</a>
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    @if(!empty($pagination))
        @include('partials.pagination', ['paginator' => $pagination])
    @endif

    {{-- Floating Bulk Action Toolbar --}}
    @include('partials.bulk_action_bar', [
        'bulkRoute'  => '/opportunities/bulk',
        'entityType' => 'opportunities',
        'statuses'   => [
            'lead'        => 'Lead',
            'meeting'     => 'Meeting',
            'proposal'    => 'Proposal',
            'negotiation' => 'Negotiation',
            'closed_won'  => 'Closed Won',
            'closed_lost' => 'Closed Lost',
        ]
    ])
    <script src="/js/bulk-actions.js"></script>
@endif
@endsection
