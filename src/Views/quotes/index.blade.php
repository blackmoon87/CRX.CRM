@extends('layouts.main')

@section('title', __('app.entities.quotes.title'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.entities.quotes.title') }}</h1>
        <p class="page-subtitle">{{ __('app.entities.quotes.subtitle') }}</p>
    </div>
    <a href="/quotes/create" class="btn btn-primary">
        + {{ __('app.entities.quotes.add_new') }}
    </a>
</div>

<div class="table-container">
    @if(empty($quotes))
        <div class="empty-box" style="padding: 3.5rem 2rem;">
            <div class="empty-box-icon">📜</div>
            <div class="empty-box-title">{{ __('app.common.no_records') }}</div>
            <p class="empty-box-desc">Create your first quote with automated line items and client signatures.</p>
            <a href="/quotes/create" class="btn btn-primary" style="margin-top: 10px;">+ {{ __('app.entities.quotes.add_new') }}</a>
        </div>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('app.entities.quotes.quote_number') }}</th>
                    <th>{{ __('app.common.title') }}</th>
                    <th>{{ __('app.common.status') }}</th>
                    <th>{{ __('app.entities.quotes.total') }}</th>
                    <th>{{ __('app.entities.quotes.valid_until') }}</th>
                    <th style="text-align: right;">{{ __('app.common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotes as $q)
                    <tr>
                        <td style="font-weight: 600; font-family: monospace;">
                            <a href="/quotes/{{ $q['id'] }}" style="color: var(--primary); text-decoration: none;">
                                {{ $q['quote_number'] }}
                            </a>
                        </td>
                        <td style="font-weight: 600; color: var(--text-main);">
                            {{ $q['title'] }}
                        </td>
                        <td>
                            @if($q['status'] === 'accepted')
                                <span class="badge badge-success">
                                    🏆 Accepted
                                </span>
                            @elseif($q['status'] === 'sent')
                                <span class="badge badge-info">
                                    📤 Sent to Client
                                </span>
                            @else
                                <span class="badge badge-secondary">
                                    Draft
                                </span>
                            @endif
                        </td>
                        <td style="font-weight: 700; color: var(--text-main);">
                            ${{ number_format((float)$q['total_amount'], 2) }}
                        </td>
                        <td style="font-size: 0.88rem; color: var(--text-dim);">
                            {{ $q['valid_until'] ? date('M j, Y', strtotime($q['valid_until'])) : '—' }}
                        </td>
                        <td style="text-align: right;">
                            <a href="/quote/{{ $q['public_token'] }}" target="_blank" class="btn btn-secondary btn-sm" style="margin-right: 4px;">
                                ↗ Client Link
                            </a>
                            <a href="/quotes/{{ $q['id'] }}" class="btn btn-secondary btn-sm">
                                {{ __('app.common.view') ?? 'View' }}
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
