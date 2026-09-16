@extends('layouts.main')

@section('title', __('app.entities.companies.title'))

@section('content')
@php use App\Helpers\QueryHelper; @endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.entities.companies.title') }}</h1>
        <p class="page-subtitle">{{ __('app.entities.companies.subtitle') }}</p>
    </div>
    <div style="display:flex;gap:0.75rem;">
        @if(user_can('companies.export') || in_array(active_user_role(), ['owner', 'admin'], true))
            <a href="/export/companies" class="btn btn-secondary" download>📤 {{ __('app.common.export_csv') }}</a>
        @endif
        @if(user_can('companies.create') || in_array(active_user_role(), ['owner', 'admin'], true))
            <a href="/import?entity=companies" class="btn btn-secondary">📥 {{ __('app.common.import_csv') }}</a>
            <a href="/companies/create" class="btn btn-primary">+ {{ __('app.entities.companies.add_new') }}</a>
        @endif
    </div>
</div>

{{-- Duplicate Detection & Merge Banner/Modal --}}
@include('partials.duplicate_modal', [
    'entityType' => 'companies',
    'duplicates' => $duplicates ?? [],
])

{{-- Saved Views Tabs --}}
@include('partials.saved_views_tabs', [
    'entityType'  => 'companies',
    'workspaceId' => $workspace['id'] ?? 1,
])

@include('partials.column_filter_bar', [
    'action'               => '/companies',
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

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 36px; text-align: center;">
                    <input type="checkbox" id="select-all-rows" title="{{ __('app.common.select_all') }}">
                </th>
                <th>
                    <a href="{{ QueryHelper::sortUrl('name', $sort ?? 'id', $dir ?? 'DESC') }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;">
                        {{ __('app.entities.companies.name') }} {!! QueryHelper::sortIndicator('name', $sort ?? 'id', $dir ?? 'DESC') !!}
                    </a>
                </th>
                <th>
                    <a href="{{ QueryHelper::sortUrl('domain', $sort ?? 'id', $dir ?? 'DESC') }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;">
                        {{ __('app.entities.companies.domain') }} {!! QueryHelper::sortIndicator('domain', $sort ?? 'id', $dir ?? 'DESC') !!}
                    </a>
                </th>
                <th>
                    <a href="{{ QueryHelper::sortUrl('industry', $sort ?? 'id', $dir ?? 'DESC') }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;">
                        {{ __('app.entities.companies.industry') }} {!! QueryHelper::sortIndicator('industry', $sort ?? 'id', $dir ?? 'DESC') !!}
                    </a>
                </th>
                <th>
                    <a href="{{ QueryHelper::sortUrl('annual_revenue', $sort ?? 'id', $dir ?? 'DESC') }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;">
                        Revenue {!! QueryHelper::sortIndicator('annual_revenue', $sort ?? 'id', $dir ?? 'DESC') !!}
                    </a>
                </th>
                <th>{{ __('app.common.address') }}</th>
                <th>{{ __('app.common.phone') }}</th>
                <th style="text-align:right;">{{ __('app.common.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @if(empty($companies))
                <tr>
                    <td colspan="8" style="text-align:center;padding:2.5rem;color:var(--text-dim);">
                        No companies matching the criteria found. <a href="/companies/create" style="color:var(--primary);font-weight:600;">Add your first company</a>
                    </td>
                </tr>
            @elseif(!empty($groupedCompanies))
                {{-- Grouped View (e.g. Grouped by Country, Industry, Size) --}}
                @foreach($groupedCompanies as $groupName => $groupRows)
                    <tr style="background:#f8fafc;border-left:3px solid var(--primary);">
                        <td colspan="8" style="padding:10px 16px;font-weight:700;color:var(--text-main);">
                            📂 <span>{{ $groupName }}</span>
                            <span class="badge" style="background:#eef2ff;color:var(--primary);margin-left:8px;font-size:0.75rem;padding:2px 8px;border-radius:12px;border:1px solid #c7d2fe;">
                                {{ count($groupRows) }} {{ count($groupRows) === 1 ? 'account' : 'accounts' }}
                            </span>
                        </td>
                    </tr>
                    @foreach($groupRows as $c)
                        @php
                            $locArr = array_filter(array_map('trim', [$c['city'] ?? '', $c['country'] ?? '']));
                        @endphp
                        <tr>
                            <td style="text-align: center;">
                                <input type="checkbox" class="bulk-row-check" value="{{ $c['id'] }}">
                            </td>
                            <td>
                                <a href="/companies/{{ $c['id'] }}" style="font-weight:600;color:var(--primary);">
                                    {{ $c['name'] }}
                                </a>
                            </td>
                            <td class="editable-cell" data-entity="companies" data-id="{{ $c['id'] }}" data-field="domain" data-raw="{{ $c['domain'] ?? '' }}">{{ $c['domain'] ?? '—' }}</td>
                            <td class="editable-cell" data-entity="companies" data-id="{{ $c['id'] }}" data-field="industry" data-raw="{{ $c['industry'] ?? '' }}">{{ $c['industry'] ?? '—' }}</td>
                            <td style="font-weight:600;color:var(--text-main);" class="editable-cell" data-entity="companies" data-id="{{ $c['id'] }}" data-field="annual_revenue" data-type="number" data-raw="{{ $c['annual_revenue'] ?? '' }}">
                                @if(!empty($c['annual_revenue']))
                                    ${{ number_format((float)$c['annual_revenue'], 0) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if(!empty($locArr))
                                    <span class="badge" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;font-size:0.75rem;">
                                        📍 {{ implode(', ', $locArr) }}
                                    </span>
                                @else
                                    <span style="color:var(--text-dim);">—</span>
                                @endif
                            </td>
                            <td class="editable-cell" data-entity="companies" data-id="{{ $c['id'] }}" data-field="phone" data-raw="{{ $c['phone'] ?? '' }}">{{ $c['phone'] ?? '—' }}</td>
                            <td style="text-align:right;">
                                <a href="/companies/{{ $c['id'] }}" class="btn btn-secondary btn-sm">{{ __('app.common.view') ?? 'View' }}</a>
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            @else
                {{-- Standard Flat View --}}
                @foreach($companies as $c)
                    @php
                        $locArr = array_filter(array_map('trim', [$c['city'] ?? '', $c['country'] ?? '']));
                    @endphp
                    <tr>
                        <td style="text-align: center;">
                            <input type="checkbox" class="bulk-row-check" value="{{ $c['id'] }}">
                        </td>
                        <td>
                            <a href="/companies/{{ $c['id'] }}" style="font-weight:600;color:var(--primary);">
                                {{ $c['name'] }}
                            </a>
                        </td>
                        <td class="editable-cell" data-entity="companies" data-id="{{ $c['id'] }}" data-field="domain" data-raw="{{ $c['domain'] ?? '' }}">{{ $c['domain'] ?? '—' }}</td>
                        <td class="editable-cell" data-entity="companies" data-id="{{ $c['id'] }}" data-field="industry" data-raw="{{ $c['industry'] ?? '' }}">{{ $c['industry'] ?? '—' }}</td>
                        <td style="font-weight:600;color:var(--text-main);" class="editable-cell" data-entity="companies" data-id="{{ $c['id'] }}" data-field="annual_revenue" data-type="number" data-raw="{{ $c['annual_revenue'] ?? '' }}">
                            @if(!empty($c['annual_revenue']))
                                ${{ number_format((float)$c['annual_revenue'], 0) }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if(!empty($locArr))
                                <span class="badge" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;font-size:0.75rem;">
                                    📍 {{ implode(', ', $locArr) }}
                                </span>
                            @else
                                <span style="color:var(--text-dim);">—</span>
                            @endif
                        </td>
                        <td class="editable-cell" data-entity="companies" data-id="{{ $c['id'] }}" data-field="phone" data-raw="{{ $c['phone'] ?? '' }}">{{ $c['phone'] ?? '—' }}</td>
                        <td style="text-align:right;">
                            <a href="/companies/{{ $c['id'] }}" class="btn btn-secondary btn-sm">{{ __('app.common.view') ?? 'View' }}</a>
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
    'bulkRoute'  => '/companies/bulk',
    'entityType' => 'companies'
])
<script src="/js/bulk-actions.js"></script>
@endsection
