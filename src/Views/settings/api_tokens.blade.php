@extends('layouts.main')

@section('title', __('app.settings.api_tokens.title'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.settings.api_tokens.title') }}</h1>
        <p class="page-subtitle">{{ __('app.settings.api_tokens.subtitle') }}</p>
    </div>
</div>

<div class="detail-layout">
    <div>
        <div class="detail-card">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;border-bottom:1px solid var(--border);padding-bottom:0.5rem;">{{ __('app.settings.api_tokens.generate') }}</h3>
            <form method="POST" action="/settings/api-tokens/generate">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="name">{{ __('app.settings.api_tokens.token_name') }}</label>
                    <input type="text" id="name" name="name" class="form-control" required placeholder="e.g. Claude Desktop Agent">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">{{ __('app.settings.api_tokens.generate') }}</button>
            </form>
        </div>

        {{-- MCP Configuration Guide --}}
        <div class="detail-card">
            <h4 style="font-size:0.85rem;font-weight:700;color:var(--text-dim);text-transform:uppercase;margin-bottom:0.6rem;">MCP Endpoint</h4>
            <div style="background:var(--bg-surface);padding:0.75rem;border:1px solid var(--border);border-radius:var(--radius-sm);font-family:monospace;font-size:0.82rem;color:var(--primary);word-break:break-all;">
                {{ config('app.url') }}/api/mcp
            </div>

            <div style="margin-top:1rem;font-size:0.82rem;color:var(--text-muted);line-height:1.5;">
                <p>Include in your HTTP request headers:</p>
                <code style="display:block;margin-top:0.4rem;background:var(--bg-surface);padding:0.5rem;border-radius:4px;color:var(--text-main);">
                    Authorization: Bearer &lt;YOUR_TOKEN&gt;
                </code>
            </div>

            <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid var(--border);">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                    <span style="font-size:0.82rem;font-weight:700;color:var(--text-main);">MCP Protocol</span>
                    <span style="font-size:0.75rem;padding:2px 8px;border-radius:12px;background:rgba(99,102,241,0.1);color:var(--primary);font-weight:700;">52 Tools</span>
                </div>
            </div>
        </div>
    </div>

    <div>
        <div class="detail-card">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;">{{ __('app.settings.api_tokens.title') }} ({{ count($tokens) }})</h3>
            <div class="table-container" style="border:none;background:transparent;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.common.name') }}</th>
                            <th>Token</th>
                            <th>{{ __('app.common.updated_at') }}</th>
                            <th style="text-align:right;">{{ __('app.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(empty($tokens))
                            <tr>
                                <td colspan="4" style="text-align:center;color:var(--text-dim);padding:2rem;">
                                    {{ __('app.common.no_records') }}
                                </td>
                            </tr>
                        @else
                            @foreach($tokens as $tok)
                                <tr>
                                    <td style="font-weight:600;color:var(--text-main);">{{ $tok['name'] }}</td>
                                    <td>
                                        <code style="background:var(--bg-surface);padding:3px 6px;border-radius:4px;color:var(--primary);">
                                            {{ $tok['token'] }}
                                        </code>
                                    </td>
                                    <td>{{ $tok['last_used_at'] ?? '—' }}</td>
                                    <td style="text-align:right;">
                                        <form method="POST" action="/settings/api-tokens/{{ $tok['id'] }}/revoke" data-confirm="Are you sure you want to revoke this API token?" style="margin:0;">
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
