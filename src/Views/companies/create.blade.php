@extends('layouts.main')

@section('title', __('app.entities.companies.create_title'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.entities.companies.create_title') }}</h1>
        <p class="page-subtitle">{{ __('app.entities.companies.create_sub') }}</p>
    </div>
    <div>
        <a href="/companies" class="btn btn-secondary">&larr; {{ __('app.common.back') }}</a>
    </div>
</div>

<div class="detail-card" style="max-width:800px;">
    <form method="POST" action="/companies">
        @csrf
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label" for="name">{{ __('app.entities.companies.name') }} *</label>
                <input type="text" id="name" name="name" class="form-control" required placeholder="e.g. Stripe Financial">
            </div>

            <div class="form-group">
                <label class="form-label" for="domain">{{ __('app.entities.companies.domain') }}</label>
                <input type="text" id="domain" name="domain" class="form-control" placeholder="stripe.com">
            </div>

            <div class="form-group">
                <label class="form-label" for="industry">{{ __('app.entities.companies.industry') }}</label>
                <input type="text" id="industry" name="industry" class="form-control" placeholder="FinTech, SaaS, Healthcare">
            </div>

            <div class="form-group">
                <label class="form-label" for="annual_revenue">{{ __('app.entities.companies.revenue') }} ($)</label>
                <input type="number" step="0.01" id="annual_revenue" name="annual_revenue" class="form-control" placeholder="1000000">
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">{{ __('app.entities.companies.phone') }}</label>
                <input type="text" id="phone" name="phone" class="form-control" placeholder="+1-555-0199">
            </div>

            <div class="form-group">
                <label class="form-label" for="email">{{ __('app.common.email') }}</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="contact@company.com">
            </div>

            <div class="form-group">
                <label class="form-label" for="website">{{ __('app.entities.companies.domain') }}</label>
                <input type="url" id="website" name="website" class="form-control" placeholder="https://company.com">
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label" for="description">{{ __('app.common.details') }}</label>
                <textarea id="description" name="description" class="form-control" placeholder="Brief summary of company operations..."></textarea>
            </div>

            {{-- Address & Location --}}
            <div class="form-group" style="grid-column: span 2; margin-top:0.5rem; padding-top:0.75rem; border-top:1px dashed var(--border);">
                <span style="font-size:0.85rem;font-weight:700;color:var(--text-main);text-transform:uppercase;letter-spacing:0.5px;">📍 {{ __('app.common.address') }}</span>
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label" for="address">{{ __('app.entities.companies.address') }}</label>
                <input type="text" id="address" name="address" class="form-control" placeholder="100 Enterprise Way, Suite 400">
            </div>

            <div class="form-group">
                <label class="form-label" for="city">{{ __('app.entities.companies.city') }}</label>
                <input type="text" id="city" name="city" class="form-control" placeholder="San Francisco">
            </div>

            <div class="form-group">
                <label class="form-label" for="state">{{ __('app.entities.companies.state') }}</label>
                <input type="text" id="state" name="state" class="form-control" placeholder="California">
            </div>

            <div class="form-group">
                <label class="form-label" for="postal_code">{{ __('app.entities.companies.postal_code') }}</label>
                <input type="text" id="postal_code" name="postal_code" class="form-control" placeholder="94105">
            </div>

            <div class="form-group">
                <label class="form-label" for="country">{{ __('app.entities.companies.country') }}</label>
                <input type="text" id="country" name="country" class="form-control" placeholder="United States">
            </div>
        </div>

        {{-- Dynamic Custom Fields --}}
        @include('partials.custom_fields_form', ['customFields' => $customFields ?? [], 'values' => []])

        <div style="margin-top:1.5rem;display:flex;gap:0.75rem;">
            <button type="submit" class="btn btn-primary">{{ __('app.common.save') }}</button>
            <a href="/companies" class="btn btn-secondary">{{ __('app.common.cancel') }}</a>
        </div>
    </form>
</div>
@endsection
