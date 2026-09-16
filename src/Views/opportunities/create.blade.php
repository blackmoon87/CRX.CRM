@extends('layouts.main')

@section('title', __('app.entities.opportunities.create_title'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.entities.opportunities.create_title') }}</h1>
        <p class="page-subtitle">{{ __('app.entities.opportunities.create_sub') }}</p>
    </div>
    <div>
        <a href="/opportunities" class="btn btn-secondary">&larr; {{ __('app.common.back') }}</a>
    </div>
</div>

<div class="detail-card" style="max-width:800px;">
    <form method="POST" action="/opportunities">
        @csrf
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label" for="name">{{ __('app.entities.opportunities.name') }} *</label>
                <input type="text" id="name" name="name" class="form-control" required placeholder="e.g. Enterprise Migration Contract">
            </div>

            <div class="form-group">
                <label class="form-label" for="amount">{{ __('app.entities.opportunities.amount') }} ($) *</label>
                <input type="number" step="0.01" id="amount" name="amount" class="form-control" required placeholder="50000">
            </div>

            <div class="form-group">
                <label class="form-label" for="currency">{{ __('app.entities.opportunities.currency') }}</label>
                <input type="text" id="currency" name="currency" class="form-control" value="USD">
            </div>

            <div class="form-group">
                <label class="form-label" for="stage">{{ __('app.entities.opportunities.stage') }}</label>
                <select id="stage" name="stage" class="form-control">
                    @foreach($stages as $sKey => $sTitle)
                        <option value="{{ $sKey }}">{{ $sTitle }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="probability">{{ __('app.entities.opportunities.probability') }}</label>
                <input type="number" id="probability" name="probability" class="form-control" value="20" min="0" max="100">
            </div>

            @if(!empty($selectedCompanyId))
                <input type="hidden" name="_redirect" value="/companies/{{ $selectedCompanyId }}">
            @elseif(!empty($selectedPersonId))
                <input type="hidden" name="_redirect" value="/people/{{ $selectedPersonId }}">
            @endif

            <div class="form-group">
                <label class="form-label" for="company_id">{{ __('app.entities.opportunities.company') }}</label>
                <select id="company_id" name="company_id" class="form-control">
                    <option value="">-- {{ __('app.common.none') ?? 'None' }} --</option>
                    @foreach($companies as $c)
                        <option value="{{ $c['id'] }}" @selected(($selectedCompanyId ?? 0) === (int)$c['id'])>{{ $c['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="person_id">{{ __('app.entities.opportunities.contact') }}</label>
                <select id="person_id" name="person_id" class="form-control">
                    <option value="">-- {{ __('app.common.none') ?? 'None' }} --</option>
                    @foreach($people as $p)
                        <option value="{{ $p['id'] }}" @selected(($selectedPersonId ?? 0) === (int)$p['id'])>{{ $p['first_name'] }} {{ $p['last_name'] ?? '' }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label" for="expected_close_date">{{ __('app.entities.opportunities.close_date') }}</label>
                <input type="date" id="expected_close_date" name="expected_close_date" class="form-control">
            </div>
        </div>

        {{-- Dynamic Custom Fields --}}
        @include('partials.custom_fields_form', ['customFields' => $customFields ?? [], 'values' => []])

        <div style="margin-top:1.5rem;display:flex;gap:0.75rem;">
            <button type="submit" class="btn btn-primary">{{ __('app.common.save') }}</button>
            <a href="/opportunities" class="btn btn-secondary">{{ __('app.common.cancel') }}</a>
        </div>
    </form>
</div>
@endsection
