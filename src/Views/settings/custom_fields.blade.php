@extends('layouts.main')

@section('title', __('app.settings.custom_fields.title'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.settings.custom_fields.title') }}</h1>
        <p class="page-subtitle">{{ __('app.settings.custom_fields.subtitle') }}</p>
    </div>
</div>

<div class="detail-layout">
    <div>
        <div class="detail-card">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;border-bottom:1px solid var(--border);padding-bottom:0.5rem;">{{ __('app.settings.custom_fields.add_field') }}</h3>
            <form method="POST" action="/settings/custom-fields">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="entity_type">{{ __('app.settings.custom_fields.target') }}</label>
                    <select id="entity_type" name="entity_type" class="form-control">
                        <option value="companies">{{ __('app.entities.companies.title') }}</option>
                        <option value="people">{{ __('app.entities.people.title') }}</option>
                        <option value="opportunities">{{ __('app.entities.opportunities.title') }}</option>
                        <option value="tasks">{{ __('app.entities.tasks.title') }}</option>
                        <option value="quotes">{{ __('app.entities.quotes.title') }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="name">{{ __('app.settings.custom_fields.label') }} *</label>
                    <input type="text" id="name" name="name" class="form-control" required placeholder="e.g. Lead Source">
                </div>

                <div class="form-group">
                    <label class="form-label" for="code">Key / Code ({{ __('app.common.optional') }})</label>
                    <input type="text" id="code" name="code" class="form-control" placeholder="e.g. lead_source">
                </div>

                <div class="form-group">
                    <label class="form-label" for="type">{{ __('app.settings.custom_fields.type') }}</label>
                    <select id="type" name="type" class="form-control">
                        <option value="text">Text</option>
                        <option value="textarea">Text Area</option>
                        <option value="number">Number</option>
                        <option value="currency">Currency ($)</option>
                        <option value="date">Date</option>
                        <option value="boolean">Boolean</option>
                        <option value="select">Dropdown Select</option>
                        <option value="multi_select">Multi-Select Dropdown</option>
                        <option value="email">Email Address</option>
                        <option value="phone">Phone Number</option>
                        <option value="url">Website / URL</option>
                        <option value="rating">Rating</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="options">Options (comma separated)</label>
                    <input type="text" id="options" name="options" class="form-control" placeholder="Website, Referral, Cold Call, Partner">
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">{{ __('app.settings.custom_fields.add_field') }}</button>
            </form>
        </div>
    </div>

    <div>
        <div class="detail-card">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;">{{ __('app.settings.custom_fields.title') }} ({{ count($fields) }})</h3>
            <div class="table-container" style="border:none;background:transparent;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.settings.custom_fields.target') }}</th>
                            <th>{{ __('app.settings.custom_fields.label') }}</th>
                            <th>Code</th>
                            <th>{{ __('app.settings.custom_fields.type') }}</th>
                            <th>Options</th>
                            <th style="text-align:right;">{{ __('app.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(empty($fields))
                            <tr>
                                <td colspan="6" style="text-align:center;color:var(--text-dim);padding:2rem;">
                                    {{ __('app.common.no_records') }}
                                </td>
                            </tr>
                        @else
                            @foreach($fields as $f)
                                <tr>
                                    <td><span class="badge badge-primary">{{ $f['entity_type'] }}</span></td>
                                    <td style="font-weight:600;color:var(--text-main);">{{ $f['name'] }}</td>
                                    <td><code>{{ $f['code'] }}</code></td>
                                    <td>{{ $f['type'] }}</td>
                                    <td>
                                        @if(!empty($f['options']))
                                            <span style="font-size:0.75rem;color:var(--text-dim);">{{ implode(', ', json_decode((string)$f['options'], true) ?? []) }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td style="text-align:right;">
                                        <form method="POST" action="/settings/custom-fields/{{ $f['id'] }}/delete" data-confirm="Are you sure you want to remove this custom field?" style="margin:0;">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger);">{{ __('app.common.delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
