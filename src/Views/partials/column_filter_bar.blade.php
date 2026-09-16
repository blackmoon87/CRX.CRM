@php
    use App\Helpers\QueryHelper;
    $groupBy = $groupBy ?? ($_GET['group_by'] ?? '');
    $hasActiveFilters = !empty($search) || !empty($activeFilters) || (!empty($sort) && $sort !== 'id' && $sort !== 'due_date');
@endphp

<div style="background:var(--bg-surface);border:1px solid var(--border);box-shadow:var(--shadow-xs);border-radius:10px;padding:12px 16px;margin-bottom:1.25rem;">
    {{-- Main Filter Row --}}
    <form method="GET" action="{{ $action }}" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <input type="hidden" name="sort" value="{{ $sort ?? 'id' }}">
        <input type="hidden" name="dir" value="{{ strtolower($dir ?? 'desc') }}">
        <input type="hidden" name="per_page" value="{{ $perPage ?? 15 }}">
        @if(!empty($extraHidden))
            @foreach($extraHidden as $hk => $hv)
                <input type="hidden" name="{{ $hk }}" value="{{ $hv }}">
            @endforeach
        @endif
        @if(!empty($activeFilters))
            @foreach($activeFilters as $afKey => $af)
                <input type="hidden" name="filter[{{ $af['filter_key'] }}]" value="{{ $af['value'] }}">
            @endforeach
        @endif

        {{-- Unified Column Filter Selector --}}
        <div style="display:flex;align-items:center;gap:8px;flex:1;min-width:300px;flex-wrap:wrap;">
            <div style="min-width:180px;">
                <select name="filter_col" class="form-control" style="font-weight:600;cursor:pointer;">
                    <option value="all" @selected(($currentFilterCol ?? 'all') === 'all' || empty($currentFilterCol))>🔍 {{ __('app.filter_bar.all_records') }}</option>
                    @foreach($allowedColumns as $colKey => $colMeta)
                        <option value="{{ $colKey }}" @selected(($currentFilterCol ?? '') === $colKey)>
                            {{ $colMeta['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="min-width:130px;">
                <select name="filter_op" class="form-control" style="font-weight:500;cursor:pointer;">
                    <option value="contains" @selected(($currentFilterOp ?? 'contains') === 'contains')>{{ __('app.filter_bar.contains') }}</option>
                    <option value="equals" @selected(($currentFilterOp ?? '') === 'equals')>{{ __('app.filter_bar.equals') }} (=)</option>
                    <option value="starts_with" @selected(($currentFilterOp ?? '') === 'starts_with')>{{ __('app.filter_bar.starts_with') }}</option>
                    <option value="gte" @selected(($currentFilterOp ?? '') === 'gte')>&ge;</option>
                    <option value="lte" @selected(($currentFilterOp ?? '') === 'lte')>&le;</option>
                </select>
            </div>

            <div style="flex:1;min-width:200px;">
                <input type="text" name="filter_val" class="form-control" placeholder="{{ __('app.filter_bar.filter_value') }}" value="{{ $currentFilterVal ?: ($search ?? '') }}">
            </div>
        </div>

        {{-- Dynamic Group By Dropdown --}}
        <div style="min-width:160px;">
            <select name="group_by" class="form-control" style="font-weight:600;cursor:pointer;background:var(--bg-main);" onchange="this.form.submit()">
                <option value="">📂 {{ __('app.filter_bar.no_grouping') }}</option>
                <option value="tags" @selected(($groupBy ?? '') === 'tags')>🏷️ {{ __('app.filter_bar.by_tags') }}</option>
                @if(isset($allowedColumns['country']))
                    <option value="country" @selected(($groupBy ?? '') === 'country')>🌍 {{ __('app.filter_bar.by_country') }}</option>
                @endif
                @if(isset($allowedColumns['state']))
                    <option value="state" @selected(($groupBy ?? '') === 'state')>📍 {{ __('app.filter_bar.by_state') }}</option>
                @endif
                @if(isset($allowedColumns['city']))
                    <option value="city" @selected(($groupBy ?? '') === 'city')>🏙️ {{ __('app.filter_bar.by_city') }}</option>
                @endif
                @if(isset($allowedColumns['industry']))
                    <option value="industry" @selected(($groupBy ?? '') === 'industry')>🏢 {{ __('app.filter_bar.by_industry') }}</option>
                @endif
                @if(isset($allowedColumns['status']))
                    <option value="status" @selected(($groupBy ?? '') === 'status')>🚦 {{ __('app.filter_bar.by_status') }}</option>
                @endif
                @if(isset($allowedColumns['size']))
                    <option value="size" @selected(($groupBy ?? '') === 'size')>👥 {{ __('app.filter_bar.by_size') }}</option>
                @endif
                @if(str_contains($action ?? '', 'people'))
                    <option value="company_name" @selected(($groupBy ?? '') === 'company_name')>🏢 {{ __('app.filter_bar.by_company') }}</option>
                @endif
            </select>
        </div>

        <button type="submit" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;">
            🔍 {{ __('app.common.filter') }}
        </button>

        @if($hasActiveFilters || !empty($currentFilterVal) || !empty($search) || !empty($groupBy))
            <a href="{{ $action }}{{ !empty($extraHidden) ? '?' . http_build_query($extraHidden) : '' }}" class="btn btn-secondary" title="Clear all search criteria and filters">
                ✕ {{ __('app.common.reset') }}
            </a>
        @endif
    </form>

    {{-- Active Filter Chips Bar --}}
    @if(!empty($activeFilters))
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:10px;padding-top:10px;border-top:1px dashed var(--border);">
            <span style="font-size:0.75rem;font-weight:700;color:var(--text-dim);text-transform:uppercase;">Active Column Filters:</span>
            @foreach($activeFilters as $afKey => $af)
                <span class="badge" style="background:#ffffff;border:1px solid var(--border);color:var(--text-main);font-size:0.75rem;padding:4px 8px;display:inline-flex;align-items:center;gap:6px;">
                    <strong style="color:var(--primary);">{{ $af['label'] }}</strong>
                    <span style="color:var(--text-dim);">{{ $af['operator'] }}</span>
                    <span style="font-weight:700;">"{{ $af['value'] }}"</span>
                    <a href="{{ $action }}{{ QueryHelper::removeFilterUrl($af['filter_key']) }}" style="color:var(--danger);text-decoration:none;font-weight:800;margin-left:2px;" title="Remove this filter">✕</a>
                </span>
            @endforeach
        </div>
    @endif
</div>
