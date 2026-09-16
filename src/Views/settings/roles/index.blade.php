@extends('layouts.main')

@section('title', __('app.settings.roles.title'))

@section('content')
<style>
/* ─── Modern Scoped Styles for Roles & Permissions Matrix ─── */
.rbac-container {
    max-width: 1400px;
    margin: 0 auto;
}

.rbac-wrapper {
    display: grid;
    grid-template-columns: 360px minmax(0, 1fr);
    gap: 1.5rem;
    align-items: start;
}

@media (max-width: 1060px) {
    .rbac-wrapper {
        grid-template-columns: 1fr;
    }
}

/* Glass & Surface Cards */
.rbac-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.02);
    padding: 1.5rem;
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
}

/* Available Roles Sidebar List */
.role-card-item {
    padding: 1rem 1.1rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    background: #ffffff;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

.role-card-item:hover {
    border-color: #6366f1;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px -2px rgba(99, 102, 241, 0.12);
}

.role-card-item.active-role {
    border-color: #4f46e5;
    background: linear-gradient(180deg, #f8faff 0%, #f1f5fd 100%);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15), 0 8px 20px -4px rgba(79, 70, 229, 0.12);
}

/* Role Icon Boxes */
.role-icon-box {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}
.role-icon-owner  { background: #f3e8ff; color: #7c3aed; }
.role-icon-admin  { background: #eff6ff; color: #2563eb; }
.role-icon-member { background: #ecfdf5; color: #059669; }
.role-icon-viewer { background: #f1f5f9; color: #475569; }
.role-icon-custom { background: #fef3c7; color: #d97706; }

/* Vibrant Gradient Badges */
.role-badge-owner {
    background: linear-gradient(135deg, #8b5cf6, #6366f1);
    color: #ffffff;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 0.72rem;
    letter-spacing: 0.3px;
    box-shadow: 0 2px 6px rgba(139, 92, 246, 0.3);
}

.role-badge-admin {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #ffffff;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 0.72rem;
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
}

.role-badge-member {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #ffffff;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 0.72rem;
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3);
}

.role-badge-viewer {
    background: linear-gradient(135deg, #64748b, #475569);
    color: #ffffff;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 0.72rem;
    box-shadow: 0 2px 6px rgba(100, 116, 139, 0.3);
}

.role-badge-custom {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #ffffff;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 0.72rem;
    box-shadow: 0 2px 6px rgba(217, 119, 6, 0.3);
}

.role-badge-system {
    background: #f8fafc;
    color: #475569;
    border: 1px solid #cbd5e1;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.7rem;
}

/* Preset Toolbar Buttons */
.preset-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.85rem;
    font-size: 0.75rem;
    font-weight: 700;
    border-radius: 9999px;
    cursor: pointer;
    transition: all 0.15s ease;
    border: 1.5px solid transparent;
}
.preset-pill:hover {
    transform: translateY(-1px);
}
.preset-pill-read {
    background: #eff6ff;
    color: #1d4ed8;
    border-color: #bfdbfe;
}
.preset-pill-read:hover {
    background: #dbeafe;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.15);
}
.preset-pill-editor {
    background: #ecfdf5;
    color: #047857;
    border-color: #a7f3d0;
}
.preset-pill-editor:hover {
    background: #d1fae5;
    box-shadow: 0 2px 8px rgba(5, 150, 105, 0.15);
}
.preset-pill-all {
    background: #fffbeb;
    color: #b45309;
    border-color: #fde68a;
}
.preset-pill-all:hover {
    background: #fef3c7;
    box-shadow: 0 2px 8px rgba(217, 119, 6, 0.15);
}
.preset-pill-none {
    background: #fff1f2;
    color: #be123c;
    border-color: #fecdd3;
}
.preset-pill-none:hover {
    background: #ffe4e6;
    box-shadow: 0 2px 8px rgba(225, 29, 72, 0.15);
}

/* High-Contrast Matrix Table */
.rbac-matrix-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}
.rbac-matrix-table th {
    background: #f8fafc;
    color: #1e293b;
    font-weight: 800;
    font-size: 0.82rem;
    padding: 0.9rem 1rem;
    border-bottom: 1px solid var(--border);
    text-align: center;
    letter-spacing: 0.02em;
}
.rbac-matrix-table th:first-child {
    text-align: start;
}
.rbac-matrix-table th.th-delete {
    color: #dc2626;
    background: #fff5f5;
}

.rbac-matrix-table td {
    padding: 0.9rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    text-align: center;
    vertical-align: middle;
    background: #ffffff;
    transition: background 0.15s ease;
}
.rbac-matrix-table tr:hover td {
    background: #f8faff;
}
.rbac-matrix-table tr:last-child td {
    border-bottom: none;
}
.rbac-matrix-table td:first-child {
    text-align: start;
}

/* Modern Checkbox Accent */
.perm-cb {
    width: 19px;
    height: 19px;
    cursor: pointer;
    border-radius: 5px;
    accent-color: #4f46e5;
    transition: transform 0.1s ease;
    vertical-align: middle;
}
.perm-cb:hover {
    transform: scale(1.18);
}
.perm-cb-delete {
    accent-color: #ef4444;
}
.perm-cb-delete:hover {
    accent-color: #dc2626;
}

/* Module Feature Icon Box */
.module-icon-box {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.mod-contacts   { background: #eff6ff; color: #2563eb; }
.mod-companies  { background: #f5f3ff; color: #7c3aed; }
.mod-deals      { background: #fef3c7; color: #d97706; }
.mod-quotes     { background: #ecfdf5; color: #059669; }
.mod-tasks      { background: #fff1f2; color: #e11d48; }
.mod-reports    { background: #f0fdfa; color: #0d9488; }
.mod-settings   { background: #f1f5f9; color: #475569; }

/* Settings Chips Grid */
.settings-chip-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: flex-start;
}
.settings-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.35rem 0.75rem;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
    transition: all 0.15s ease;
    user-select: none;
}
.settings-chip:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}
.settings-chip:has(input:checked) {
    background: #eff6ff;
    border-color: #93c5fd;
    color: #1e40af;
}

/* Input Fields */
.form-control-custom {
    width: 100%;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    padding: 0.65rem 0.95rem;
    font-size: 0.88rem;
    color: #0f172a;
    background: #ffffff;
    transition: all 0.15s ease;
    outline: none;
    box-sizing: border-box;
}
.form-control-custom:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
}
.form-control-custom:disabled {
    background: #f1f5f9;
    color: #64748b;
    cursor: not-allowed;
}

/* RTL Adjustments */
html[dir="rtl"] .rbac-matrix-table th:first-child,
html[dir="rtl"] .rbac-matrix-table td:first-child {
    text-align: right;
}
html[dir="rtl"] .settings-chip-container {
    justify-content: flex-start;
}
</style>

<div class="rbac-container">
    {{-- Page Header --}}
    <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.75rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <h1 class="page-title" style="display:flex;align-items:center;gap:0.6rem;font-size:1.6rem;font-weight:800;color:#0f172a;margin:0;">
                <span style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg, #4f46e5, #6366f1);color:#ffffff;box-shadow:0 4px 12px rgba(79,70,229,0.3);">
                    🛡️
                </span>
                <span>{{ __('app.settings.roles.title') }}</span>
            </h1>
            <p class="page-subtitle" style="color:#64748b;font-size:0.88rem;margin:0.35rem 0 0 0;">
                {{ __('app.settings.roles.subtitle') }}
            </p>
        </div>
        <div style="display:flex;gap:0.75rem;align-items:center;">
            <a href="/settings/workspace" class="btn btn-secondary" style="display:inline-flex;align-items:center;gap:0.4rem;font-weight:600;padding:0.6rem 1.1rem;border-radius:8px;">
                <svg style="width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>{{ __('app.settings.workspace.team_members') }}</span>
            </a>
            <button type="button" class="btn btn-primary" onclick="resetRoleForm()" style="display:inline-flex;align-items:center;gap:0.4rem;background:linear-gradient(135deg, #4f46e5, #6366f1);border:none;box-shadow:0 4px 14px rgba(79,70,229,0.35);font-weight:700;padding:0.6rem 1.25rem;border-radius:8px;">
                <span>+</span>
                <span>{{ __('app.settings.roles.create_role') }}</span>
            </button>
        </div>
    </div>

    {{-- Main RBAC Grid --}}
    <div class="rbac-wrapper">
        {{-- Left Side: Available Roles --}}
        <div>
            <div class="rbac-card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;border-bottom:1.5px solid #f1f5f9;padding-bottom:0.75rem;">
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <span style="font-weight:800;font-size:1.05rem;color:#0f172a;">{{ __('app.settings.roles.available_roles') }}</span>
                    </div>
                    <span style="background:#eef2ff;color:#4338ca;font-weight:800;padding:3px 10px;border-radius:20px;font-size:0.75rem;border:1px solid #c7d2fe;">
                        {{ count($roles) }} {{ __('app.common.all') }}
                    </span>
                </div>

                <div style="display:flex;flex-direction:column;gap:0.85rem;">
                    @foreach($roles as $r)
                        @php
                            $isPreset = !empty($r['is_system']);
                            $badgeClass = match($r['slug']) {
                                'owner'         => 'role-badge-owner',
                                'administrator' => 'role-badge-admin',
                                'member'        => 'role-badge-member',
                                'viewer'        => 'role-badge-viewer',
                                default         => 'role-badge-custom',
                            };
                            $iconBoxClass = match($r['slug']) {
                                'owner'         => 'role-icon-owner',
                                'administrator' => 'role-icon-admin',
                                'member'        => 'role-icon-member',
                                'viewer'        => 'role-icon-viewer',
                                default         => 'role-icon-custom',
                            };
                            $roleIcon = match($r['slug']) {
                                'owner'         => '👑',
                                'administrator' => '🛡️',
                                'member'        => '👤',
                                'viewer'        => '👁️',
                                default         => '⭐',
                            };
                        @endphp
                        <div class="role-card-item" id="role-card-{{ $r['id'] }}" onclick="handleRoleCardClick({{ $r['id'] }})">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.4rem;gap:0.5rem;">
                                <div style="display:flex;align-items:center;gap:0.6rem;min-width:0;">
                                    <div class="role-icon-box {{ $iconBoxClass }}">
                                        {{ $roleIcon }}
                                    </div>
                                    <div style="min-width:0;">
                                        <div style="font-weight:800;font-size:0.92rem;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                            {{ $r['name'] }}
                                        </div>
                                        <div style="font-family:monospace;font-size:0.72rem;color:#64748b;">
                                            {{ $r['slug'] }}
                                        </div>
                                    </div>
                                </div>
                                <span class="{{ $badgeClass }}" style="flex-shrink:0;">
                                    {{ $r['name'] }}
                                </span>
                            </div>

                            <p style="margin:0.4rem 0 0.75rem 0;color:#64748b;font-size:0.8rem;line-height:1.45;">
                                {{ $r['description'] ?? '—' }}
                            </p>

                            <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px dashed #e2e8f0;padding-top:0.6rem;">
                                <span style="font-size:0.75rem;font-weight:600;color:#64748b;display:inline-flex;align-items:center;gap:0.3rem;">
                                    <span>👥</span>
                                    <span>{{ __('app.settings.roles.members_count', ['count' => $r['member_count'] ?? ($r['user_count'] ?? 0)]) }}</span>
                                </span>

                                @if($isPreset)
                                    <button type="button" class="btn btn-secondary btn-sm" style="font-size:0.75rem;padding:0.25rem 0.65rem;border-radius:6px;font-weight:700;color:#4f46e5;border-color:#c7d2fe;background:#f8faff;" onclick="event.stopPropagation(); handleViewPreset({{ $r['id'] }})">
                                        👁️ {{ __('app.settings.roles.view_matrix') }}
                                    </button>
                                @else
                                    <div style="display:flex;gap:0.4rem;" onclick="event.stopPropagation();">
                                        <button type="button" class="btn btn-secondary btn-sm" style="font-size:0.75rem;padding:0.25rem 0.65rem;border-radius:6px;font-weight:700;" onclick="handleEditRole({{ $r['id'] }})">
                                            ✏️ {{ __('app.common.edit') }}
                                        </button>
                                        <form method="POST" action="/settings/roles/{{ $r['id'] }}/delete" style="margin:0;" onsubmit="return confirm('{{ __('app.common.delete') }}?');">
                                            @csrf
                                            <button type="submit" class="btn btn-danger btn-sm" style="font-size:0.75rem;padding:0.25rem 0.65rem;border-radius:6px;font-weight:700;background:#fee2e2;color:#dc2626;border-color:#fca5a5;">
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Right Side: Dynamic Role Builder & Matrix Form --}}
        <div>
            <div class="rbac-card">
                <form id="role-form" method="POST" action="/settings/roles">
                    @csrf
                    <input type="hidden" name="role_id" id="role_id" value="">

                    {{-- Form Header --}}
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem;border-bottom:1.5px solid #f1f5f9;padding-bottom:1.25rem;gap:1rem;">
                        <div>
                            <div style="display:flex;align-items:center;gap:0.5rem;">
                                <h2 id="form-heading" style="font-size:1.25rem;font-weight:800;color:#0f172a;margin:0;">
                                    {{ __('app.settings.roles.create_role') }}
                                </h2>
                                <span id="preset-badge" class="role-badge-system" style="display:none;">
                                    🔒 {{ __('app.settings.roles.system_preset') }}
                                </span>
                            </div>
                            <p id="form-subheading" style="margin:0.35rem 0 0 0;color:#64748b;font-size:0.85rem;">
                                {{ __('app.settings.roles.builder_desc') }}
                            </p>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="resetRoleForm()" style="font-weight:700;border-radius:6px;">
                            + {{ __('app.settings.roles.create_role') }}
                        </button>
                    </div>

                    {{-- Name & Description inputs --}}
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.75rem;">
                        <div>
                            <label for="role_name" style="display:block;font-size:0.8rem;font-weight:700;color:#334155;margin-bottom:0.4rem;">
                                {{ __('app.settings.roles.role_name') }} <span style="color:#ef4444;">*</span>
                            </label>
                            <input type="text" id="role_name" name="name" class="form-control-custom" required placeholder="{{ __('app.settings.roles.role_name_placeholder') }}">
                        </div>
                        <div>
                            <label for="role_description" style="display:block;font-size:0.8rem;font-weight:700;color:#334155;margin-bottom:0.4rem;">
                                {{ __('app.settings.roles.role_desc') }}
                            </label>
                            <input type="text" id="role_description" name="description" class="form-control-custom" placeholder="{{ __('app.settings.roles.role_desc_placeholder') }}">
                        </div>
                    </div>

                    {{-- Matrix Subtitle & Quick Presets Toolbar --}}
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:0.75rem;">
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <span style="font-size:1.1rem;">📑</span>
                            <span style="font-weight:800;font-size:0.95rem;color:#0f172a;">{{ __('app.settings.roles.matrix_title') }}</span>
                        </div>
                        <div id="matrix-presets-toolbar" style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                            <button type="button" class="preset-pill preset-pill-read" onclick="applyPreset('read_only')">
                                <span>👁️</span>
                                <span>{{ __('app.settings.roles.preset_read_only') }}</span>
                            </button>
                            <button type="button" class="preset-pill preset-pill-editor" onclick="applyPreset('editor')">
                                <span>✍️</span>
                                <span>{{ __('app.settings.roles.preset_editor') }}</span>
                            </button>
                            <button type="button" class="preset-pill preset-pill-all" onclick="applyPreset('all')">
                                <span>⚡</span>
                                <span>{{ __('app.settings.roles.preset_all') }}</span>
                            </button>
                            <button type="button" class="preset-pill preset-pill-none" onclick="applyPreset('none')">
                                <span>✕</span>
                                <span>{{ __('app.settings.roles.preset_clear') }}</span>
                            </button>
                        </div>
                    </div>

                    {{-- High-Contrast Granular Permission Matrix Table --}}
                    <div style="overflow-x:auto;margin-bottom:1.5rem;">
                        <table class="rbac-matrix-table">
                            <thead>
                                <tr>
                                    <th style="width:28%;">{{ __('app.settings.roles.th_module') }}</th>
                                    <th style="width:14%;">{{ __('app.settings.roles.action_view') }}</th>
                                    <th style="width:14%;">{{ __('app.settings.roles.action_create') }}</th>
                                    <th style="width:14%;">{{ __('app.settings.roles.action_edit') }}</th>
                                    <th style="width:14%;" class="th-delete">🔴 {{ __('app.settings.roles.action_delete') }}</th>
                                    <th style="width:16%;">{{ __('app.settings.roles.action_export') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $modules = [
                                        'contacts'  => ['label' => __('app.entities.people.title') ?? 'Contacts / People', 'icon' => '👤', 'cls' => 'mod-contacts'],
                                        'companies' => ['label' => __('app.entities.companies.title') ?? 'Companies', 'icon' => '🏢', 'cls' => 'mod-companies'],
                                        'deals'     => ['label' => __('app.entities.opportunities.title') ?? 'Deals / Pipelines', 'icon' => '💼', 'cls' => 'mod-deals'],
                                        'quotes'    => ['label' => __('app.entities.quotes.title') ?? 'Quotes & Invoices', 'icon' => '📄', 'cls' => 'mod-quotes'],
                                        'tasks'     => ['label' => __('app.entities.tasks.title') ?? 'Tasks & Activities', 'icon' => '☑️', 'cls' => 'mod-tasks'],
                                        'reports'   => ['label' => __('app.nav.reports'), 'icon' => '📊', 'cls' => 'mod-reports'],
                                        'settings'  => ['label' => __('app.nav.workspace'), 'icon' => '⚙️', 'cls' => 'mod-settings'],
                                    ];
                                @endphp

                                @foreach($modules as $modKey => $modInfo)
                                    @php
                                        $modPerms = $permissionsGrouped[$modKey] ?? [];
                                        $permsByAction = [];
                                        foreach($modPerms as $p) {
                                            $permsByAction[$p['action'] ?? $p['slug']] = $p;
                                        }
                                    @endphp
                                    <tr>
                                        {{-- Module Title & Icon --}}
                                        <td>
                                            <div style="display:flex;align-items:center;gap:0.6rem;">
                                                <span class="module-icon-box {{ $modInfo['cls'] }}">{{ $modInfo['icon'] }}</span>
                                                <span style="font-weight:700;color:#0f172a;font-size:0.9rem;">{{ $modInfo['label'] }}</span>
                                            </div>
                                        </td>

                                        @if($modKey === 'settings')
                                            {{-- Settings Sub-Permissions clean chip container across remaining 5 columns --}}
                                            <td colspan="5" style="text-align:start;padding:0.75rem 1rem;">
                                                <div class="settings-chip-container">
                                                    @foreach($modPerms as $sp)
                                                        @php
                                                            $subKey = str_replace('settings.', '', $sp['slug']);
                                                            $subLabel = match($subKey) {
                                                                'workspace'     => __('app.settings.roles.perm_workspace'),
                                                                'members'       => __('app.settings.roles.perm_members'),
                                                                'roles'         => __('app.settings.roles.perm_roles'),
                                                                'custom_fields' => __('app.settings.roles.perm_custom_fields'),
                                                                default         => $sp['name'],
                                                            };
                                                        @endphp
                                                        <label class="settings-chip" title="{{ $sp['name'] }}">
                                                            <input type="checkbox" name="permissions[]" value="{{ $sp['slug'] }}" class="perm-cb perm-settings">
                                                            <span>{{ $subLabel }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </td>
                                        @else
                                            {{-- Read / View --}}
                                            <td>
                                                @if(isset($permsByAction['view']))
                                                    <input type="checkbox" name="permissions[]" value="{{ $permsByAction['view']['slug'] }}" class="perm-cb perm-view" title="{{ $permsByAction['view']['name'] }}">
                                                @else
                                                    <span style="color:#cbd5e1;">—</span>
                                                @endif
                                            </td>
                                            {{-- Create --}}
                                            <td>
                                                @if(isset($permsByAction['create']))
                                                    <input type="checkbox" name="permissions[]" value="{{ $permsByAction['create']['slug'] }}" class="perm-cb perm-create" title="{{ $permsByAction['create']['name'] }}">
                                                @else
                                                    <span style="color:#cbd5e1;">—</span>
                                                @endif
                                            </td>
                                            {{-- Edit --}}
                                            <td>
                                                @if(isset($permsByAction['edit']))
                                                    <input type="checkbox" name="permissions[]" value="{{ $permsByAction['edit']['slug'] }}" class="perm-cb perm-edit" title="{{ $permsByAction['edit']['name'] }}">
                                                @else
                                                    <span style="color:#cbd5e1;">—</span>
                                                @endif
                                            </td>
                                            {{-- Delete --}}
                                            <td>
                                                @if(isset($permsByAction['delete']))
                                                    <input type="checkbox" name="permissions[]" value="{{ $permsByAction['delete']['slug'] }}" class="perm-cb perm-delete perm-cb-delete" title="{{ $permsByAction['delete']['name'] }}">
                                                @else
                                                    <span style="color:#cbd5e1;">—</span>
                                                @endif
                                            </td>
                                            {{-- Export / Manage --}}
                                            <td>
                                                @if(isset($permsByAction['export']))
                                                    <label style="cursor:pointer;display:inline-flex;align-items:center;gap:0.4rem;padding:0.2rem 0.55rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;font-size:0.75rem;font-weight:600;color:#334155;margin:0;" title="{{ $permsByAction['export']['name'] }}">
                                                        <input type="checkbox" name="permissions[]" value="{{ $permsByAction['export']['slug'] }}" class="perm-cb perm-export">
                                                        <span>{{ __('app.settings.roles.action_export') }}</span>
                                                    </label>
                                                @else
                                                    <span style="color:#cbd5e1;">—</span>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Action Footer --}}
                    <div id="form-actions" style="display:flex;justify-content:flex-end;gap:0.75rem;align-items:center;">
                        <button type="button" class="btn btn-secondary" onclick="resetRoleForm()" style="font-weight:700;padding:0.65rem 1.4rem;border-radius:8px;">
                            {{ __('app.common.cancel') }}
                        </button>
                        <button type="submit" id="save-role-btn" class="btn btn-primary" style="background:linear-gradient(135deg, #4f46e5, #6366f1);border:none;box-shadow:0 4px 14px rgba(79,70,229,0.35);font-weight:800;padding:0.65rem 1.6rem;border-radius:8px;">
                            {{ __('app.settings.roles.save_role') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Roles collection passed securely to JS without HTML attribute escaping issues
const ROLES_DATA = {!! json_encode($roles, JSON_UNESCAPED_UNICODE) !!};

// Localized string templates safely injected via json_encode
const I18N = {
    createRole: {!! json_encode(__('app.settings.roles.create_role')) !!},
    builderDesc: {!! json_encode(__('app.settings.roles.builder_desc')) !!},
    saveRole: {!! json_encode(__('app.settings.roles.save_role')) !!},
    updateRole: {!! json_encode(__('app.settings.roles.update_role_btn')) !!},
    editRoleTitle: {!! json_encode(__('app.settings.roles.edit_role_title')) !!},
    presetRoleTitle: {!! json_encode(__('app.settings.roles.preset_role_title')) !!},
    presetReadonlyNote: {!! json_encode(__('app.settings.roles.preset_readonly_note')) !!}
};

function getRoleById(roleId) {
    return ROLES_DATA.find(r => String(r.id) === String(roleId));
}

function resetRoleForm() {
    const form = document.getElementById('role-form');
    form.action = '/settings/roles';
    document.getElementById('role_id').value = '';
    document.getElementById('role_name').value = '';
    document.getElementById('role_name').disabled = false;
    document.getElementById('role_description').value = '';
    document.getElementById('role_description').disabled = false;

    document.getElementById('form-heading').innerText = I18N.createRole;
    document.getElementById('form-subheading').innerText = I18N.builderDesc;
    document.getElementById('save-role-btn').innerText = I18N.saveRole;
    document.getElementById('save-role-btn').style.display = 'inline-flex';
    document.getElementById('preset-badge').style.display = 'none';
    document.getElementById('matrix-presets-toolbar').style.display = 'flex';

    enableCheckboxes(true);
    applyPreset('editor');
    highlightActiveCard(null);
}

function handleRoleCardClick(roleId) {
    const role = getRoleById(roleId);
    if (!role) return;
    if (role.is_system) {
        viewPreset(role);
    } else {
        editRole(role);
    }
}

function handleEditRole(roleId) {
    const role = getRoleById(roleId);
    if (role) editRole(role);
}

function handleViewPreset(roleId) {
    const role = getRoleById(roleId);
    if (role) viewPreset(role);
}

function editRole(role) {
    const form = document.getElementById('role-form');
    form.action = '/settings/roles/' + role.id + '/update';
    document.getElementById('role_id').value = role.id;
    document.getElementById('role_name').value = role.name;
    document.getElementById('role_name').disabled = false;
    document.getElementById('role_description').value = role.description || '';
    document.getElementById('role_description').disabled = false;

    document.getElementById('form-heading').innerText = I18N.editRoleTitle.replace(':name', role.name);
    document.getElementById('form-subheading').innerText = I18N.builderDesc;
    document.getElementById('save-role-btn').innerText = I18N.updateRole;
    document.getElementById('save-role-btn').style.display = 'inline-flex';
    document.getElementById('preset-badge').style.display = 'none';
    document.getElementById('matrix-presets-toolbar').style.display = 'flex';

    enableCheckboxes(true);
    populatePermissions(role.permissions || []);
    highlightActiveCard(role.id);
}

function viewPreset(role) {
    document.getElementById('role_name').value = role.name;
    document.getElementById('role_name').disabled = true;
    document.getElementById('role_description').value = role.description || '';
    document.getElementById('role_description').disabled = true;

    document.getElementById('form-heading').innerText = I18N.presetRoleTitle.replace(':name', role.name);
    document.getElementById('form-subheading').innerText = I18N.presetReadonlyNote;
    document.getElementById('save-role-btn').style.display = 'none';
    document.getElementById('preset-badge').style.display = 'inline-block';
    document.getElementById('matrix-presets-toolbar').style.display = 'none';

    enableCheckboxes(false);
    populatePermissions(role.permissions || []);
    highlightActiveCard(role.id);
}

function enableCheckboxes(enabled) {
    document.querySelectorAll('.perm-cb').forEach(cb => {
        cb.disabled = !enabled;
    });
}

function populatePermissions(perms) {
    const hasAll = perms.includes('*');
    document.querySelectorAll('.perm-cb').forEach(cb => {
        cb.checked = hasAll || perms.includes(cb.value);
    });
}

function highlightActiveCard(roleId) {
    document.querySelectorAll('.role-card-item').forEach(c => {
        c.classList.remove('active-role');
    });
    if (roleId) {
        const el = document.getElementById('role-card-' + roleId);
        if (el) el.classList.add('active-role');
    }
}

function applyPreset(type) {
    document.querySelectorAll('.perm-cb').forEach(cb => {
        if (type === 'all') {
            cb.checked = true;
        } else if (type === 'none') {
            cb.checked = false;
        } else if (type === 'read_only') {
            cb.checked = cb.classList.contains('perm-view');
        } else if (type === 'editor') {
            cb.checked = cb.classList.contains('perm-view') || cb.classList.contains('perm-create') || cb.classList.contains('perm-edit');
        }
    });
}

// Default to editor preset on load
document.addEventListener('DOMContentLoaded', () => {
    applyPreset('editor');
});
</script>
@endsection
