{{-- ─────────────────────────────────────────────────────────────────────────
     SPARTAN 3D COMMAND PALETTE & GLOBAL SHORTCUTS MODAL
     Cmd+K / Ctrl+K Quick Launcher & Linear/Attio Style Navigation
────────────────────────────────────────────────────────────────────────── --}}

<div id="commandPaletteOverlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(15,23,42,0.4); z-index:99999; backdrop-filter:blur(6px); align-items:flex-start; justify-content:center; padding-top:12vh;">
    <div id="commandPaletteBox" style="background:#FFFFFF; border:1px solid var(--border); border-radius:14px; box-shadow:var(--shadow-xl); width:90%; max-width:620px; overflow:hidden;">
        
        <!-- Input Header -->
        <div style="display:flex; align-items:center; gap:0.75rem; padding:1rem 1.25rem; border-bottom:1px solid var(--border); background:#FFFFFF;">
            <span style="font-size:1.2rem; color:var(--text-dim);">🔍</span>
            <input type="text" id="commandPaletteInput" placeholder="{{ __('app.command_palette.placeholder') }}" autocomplete="off" style="width:100%; border:none; background:transparent; font-size:1.05rem; font-weight:600; color:var(--text-main); outline:none; font-family:inherit;">
            <span style="font-size:0.7rem; font-weight:700; background:#F1F5F9; color:#475569; padding:0.2rem 0.5rem; border-radius:4px; border:1px solid #E2E8F0; font-family:monospace;">ESC</span>
        </div>

        <!-- Command List -->
        <div id="commandPaletteResults" style="max-height:380px; overflow-y:auto; padding:0.5rem 0;">
            
            <!-- Quick Actions Group -->
            <div style="padding:0.4rem 1.25rem; font-size:0.72rem; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-dim);">
                ⚡ {{ __('app.command_palette.actions') }}
            </div>
            <a href="/opportunities/create" class="palette-item" data-title="New Deal">
                <span class="palette-icon" style="background:#EFF6FF; color:#0284C7;">💼</span>
                <span style="flex:1; font-weight:700; color:var(--text-main);">{{ __('app.command_palette.create_deal') }}</span>
                <kbd class="palette-kbd">D</kbd>
            </a>
            <a href="/people/create" class="palette-item" data-title="New Contact">
                <span class="palette-icon" style="background:#F3E8FF; color:#7C3AED;">👤</span>
                <span style="flex:1; font-weight:700; color:var(--text-main);">{{ __('app.command_palette.create_contact') }}</span>
                <kbd class="palette-kbd">C</kbd>
            </a>
            <a href="/tasks/create" class="palette-item" data-title="New Task">
                <span class="palette-icon" style="background:#DCFCE7; color:#16A34A;">✅</span>
                <span style="flex:1; font-weight:700; color:var(--text-main);">{{ __('app.command_palette.create_task') }}</span>
                <kbd class="palette-kbd">T</kbd>
            </a>
            <a href="/companies/create" class="palette-item" data-title="New Company">
                <span class="palette-icon" style="background:#E0F2FE; color:#0284C7;">🏢</span>
                <span style="flex:1; font-weight:700; color:var(--text-main);">{{ __('app.command_palette.create_company') }}</span>
            </a>

            <!-- Navigation Group -->
            <div style="padding:0.6rem 1.25rem 0.3rem 1.25rem; font-size:0.72rem; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-dim);">
                📍 {{ __('app.command_palette.navigation') }}
            </div>
            <a href="/dashboard" class="palette-item" data-title="Dashboard Overview">
                <span class="palette-icon">📊</span>
                <span style="flex:1; font-weight:600; color:var(--text-main);">{{ __('app.nav.overview') }}</span>
            </a>
            <a href="/opportunities" class="palette-item" data-title="Deals & Pipeline Kanban">
                <span class="palette-icon">🎯</span>
                <span style="flex:1; font-weight:600; color:var(--text-main);">{{ __('app.nav.deals') }}</span>
            </a>
            <a href="/people" class="palette-item" data-title="Contacts People">
                <span class="palette-icon">👥</span>
                <span style="flex:1; font-weight:600; color:var(--text-main);">{{ __('app.nav.contacts') }}</span>
            </a>
            <a href="/companies" class="palette-item" data-title="Companies Accounts">
                <span class="palette-icon">🏢</span>
                <span style="flex:1; font-weight:600; color:var(--text-main);">{{ __('app.nav.companies') }}</span>
            </a>
            <a href="/tasks" class="palette-item" data-title="Tasks Todo">
                <span class="palette-icon">📋</span>
                <span style="flex:1; font-weight:600; color:var(--text-main);">{{ __('app.nav.tasks') }}</span>
            </a>
            <a href="/settings/workflows" class="palette-item" data-title="Workflow Automations Rules">
                <span class="palette-icon">⚡</span>
                <span style="flex:1; font-weight:600; color:var(--text-main);">{{ __('app.nav.automations') }}</span>
            </a>
            <a href="/reports" class="palette-item" data-title="Analytics Reports Velocity">
                <span class="palette-icon">📈</span>
                <span style="flex:1; font-weight:600; color:var(--text-main);">{{ __('app.nav.analytics') }}</span>
            </a>
            <a href="/settings/recycle-bin" class="palette-item" data-title="Recycle Bin Trash Restore">
                <span class="palette-icon">🗑️</span>
                <span style="flex:1; font-weight:600; color:var(--text-main);">{{ __('app.nav.recycle_bin') }}</span>
            </a>
            <a href="/settings/workspace" class="palette-item" data-title="Workspace Settings Team Members">
                <span class="palette-icon">⚙️</span>
                <span style="flex:1; font-weight:600; color:var(--text-main);">{{ __('app.nav.workspace') }}</span>
            </a>
            <a href="/settings/roles" class="palette-item" data-title="Roles Permissions Access Control RBAC Read Write Delete">
                <span class="palette-icon">🛡️</span>
                <span style="flex:1; font-weight:600; color:var(--text-main);">{{ __('app.settings.roles.title') ?? 'Roles & Permissions' }}</span>
            </a>
            <a href="/settings/api-tokens" class="palette-item" data-title="API Tokens MCP Server Agent Access">
                <span class="palette-icon">🤖</span>
                <span style="flex:1; font-weight:600; color:var(--text-main);">{{ __('app.nav.api_tokens') }}</span>
            </a>
        </div>

        <!-- Footer Shortcuts Legend -->
        <div style="padding:0.75rem 1.25rem; background:#F1F5F9; border-top:1.5px solid var(--border); display:flex; justify-content:space-between; align-items:center; font-size:0.72rem; color:var(--text-dim);">
            <div style="display:flex; gap:0.75rem;">
                <span>{{ __('app.command_palette.navigate') }} <kbd class="palette-kbd">↑</kbd> <kbd class="palette-kbd">↓</kbd></span>
                <span>{{ __('app.command_palette.select') }} <kbd class="palette-kbd">↵</kbd></span>
                <span>{{ __('app.command_palette.close') }} <kbd class="palette-kbd">esc</kbd></span>
            </div>
            <span>{{ __('app.command_palette.shortcuts') }} <kbd class="palette-kbd">?</kbd></span>
        </div>
    </div>
</div>

{{-- Cheatsheet Modal --}}
<div id="shortcutsHelpOverlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(15,23,42,0.4); z-index:99999; backdrop-filter:blur(6px); align-items:center; justify-content:center;">
    <div style="background:#FFFFFF; border:1px solid var(--border); border-radius:14px; box-shadow:var(--shadow-xl); width:90%; max-width:480px; padding:1.5rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid var(--border); padding-bottom:0.5rem;">
            <h3 style="font-size:1.1rem; font-weight:700; margin:0; color:var(--text-main);">⌨️ {{ __('app.command_palette.keyboard_shortcuts') }}</h3>
            <button type="button" onclick="closeShortcutsHelp()" style="background:none; border:none; font-size:1.3rem; cursor:pointer;">✕</button>
        </div>
        <div style="display:flex; flex-direction:column; gap:0.6rem; font-size:0.85rem;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span>{{ __('app.command_palette.title') }}</span>
                <kbd class="palette-kbd">⌘K / Ctrl+K</kbd>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span>{{ __('app.command_palette.create_contact') }}</span>
                <kbd class="palette-kbd">C</kbd>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span>{{ __('app.command_palette.create_deal') }}</span>
                <kbd class="palette-kbd">D</kbd>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span>{{ __('app.command_palette.create_task') }}</span>
                <kbd class="palette-kbd">T</kbd>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span>{{ __('app.command_palette.close') }}</span>
                <kbd class="palette-kbd">Esc</kbd>
            </div>
        </div>
    </div>
</div>

<style>
.palette-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.65rem 1.25rem;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.12s ease;
}
.palette-item:hover, .palette-item.active {
    background: #F1F5F9;
}
.palette-item.active {
    outline: 2px solid var(--primary);
    outline-offset: -2px;
}
.palette-icon {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    flex-shrink: 0;
}
.palette-kbd {
    font-family: monospace;
    font-size: 0.75rem;
    font-weight: 800;
    background: #E2E8F0;
    color: #334155;
    padding: 0.15rem 0.45rem;
    border-radius: 4px;
    border: 1px solid #CBD5E1;
    box-shadow: 0 1px 0 rgba(0,0,0,0.15);
}
</style>
