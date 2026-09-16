@extends('layouts.main')

@section('title', __('app.settings.tags.title'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.settings.tags.title') }}</h1>
        <p class="page-subtitle">{{ __('app.settings.tags.subtitle') }}</p>
    </div>
</div>

<div class="detail-layout" style="grid-template-columns: 360px 1fr; gap: 1.5rem;">
    <div>
        <div class="detail-card">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;border-bottom:1px solid var(--border);padding-bottom:0.5rem;">
                {{ __('app.settings.tags.create') }}
            </h3>
            <form method="POST" action="/settings/tags">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="name">{{ __('app.settings.tags.label') }} *</label>
                    <input type="text" id="name" name="name" class="form-control" required placeholder="e.g. Enterprise, VIP, ChurnRisk">
                </div>

                <div class="form-group">
                    <label class="form-label" for="color">{{ __('app.settings.tags.color') }}</label>
                    <div style="display:flex;gap:0.5rem;align-items:center;">
                        <input type="color" id="color" name="color" value="#7C3AED" style="width:44px;height:38px;padding:2px;border:1px solid var(--border);border-radius:6px;cursor:pointer;">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">
                    + {{ __('app.settings.tags.create') }}
                </button>
            </form>
        </div>
    </div>

    <div>
        <div class="detail-card">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;">
                {{ __('app.settings.tags.title') }} ({{ count($tags) }})
            </h3>
            <div class="table-container" style="border:none;background:transparent;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.common.view') }}</th>
                            <th>{{ __('app.settings.tags.label') }}</th>
                            <th>Code</th>
                            <th style="text-align:right;">{{ __('app.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(empty($tags))
                            <tr>
                                <td colspan="4" style="text-align:center;padding:2rem;color:var(--text-dim);">
                                    {{ __('app.common.no_records') }}
                                </td>
                            </tr>
                        @else
                            @foreach($tags as $t)
                                <tr>
                                    <td>
                                        <span class="badge" style="background:{{ $t['color'] }};color:#fff;font-weight:700;box-shadow:1.5px 1.5px 0 rgba(0,0,0,0.25);">
                                            #{{ $t['name'] }}
                                        </span>
                                    </td>
                                    <td style="font-weight:600;color:var(--text-main);">{{ $t['name'] }}</td>
                                    <td><code>{{ $t['color'] }}</code></td>
                                    <td style="text-align:right;">
                                        <form method="POST" action="/settings/tags/{{ $t['id'] }}/delete" data-confirm="Are you sure you want to delete this tag?" style="margin:0;">
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
