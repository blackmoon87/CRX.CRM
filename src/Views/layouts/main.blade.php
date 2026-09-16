<!DOCTYPE html>
<html lang="{{ current_locale() }}" dir="{{ is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CRX') — {{ config('app.name', 'CRX CRM') }}</title>
    <link rel="stylesheet" href="{{ asset('css/reset.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/crm.css') }}">
</head>
<body>
    <div class="app-layout">
        {{-- Sidebar --}}
        <aside class="app-sidebar">
            <div class="brand-header">
                <div class="brand-logo">C</div>
                <div>
                    <div class="brand-name">CRX</div>
                    <span class="brand-badge">{{ __('app.tagline') }}</span>
                </div>
            </div>

            {{-- Workspace Switcher --}}
            @if(!empty($workspaces))
                <div class="workspace-selector">
                    <label for="workspaceSelect">{{ __('app.common.workspace') }}</label>
                    <form id="workspaceSwitchForm" method="POST" action="/workspaces/switch">
                        @csrf
                        <select id="workspaceSelect" name="workspace_id">
                            @foreach($workspaces as $ws)
                                <option value="{{ $ws['id'] }}" @selected(($workspace['id'] ?? 0) == $ws['id'])>
                                    {{ $ws['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @endif

            {{-- Navigation Menu --}}
            <nav class="nav-menu">
                <a href="/dashboard" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                    <span>{{ __('app.nav.overview') }}</span>
                </a>
                <a href="/reports" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                    <span>{{ __('app.nav.analytics') }}</span>
                </a>

                <div class="nav-section-title">{{ __('app.nav.crm_entities') }}</div>
                <a href="/opportunities" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <span>{{ __('app.nav.deals') }}</span>
                </a>
                <a href="/companies" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                    <span>{{ __('app.nav.companies') }}</span>
                </a>
                <a href="/people" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span>{{ __('app.nav.contacts') }}</span>
                </a>
                <a href="/tasks" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <span>{{ __('app.nav.tasks') }}</span>
                </a>
                <a href="/quotes" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    <span>{{ __('app.nav.quotes') }}</span>
                </a>

                <div class="nav-section-title">{{ __('app.nav.growth_automation') }}</div>
                <a href="/settings/booking" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span>{{ __('app.nav.scheduler') }}</span>
                </a>
                <a href="/settings/forms" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                    <span>{{ __('app.nav.forms') }}</span>
                </a>
                <a href="/settings/tags" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/><circle cx="7" cy="7" r=".5" fill="currentColor"/></svg>
                    <span>{{ __('app.nav.tags') }}</span>
                </a>
                <a href="/settings/templates" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <span>{{ __('app.nav.snippets') }}</span>
                </a>
                <a href="/settings/workflows" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    <span>{{ __('app.nav.automations') }}</span>
                </a>

                <div class="nav-section-title">{{ __('app.nav.system_admin') }}</div>
                <a href="/settings/recycle-bin" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                    <span>{{ __('app.nav.recycle_bin') }}</span>
                </a>
                <a href="/import" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                    <span>{{ __('app.nav.import_export') }}</span>
                </a>
                <a href="/settings/custom-fields" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                    <span>{{ __('app.nav.custom_fields') }}</span>
                </a>
                <a href="/settings/api-tokens" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m10 15 5-3-5-3v6Z"/></svg>
                    <span>{{ __('app.nav.api_tokens') }}</span>
                </a>
                <a href="/settings/workspace" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                    <span>{{ __('app.nav.workspace') }}</span>
                </a>
                <a href="/settings/roles" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <span>{{ __('app.settings.roles.title') ?? 'Roles & Permissions' }}</span>
                </a>
            </nav>

            {{-- Sidebar Footer --}}
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="user-details">
                        <div class="user-name">{{ auth()->user()?->name ?? 'User' }}</div>
                        <div class="user-role">{{ auth()->user()?->email ?? '' }}</div>
                    </div>
                </div>
                <a href="/logout" class="btn btn-secondary btn-sm" title="{{ __('app.nav.sign_out') }}">
                    <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                </a>
            </div>
        </aside>

        {{-- Main Area --}}
        <main class="app-main">
            <header class="app-topbar">
                <div style="display:flex;align-items:center;gap:0.75rem;">
                    <div style="width:28px;height:28px;border-radius:6px;background:#E0F2FE;border:1.5px solid #BAE6FD;display:flex;align-items:center;justify-content:center;font-size:0.9rem;">
                        🏢
                    </div>
                    <div style="display:flex;align-items:baseline;gap:0.4rem;">
                        <span style="font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-dim);">{{ __('app.common.workspace') }}</span>
                        <span style="font-weight:800;color:var(--text-main);font-size:0.95rem;">{{ $workspace['name'] ?? 'CRX' }}</span>
                    </div>
                </div>

                {{-- Global Command Palette Trigger (Cmd/Ctrl + K) --}}
                <button type="button" onclick="toggleCommandPalette()" style="display:inline-flex;align-items:center;gap:0.6rem;padding:6px 14px;background:#F8FAFC;border:1px solid var(--border);border-radius:8px;cursor:pointer;color:var(--text-dim);font-size:0.82rem;font-weight:500;transition:all 0.12s ease;" onmouseover="this.style.borderColor='var(--primary)';" onmouseout="this.style.borderColor='var(--border)';" title="Global Command Palette (Press Cmd+K or Ctrl+K)">
                    <span>🔍</span>
                    <span>{{ __('app.common.search_prompt') }}</span>
                    <kbd style="font-family:monospace;font-size:0.72rem;font-weight:700;background:#E2E8F0;color:#334155;padding:1px 5px;border-radius:4px;border:1px solid #CBD5E1;">⌘K</kbd>
                </button>

                <div style="display:flex;align-items:center;gap:0.6rem;">
                    {{-- Language Switcher Pill --}}
                    <div class="lang-selector-bar" style="display:inline-flex;align-items:center;gap:2px;background:#F8FAFC;padding:3px;border-radius:8px;border:1px solid var(--border);">
                        <a href="/locale/en" style="text-decoration:none;font-size:11px;font-weight:700;padding:3px 7px;border-radius:5px;color:{{ current_locale() === 'en' ? '#fff' : 'var(--text-dim)' }};background:{{ current_locale() === 'en' ? 'var(--primary)' : 'transparent' }};" title="English">🇺🇸 EN</a>
                        <a href="/locale/ar" style="text-decoration:none;font-size:11px;font-weight:700;padding:3px 7px;border-radius:5px;color:{{ current_locale() === 'ar' ? '#fff' : 'var(--text-dim)' }};background:{{ current_locale() === 'ar' ? 'var(--primary)' : 'transparent' }};" title="العربية">🇸🇦 AR</a>
                        <a href="/locale/fr" style="text-decoration:none;font-size:11px;font-weight:700;padding:3px 7px;border-radius:5px;color:{{ current_locale() === 'fr' ? '#fff' : 'var(--text-dim)' }};background:{{ current_locale() === 'fr' ? 'var(--primary)' : 'transparent' }};" title="Français">🇫🇷 FR</a>
                    </div>

                    <a href="/opportunities/create" class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:0.35rem;">
                        <span>+</span><span>{{ __('app.common.new_deal') }}</span>
                    </a>
                    <a href="/companies/create" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:0.35rem;">
                        <span>+</span><span>{{ __('app.common.new_company') }}</span>
                    </a>
                    <a href="/people/create" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:0.35rem;">
                        <span>+</span><span>{{ __('app.common.new_contact') }}</span>
                    </a>
                </div>
            </header>

            <div class="page-content">
                {{-- Flash Notifications --}}
                @flash('success')
                    <div class="alert alert-success" style="display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-radius:var(--r-sm);box-shadow:var(--shadow-xs);border:1px solid #BBF7D0;background:#F0FDF4;color:#166534;margin-bottom:1.25rem;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span style="font-size:1.2rem;">✅</span>
                            <span style="font-weight:600;">{{ $flashMsg }}</span>
                        </div>
                        <button type="button" onclick="this.closest('.alert').remove()" style="background:none;border:none;cursor:pointer;font-size:1.1rem;color:#166534;font-weight:700;">✕</button>
                    </div>
                @endflash

                @flash('error')
                    <div class="alert alert-danger" style="display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-radius:var(--r-sm);box-shadow:var(--shadow-xs);border:1px solid #FECACA;background:#FEF2F2;color:#991B1B;margin-bottom:1.25rem;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span style="font-size:1.2rem;">⚠️</span>
                            <span style="font-weight:600;">{{ $flashMsg }}</span>
                        </div>
                        <button type="button" onclick="this.closest('.alert').remove()" style="background:none;border:none;cursor:pointer;font-size:1.1rem;color:#991B1B;font-weight:700;">✕</button>
                    </div>
                @endflash

                {{-- Page Main Content --}}
                @yield('content')
            </div>
        </main>
    </div>

    {{-- Spartan 3D AI Assistant Floating Trigger --}}
    <button id="aiAssistantTrigger" class="ai-float-btn" onclick="toggleAiDrawer()" title="{{ __('app.copilot.trigger') }}">
        <span class="ai-sparkle">🤖</span>
        <span>{{ __('app.copilot.trigger') }}</span>
    </button>

    {{-- Slide-Out Drawer & Backdrop --}}
    <div id="aiDrawerOverlay" class="ai-drawer-overlay" onclick="toggleAiDrawer()"></div>
    <div id="aiDrawer" class="ai-drawer">
        <div class="ai-drawer-header">
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-size:22px;">🤖</span>
                <div>
                    <h3 style="font-size:14px;font-weight:800;margin:0;color:var(--text-main);">{{ __('app.copilot.title') }}</h3>
                    <span style="font-size:11px;color:var(--text-dim);font-weight:600;">{{ __('app.copilot.subtitle') }}</span>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:6px;">
                <button class="btn btn-secondary btn-sm" onclick="clearCopilotChat()" title="Reset chat chain" style="padding:2px 8px;font-size:11px;">🧹 {{ __('app.copilot.reset') }}</button>
                <button class="btn btn-secondary btn-sm" onclick="toggleAiDrawer()" style="padding:2px 8px;font-weight:700;">✕</button>
            </div>
        </div>

        <div class="ai-quick-prompts">
            <button class="ai-pill" onclick="sendQuickPrompt('What is the biggest trade in my account, and what MCP action do you recommend for it?')">⚡ Biggest Deal & MCP Action</button>
            <button class="ai-pill" onclick="sendQuickPrompt('Generate a CPQ quote for our top deal')">📄 Create Quote</button>
            <button class="ai-pill" onclick="sendQuickPrompt('Run AI lead score on my primary contact')">🎯 Score Lead</button>
            <button class="ai-pill" onclick="sendQuickPrompt('Give me our public meeting booking link')">📅 Booking Link</button>
        </div>

        <div id="aiMessages" class="ai-messages-list">
            <div class="ai-msg ai-msg-assistant">
                <strong>{{ __('app.copilot.title') }}:</strong>
                <p style="margin:4px 0 0 0;">{{ __('app.copilot.greeting') }}</p>
            </div>
        </div>

        <form id="aiChatForm" onsubmit="handleAiSubmit(event)" class="ai-chat-input-box">
            <input type="text" id="aiUserInput" placeholder="{{ __('app.copilot.placeholder') }}" autocomplete="off">
            <button type="submit" class="btn btn-primary btn-sm" style="padding:6px 14px;font-weight:700;">{{ __('app.copilot.send') }}</button>
        </form>
    </div>

    <script src="{{ asset('js/spartan-dialog.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    <script src="{{ asset('js/ai-assistant.js') }}"></script>
    <script src="{{ asset('js/meeting-reminders.js') }}"></script>
    <script src="{{ asset('js/inline-edit.js') }}"></script>
    <script src="{{ asset('js/command-palette.js') }}"></script>
    @include('partials.command_palette')
    @yield('scripts')
</body>
</html>

