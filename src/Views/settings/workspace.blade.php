@extends('layouts.main')

@section('title', __('app.settings.workspace.title'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.settings.workspace.title') }}</h1>
        <p class="page-subtitle">{{ __('app.settings.workspace.subtitle') }}</p>
    </div>
    <div>
        <a href="/settings/roles" class="btn btn-primary">
            <svg style="width:16px;height:16px;margin-right:0.35rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            {{ __('app.settings.roles.title') ?? 'Roles & Permissions' }}
        </a>
    </div>
</div>

<div class="detail-layout">
    <div>
        <div class="detail-card" style="margin-bottom:1.5rem;">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;border-bottom:1px solid var(--border);padding-bottom:0.5rem;">
                {{ __('app.settings.workspace.profile') }}
            </h3>
            <form method="POST" action="/settings/workspace/update">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="name">{{ __('app.settings.workspace.name') }}</label>
                    <input type="text" id="name" name="name" class="form-control" required value="{{ $workspace['name'] }}">
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('app.settings.workspace.slug') }}</label>
                    <input type="text" class="form-control" disabled value="{{ $workspace['slug'] }}" style="background:var(--bg-main);">
                </div>

                <button type="submit" class="btn btn-primary">{{ __('app.common.save_changes') }}</button>
            </form>
        </div>

        {{-- Invite Member Card --}}
        <div class="detail-card">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;border-bottom:1px solid var(--border);padding-bottom:0.5rem;">
                {{ __('app.settings.workspace.team_members') }}
            </h3>
            <form method="POST" action="/settings/workspace/invite">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="member_email">{{ __('app.common.email') }} *</label>
                    <input type="email" id="member_email" name="email" class="form-control" required placeholder="colleague@company.com">
                </div>

                <div class="form-group">
                    <label class="form-label" for="member_name">{{ __('app.auth.full_name') }} ({{ __('app.common.optional') }})</label>
                    <input type="text" id="member_name" name="name" class="form-control" placeholder="Jane Doe">
                </div>

                <div class="form-group">
                    <label class="form-label" for="member_role">{{ __('app.entities.people.title_role') }}</label>
                    <select id="member_role" name="role" class="form-control">
                        @if(isset($roles) && is_array($roles))
                            @foreach($roles as $r)
                                <option value="{{ $r['slug'] }}" {{ $r['slug'] === 'member' ? 'selected' : '' }}>
                                    {{ $r['name'] }} {{ $r['is_system'] ? '' : ' (' . (__('app.settings.roles.custom') ?? 'Custom') . ')' }}
                                </option>
                            @endforeach
                        @else
                            <option value="member">Member</option>
                            <option value="admin">Admin</option>
                            <option value="viewer">Viewer</option>
                        @endif
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">
                    {{ __('app.common.create') }}
                </button>
            </form>
        </div>
    </div>

    <div>
        <div class="detail-card">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;display:flex;justify-content:space-between;align-items:center;">
                <span>{{ __('app.settings.workspace.team_members') }}</span>
                <span class="badge badge-primary">{{ count($members) }} Total</span>
            </h3>
            <div class="table-container" style="border:none;background:transparent;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.common.name') }}</th>
                            <th>{{ __('app.common.email') }}</th>
                            <th>{{ __('app.entities.people.title_role') }}</th>
                            <th>{{ __('app.common.created_at') }}</th>
                            <th style="text-align:right;">{{ __('app.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($members as $m)
                            <tr>
                                <td>
                                    <div style="font-weight:600;color:var(--text-main);">
                                        {{ $m['name'] }}
                                        @if($m['user_id'] == $currentUserId)
                                            <span style="font-size:0.7rem;color:var(--primary);margin-left:0.25rem;">(You)</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $m['email'] }}</td>
                                <td>
                                    @if($m['user_id'] != $currentUserId && $m['role'] !== 'owner' && (user_can('settings.members') || in_array(active_user_role(), ['owner', 'admin'], true)))
                                        <form method="POST" action="/settings/workspace/members/{{ $m['membership_id'] }}/role" style="margin:0;display:inline-block;">
                                            @csrf
                                            <select name="role" onchange="this.form.submit()" class="form-control" style="padding:0.2rem 0.5rem;font-size:0.75rem;height:auto;width:auto;">
                                                @foreach($roles ?? [] as $r)
                                                    <option value="{{ $r['slug'] }}" {{ $r['slug'] === $m['role'] ? 'selected' : '' }}>
                                                        {{ $r['name'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @else
                                        @if($m['role'] === 'owner')
                                            <span class="badge" style="background:#805ad5;color:#fff;">Owner</span>
                                        @elseif($m['role'] === 'admin')
                                            <span class="badge badge-primary">Admin</span>
                                        @elseif($m['role'] === 'viewer')
                                            <span class="badge" style="background:#718096;color:#fff;">Viewer</span>
                                        @elseif($m['role'] === 'member')
                                            <span class="badge" style="background:#38a169;color:#fff;">Member</span>
                                        @else
                                            <span class="badge" style="background:#0284c7;color:#fff;">{{ ucfirst(str_replace('_', ' ', $m['role'])) }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td>{{ substr((string)$m['created_at'], 0, 10) }}</td>
                                <td style="text-align:right;">
                                    @if($m['user_id'] != $currentUserId && $m['role'] !== 'owner')
                                        <form method="POST" action="/settings/workspace/members/{{ $m['membership_id'] }}/remove" data-confirm="Remove this user from the workspace?" style="margin:0;">
                                             @csrf
                                            <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger);">
                                                {{ __('app.common.delete') }}
                                            </button>
                                        </form>
                                    @else
                                        <span style="font-size:0.75rem;color:var(--text-dim);">Protected</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
