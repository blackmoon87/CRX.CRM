@extends('layouts.main')

@section('title', __('app.settings.recycle_bin.title'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.settings.recycle_bin.title') }}</h1>
        <p class="page-subtitle">{{ __('app.settings.recycle_bin.subtitle') }}</p>
    </div>
</div>

<div class="detail-card" style="margin-bottom:1.5rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border);padding-bottom:1rem;margin-bottom:1rem;">
        <div>
            <h3 style="font-size:1.1rem;font-weight:700;margin:0;color:var(--text-main);">{{ __('app.settings.recycle_bin.deleted_items') }}</h3>
            <span style="font-size:0.8rem;color:var(--text-dim);">Total {{ $totalCount }}</span>
        </div>
        <span class="badge {{ $totalCount > 0 ? 'badge-warning' : 'badge-success' }}">
            {{ $totalCount }}
        </span>
    </div>

    @if($totalCount === 0)
        <div style="text-align:center;padding:3rem 1rem;">
            <div style="font-size:2.5rem;margin-bottom:0.75rem;">🛡️</div>
            <h3 style="font-size:1.1rem;font-weight:700;color:var(--text-main);margin-bottom:0.25rem;">{{ __('app.settings.recycle_bin.clean_title') }}</h3>
            <p style="font-size:0.85rem;color:var(--text-dim);margin:0;">{{ __('app.settings.recycle_bin.clean_subtitle') }}</p>
        </div>
    @else
        {{-- Deleted Items Table --}}
        <div class="table-container" style="border:none;background:transparent;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('app.settings.custom_fields.type') }}</th>
                        <th>{{ __('app.common.name') }}</th>
                        <th>{{ __('app.common.created_at') }}</th>
                        <th style="text-align:right;">{{ __('app.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Companies --}}
                    @foreach($companies as $c)
                        <tr>
                            <td><span class="badge badge-primary">{{ __('app.entities.companies.title') }}</span></td>
                            <td style="font-weight:700;color:var(--text-main);">{{ $c['name'] }}</td>
                            <td>{{ $c['deleted_at'] }}</td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex;gap:0.5rem;">
                                    <form method="POST" action="/settings/recycle-bin/restore" style="margin:0;">
                                        @csrf
                                        <input type="hidden" name="entity_type" value="companies">
                                        <input type="hidden" name="id" value="{{ $c['id'] }}">
                                        <button type="submit" class="btn btn-primary btn-sm">↺ {{ __('app.settings.recycle_bin.restore') }}</button>
                                    </form>
                                    <form method="POST" action="/settings/recycle-bin/purge" data-confirm="Permanently delete this company? This cannot be undone." style="margin:0;">
                                        @csrf
                                        <input type="hidden" name="entity_type" value="companies">
                                        <input type="hidden" name="id" value="{{ $c['id'] }}">
                                        <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger);">{{ __('app.settings.recycle_bin.purge') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    {{-- People --}}
                    @foreach($people as $p)
                        <tr>
                            <td><span class="badge badge-info">{{ __('app.entities.people.title') }}</span></td>
                            <td style="font-weight:700;color:var(--text-main);">{{ $p['first_name'] }} {{ $p['last_name'] ?? '' }}</td>
                            <td>{{ $p['deleted_at'] }}</td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex;gap:0.5rem;">
                                    <form method="POST" action="/settings/recycle-bin/restore" style="margin:0;">
                                        @csrf
                                        <input type="hidden" name="entity_type" value="people">
                                        <input type="hidden" name="id" value="{{ $p['id'] }}">
                                        <button type="submit" class="btn btn-primary btn-sm">↺ {{ __('app.settings.recycle_bin.restore') }}</button>
                                    </form>
                                    <form method="POST" action="/settings/recycle-bin/purge" data-confirm="Permanently delete this contact? This cannot be undone." style="margin:0;">
                                        @csrf
                                        <input type="hidden" name="entity_type" value="people">
                                        <input type="hidden" name="id" value="{{ $p['id'] }}">
                                        <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger);">{{ __('app.settings.recycle_bin.purge') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    {{-- Opportunities --}}
                    @foreach($opportunities as $o)
                        <tr>
                            <td><span class="badge badge-warning">{{ __('app.entities.opportunities.title') }}</span></td>
                            <td style="font-weight:700;color:var(--text-main);">{{ $o['name'] }} (${{ number_format((float)$o['amount'], 2) }})</td>
                            <td>{{ $o['deleted_at'] }}</td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex;gap:0.5rem;">
                                    <form method="POST" action="/settings/recycle-bin/restore" style="margin:0;">
                                        @csrf
                                        <input type="hidden" name="entity_type" value="opportunities">
                                        <input type="hidden" name="id" value="{{ $o['id'] }}">
                                        <button type="submit" class="btn btn-primary btn-sm">↺ {{ __('app.settings.recycle_bin.restore') }}</button>
                                    </form>
                                    <form method="POST" action="/settings/recycle-bin/purge" data-confirm="Permanently delete this deal? This cannot be undone." style="margin:0;">
                                        @csrf
                                        <input type="hidden" name="entity_type" value="opportunities">
                                        <input type="hidden" name="id" value="{{ $o['id'] }}">
                                        <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger);">{{ __('app.settings.recycle_bin.purge') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    {{-- Tasks --}}
                    @foreach($tasks as $t)
                        <tr>
                            <td><span class="badge badge-secondary">{{ __('app.entities.tasks.title') }}</span></td>
                            <td style="font-weight:700;color:var(--text-main);">{{ $t['title'] }}</td>
                            <td>{{ $t['deleted_at'] }}</td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex;gap:0.5rem;">
                                    <form method="POST" action="/settings/recycle-bin/restore" style="margin:0;">
                                        @csrf
                                        <input type="hidden" name="entity_type" value="tasks">
                                        <input type="hidden" name="id" value="{{ $t['id'] }}">
                                        <button type="submit" class="btn btn-primary btn-sm">↺ {{ __('app.settings.recycle_bin.restore') }}</button>
                                    </form>
                                    <form method="POST" action="/settings/recycle-bin/purge" data-confirm="Permanently delete this task? This cannot be undone." style="margin:0;">
                                        @csrf
                                        <input type="hidden" name="entity_type" value="tasks">
                                        <input type="hidden" name="id" value="{{ $t['id'] }}">
                                        <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger);">{{ __('app.settings.recycle_bin.purge') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
