@extends('layouts.main')

@section('title', __('app.entities.people.title'))

@section('content')
@php use App\Helpers\QueryHelper; @endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.entities.people.title') }}</h1>
        <p class="page-subtitle">{{ __('app.entities.people.subtitle') }}</p>
    </div>
    <div style="display:flex;gap:0.75rem;">
        @if(user_can('contacts.export') || in_array(active_user_role(), ['owner', 'admin'], true))
            <a href="/export/people" class="btn btn-secondary" download>📤 {{ __('app.common.export_csv') }}</a>
        @endif
        @if(user_can('contacts.create') || in_array(active_user_role(), ['owner', 'admin'], true))
            <a href="/import?entity=people" class="btn btn-secondary">📥 {{ __('app.common.import_csv') }}</a>
            <a href="/people/create" class="btn btn-primary">+ {{ __('app.entities.people.add_new') }}</a>
        @endif
    </div>
</div>

{{-- Duplicate Detection & Merge Banner/Modal --}}
@include('partials.duplicate_modal', [
    'entityType' => 'people',
    'duplicates' => $duplicates ?? [],
])

{{-- Saved Views Tabs --}}
@include('partials.saved_views_tabs', [
    'entityType'  => 'people',
    'workspaceId' => $workspace['id'] ?? 1,
])

{{-- Universal Column Filter Bar --}}
@include('partials.column_filter_bar', [
    'action'               => '/people',
    'allowedColumns'       => $allowedFilterColumns,
    'activeFilters'        => $activeFilters,
    'search'               => $search,
    'sort'                 => $sort,
    'dir'                  => $dir,
    'perPage'              => $perPage,
    'currentFilterCol'     => $currentFilterCol ?? '',
    'currentFilterOp'      => $currentFilterOp ?? 'contains',
    'currentFilterVal'     => $currentFilterVal ?? '',
    'extraHidden'          => !empty($companyId) ? ['company_id' => $companyId] : [],
])

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 36px; text-align: center;">
                    <input type="checkbox" id="select-all-rows" title="{{ __('app.common.select_all') }}">
                </th>
                <th>
                    <a href="{{ QueryHelper::sortUrl('first_name', $sort ?? 'id', $dir ?? 'DESC') }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;">
                        {{ __('app.entities.people.name') }} {!! QueryHelper::sortIndicator('first_name', $sort ?? 'id', $dir ?? 'DESC') !!}
                    </a>
                </th>
                <th>{{ __('app.entities.people.company') }}</th>
                <th>
                    <a href="{{ QueryHelper::sortUrl('job_title', $sort ?? 'id', $dir ?? 'DESC') }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;">
                        {{ __('app.entities.people.title_role') }} {!! QueryHelper::sortIndicator('job_title', $sort ?? 'id', $dir ?? 'DESC') !!}
                    </a>
                </th>
                <th>
                    <a href="{{ QueryHelper::sortUrl('email', $sort ?? 'id', $dir ?? 'DESC') }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;">
                        {{ __('app.common.email') }} {!! QueryHelper::sortIndicator('email', $sort ?? 'id', $dir ?? 'DESC') !!}
                    </a>
                </th>
                <th>{{ __('app.common.phone') }}</th>
                <th>{{ __('app.common.address') }}</th>
                <th>{{ __('app.common.status') }}</th>
                <th style="text-align:right;">{{ __('app.common.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @if(empty($people))
                <tr>
                    <td colspan="9" style="text-align:center;padding:2.5rem;color:var(--text-dim);">
                        No contacts found matching the criteria. <a href="/people/create" style="color:var(--primary);font-weight:600;">Add your first contact</a>
                    </td>
                </tr>
            @elseif(!empty($groupedPeople))
                {{-- Grouped View (e.g. Grouped by Country, State, City, Status, Company) --}}
                @foreach($groupedPeople as $groupName => $groupRows)
                    <tr style="background:#f8fafc;border-left:3px solid var(--primary);">
                        <td colspan="9" style="padding:10px 16px;font-weight:700;color:var(--text-main);">
                            📂 <span>{{ $groupName }}</span>
                            <span class="badge" style="background:#eef2ff;color:var(--primary);margin-left:8px;font-size:0.75rem;padding:2px 8px;border-radius:12px;border:1px solid #c7d2fe;">
                                {{ count($groupRows) }} {{ count($groupRows) === 1 ? 'contact' : 'contacts' }}
                            </span>
                        </td>
                    </tr>
                    @foreach($groupRows as $p)
                        @php
                            $locArr = array_filter(array_map('trim', [$p['city'] ?? '', $p['state'] ?? '', $p['country'] ?? '']));
                        @endphp
                        <tr>
                            <td style="text-align: center;">
                                <input type="checkbox" class="bulk-row-check" value="{{ $p['id'] }}">
                            </td>
                            <td>
                                <a href="/people/{{ $p['id'] }}" style="font-weight:600;color:var(--primary);">
                                    {{ $p['first_name'] }} {{ $p['last_name'] ?? '' }}
                                </a>
                            </td>
                            <td>
                                @if(!empty($p['company_name']))
                                    <a href="/companies/{{ $p['company_id'] }}" style="color:var(--text-main);font-weight:500;">
                                        🏢 {{ $p['company_name'] }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="editable-cell" data-entity="people" data-id="{{ $p['id'] }}" data-field="job_title" data-raw="{{ $p['job_title'] ?? '' }}">{{ $p['job_title'] ?? '—' }}</td>
                            <td>
                                @if(!empty($p['email']))
                                    <a href="mailto:{{ $p['email'] }}" style="color:var(--primary);">{{ $p['email'] }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="editable-cell" data-entity="people" data-id="{{ $p['id'] }}" data-field="phone" data-raw="{{ $p['phone'] ?? '' }}">{{ $p['phone'] ?? '—' }}</td>
                            <td>
                                @if(!empty($locArr))
                                    <span class="badge" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;font-size:0.75rem;">
                                        📍 {{ implode(', ', $locArr) }}
                                    </span>
                                @else
                                    <span style="color:var(--text-dim);">—</span>
                                @endif
                            </td>
                            <td class="editable-cell" data-entity="people" data-id="{{ $p['id'] }}" data-field="status" data-type="select" data-options='{"lead":"Lead","contact":"Contact","customer":"Customer","churned":"Churned"}' data-raw="{{ $p['status'] ?? 'lead' }}">
                                <span class="badge badge-{{ $p['status'] ?? 'lead' }}">{{ ucfirst($p['status'] ?? 'lead') }}</span>
                            </td>
                            <td style="text-align:right;">
                                <a href="/people/{{ $p['id'] }}" class="btn btn-secondary btn-sm">{{ __('app.common.view') ?? 'View' }}</a>
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            @else
                @foreach($people as $p)
                    @php
                        $locArr = array_filter(array_map('trim', [$p['city'] ?? '', $p['state'] ?? '', $p['country'] ?? '']));
                    @endphp
                    <tr>
                        <td style="text-align: center;">
                            <input type="checkbox" class="bulk-row-check" value="{{ $p['id'] }}">
                        </td>
                        <td>
                            <a href="/people/{{ $p['id'] }}" style="font-weight:600;color:var(--primary);">
                                {{ $p['first_name'] }} {{ $p['last_name'] ?? '' }}
                            </a>
                        </td>
                        <td>
                            @if(!empty($p['company_name']))
                                <a href="/companies/{{ $p['company_id'] }}" style="color:var(--text-main);font-weight:500;">
                                    🏢 {{ $p['company_name'] }}
                                </a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="editable-cell" data-entity="people" data-id="{{ $p['id'] }}" data-field="job_title" data-raw="{{ $p['job_title'] ?? '' }}">{{ $p['job_title'] ?? '—' }}</td>
                        <td>
                            @if(!empty($p['email']))
                                <a href="mailto:{{ $p['email'] }}" style="color:var(--primary);">{{ $p['email'] }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="editable-cell" data-entity="people" data-id="{{ $p['id'] }}" data-field="phone" data-raw="{{ $p['phone'] ?? '' }}">{{ $p['phone'] ?? '—' }}</td>
                        <td>
                            @if(!empty($locArr))
                                <span class="badge" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;font-size:0.75rem;">
                                    📍 {{ implode(', ', $locArr) }}
                                </span>
                            @else
                                <span style="color:var(--text-dim);">—</span>
                            @endif
                        </td>
                        <td class="editable-cell" data-entity="people" data-id="{{ $p['id'] }}" data-field="status" data-type="select" data-options='{"lead":"Lead","contact":"Contact","customer":"Customer","churned":"Churned"}' data-raw="{{ $p['status'] ?? 'lead' }}">
                            <span class="badge badge-{{ $p['status'] ?? 'lead' }}">{{ ucfirst($p['status'] ?? 'lead') }}</span>
                        </td>
                        <td style="text-align:right;">
                            <a href="/people/{{ $p['id'] }}" class="btn btn-secondary btn-sm">{{ __('app.common.view') ?? 'View' }}</a>
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
    'bulkRoute'  => '/people/bulk',
    'entityType' => 'people',
    'statuses'   => [
        'lead'     => 'Lead',
        'contact'  => 'Contact',
        'customer' => 'Customer',
        'churned'  => 'Churned',
    ]
])
<script src="/js/bulk-actions.js"></script>
@endsection
