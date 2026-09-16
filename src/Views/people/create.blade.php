@extends('layouts.main')

@section('title', __('app.entities.people.create_title'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.entities.people.create_title') }}</h1>
        <p class="page-subtitle">{{ __('app.entities.people.create_sub') }}</p>
    </div>
    <div>
        <a href="/people" class="btn btn-secondary">&larr; {{ __('app.common.back') }}</a>
    </div>
</div>

<div class="detail-card" style="max-width:800px;">
    <form method="POST" action="/people">
        @csrf
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="form-group">
                <label class="form-label" for="first_name">{{ __('app.entities.people.first_name') }} *</label>
                <input type="text" id="first_name" name="first_name" class="form-control" required placeholder="Patrick">
            </div>

            <div class="form-group">
                <label class="form-label" for="last_name">{{ __('app.entities.people.last_name') }}</label>
                <input type="text" id="last_name" name="last_name" class="form-control" placeholder="Collison">
            </div>

            @if(!empty($selectedCompanyId))
                <input type="hidden" name="_redirect" value="/companies/{{ $selectedCompanyId }}">
            @endif

            <div class="form-group">
                <label class="form-label" for="company_id">{{ __('app.entities.people.company') }}</label>
                <select id="company_id" name="company_id" class="form-control">
                    <option value="">-- {{ __('app.common.none') ?? 'None' }} --</option>
                    @foreach($companies as $comp)
                        <option value="{{ $comp['id'] }}" @selected(($selectedCompanyId ?? 0) === (int)$comp['id'])>{{ $comp['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="status">{{ __('app.entities.people.status') }}</label>
                <select id="status" name="status" class="form-control">
                    <option value="lead">Lead</option>
                    <option value="qualified">Qualified Prospect</option>
                    <option value="customer">Customer</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="job_title">{{ __('app.entities.people.title_role') }}</label>
                <input type="text" id="job_title" name="job_title" class="form-control" placeholder="VP Engineering">
            </div>

            <div class="form-group">
                <label class="form-label" for="email">{{ __('app.entities.people.email') }}</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="patrick@company.com">
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label" for="phone">{{ __('app.entities.people.phone') }}</label>
                <input type="text" id="phone" name="phone" class="form-control" placeholder="+1-415-555-0144">
            </div>

            {{-- Address & Location --}}
            <div class="form-group" style="grid-column: span 2; margin-top:0.5rem; padding-top:0.75rem; border-top:1px dashed var(--border);">
                <span style="font-size:0.85rem;font-weight:700;color:var(--text-main);text-transform:uppercase;letter-spacing:0.5px;">📍 {{ __('app.common.address') }}</span>
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label" for="address">{{ __('app.entities.people.address') }}</label>
                <input type="text" id="address" name="address" class="form-control" placeholder="742 Evergreen Terrace">
            </div>

            <div class="form-group">
                <label class="form-label" for="city">{{ __('app.entities.people.city') }}</label>
                <input type="text" id="city" name="city" class="form-control" placeholder="Springfield">
            </div>

            <div class="form-group">
                <label class="form-label" for="state">{{ __('app.entities.people.state') }}</label>
                <input type="text" id="state" name="state" class="form-control" placeholder="Oregon">
            </div>

            <div class="form-group">
                <label class="form-label" for="postal_code">{{ __('app.entities.people.postal_code') }}</label>
                <input type="text" id="postal_code" name="postal_code" class="form-control" placeholder="97477">
            </div>

            <div class="form-group">
                <label class="form-label" for="country">{{ __('app.entities.people.country') }}</label>
                <input type="text" id="country" name="country" class="form-control" placeholder="United States">
            </div>
        </div>

        {{-- Dynamic Custom Fields --}}
        @include('partials.custom_fields_form', ['customFields' => $customFields ?? [], 'values' => []])

        <div style="margin-top:1.5rem;display:flex;gap:0.75rem;">
            <button type="submit" class="btn btn-primary">{{ __('app.common.save') }}</button>
            <a href="/people" class="btn btn-secondary">{{ __('app.common.cancel') }}</a>
        </div>
    </form>
</div>
@endsection
