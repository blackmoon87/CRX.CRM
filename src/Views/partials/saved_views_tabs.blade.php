@php
    $wsId = (int)($workspaceId ?? 1);
    $uId = (int)($userId ?? 1);
    $savedViews = (new \App\Models\SavedView)->table()
        ->where('workspace_id', $wsId)
        ->where('entity_type', $entityType)
        ->where(function(\Spartan\QueryBuilder $q) use ($uId) {
            $q->where('user_id', $uId)
              ->orWhere('is_shared', 1);
        })
        ->orderBy('is_default', 'DESC')
        ->orderBy('name', 'ASC')
        ->get();

    $activeViewId = (int)($_GET['view_id'] ?? 0);
    $currentGetClean = array_filter($_GET, fn($k) => !in_array($k, ['view_id', '_csrf']), ARRAY_FILTER_USE_KEY);
    $hasActiveQuery = !empty($currentGetClean['search']) || !empty($currentGetClean['filter_val']) || !empty($currentGetClean['filter_col']);
@endphp

<div class="saved-views-wrapper">
    <div class="saved-views-tabs">
        {{-- Default "All" Tab --}}
        <a href="/{{ $entityType }}" class="saved-view-tab {{ $activeViewId === 0 ? 'active' : '' }}">
            <span class="tab-icon">📑</span>
            <span class="tab-title">{{ __('app.common.all') }}</span>
        </a>

        {{-- Custom Saved View Tabs --}}
        @foreach($savedViews as $sv)
            @php
                $svParams = json_decode($sv['query_params'] ?? '{}', true) ?: [];
                $svParams['view_id'] = $sv['id'];
                $tabUrl = '/' . $entityType . '?' . http_build_query($svParams);
                $isCurrent = ($activeViewId === (int)$sv['id']);
            @endphp
            <div class="saved-view-tab-wrapper {{ $isCurrent ? 'active' : '' }}">
                <a href="{{ $tabUrl }}" class="saved-view-tab {{ $isCurrent ? 'active' : '' }}">
                    <span class="tab-icon">{{ $sv['icon'] ?: '📁' }}</span>
                    <span class="tab-title">{{ $sv['name'] }}</span>
                    @if(!empty($sv['is_default']))
                        <span class="default-star" title="Default view">★</span>
                    @endif
                    @if(!empty($sv['is_shared']))
                        <span class="shared-badge" title="Shared with workspace">👥</span>
                    @endif
                </a>

                @if($isCurrent)
                    <div class="view-settings-dropdown">
                        <button type="button" class="view-settings-btn" onclick="toggleViewMenu({{ $sv['id'] }})">▾</button>
                        <div id="view-menu-{{ $sv['id'] }}" class="view-dropdown-menu" style="display: none;">
                            <form method="POST" action="/saved-views/{{ $sv['id'] }}/update">
                                <input type="hidden" name="_csrf" value="{{ $_SESSION['_csrf'] ?? '' }}">
                                <input type="hidden" name="query_params" value="{{ htmlspecialchars(json_encode($currentGetClean)) }}">
                                <button type="submit" class="menu-action-btn">🔄 {{ __('app.common.save_changes') }}</button>
                            </form>
                            @if(empty($sv['is_default']))
                                <form method="POST" action="/saved-views/{{ $sv['id'] }}/default">
                                    <input type="hidden" name="_csrf" value="{{ $_SESSION['_csrf'] ?? '' }}">
                                    <button type="submit" class="menu-action-btn">⭐ {{ __('app.common.status') }}</button>
                                </form>
                            @endif
                            <form method="POST" action="/saved-views/{{ $sv['id'] }}/delete" data-confirm="{{ __('app.dialogs.confirm_delete') }}">
                                <input type="hidden" name="_csrf" value="{{ $_SESSION['_csrf'] ?? '' }}">
                                <button type="submit" class="menu-action-btn delete-action">🗑️ {{ __('app.common.delete') }}</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach

        {{-- Save Current View Button --}}
        <button type="button" class="spartan-save-view-btn" onclick="openSaveViewModal()">
            <span>➕</span> {{ __('app.filter_bar.save_current_view') }}
        </button>
    </div>
</div>

{{-- Spartan 3D Modal: Save New View --}}
<div id="save-view-modal" class="spartan-modal-backdrop" style="display: none;">
    <div class="spartan-modal-box">
        <div class="modal-header">
            <h3 class="modal-title">💾 {{ __('app.filter_bar.save_current_view') }}</h3>
            <button type="button" class="modal-close-btn" onclick="closeSaveViewModal()">✕</button>
        </div>
        <form method="POST" action="/saved-views">
            <input type="hidden" name="_csrf" value="{{ $_SESSION['_csrf'] ?? '' }}">
            <input type="hidden" name="entity_type" value="{{ $entityType }}">
            <input type="hidden" name="query_params" value="{{ htmlspecialchars(json_encode($currentGetClean)) }}">

            <div class="modal-body">
                <div class="spartan-form-field">
                    <label>{{ __('app.common.name') }}</label>
                    <input type="text" name="name" class="spartan-input" placeholder="{{ __('app.filter_bar.view_name_prompt') }}" required autofocus>
                </div>

                <div class="spartan-form-field" style="margin-top: 12px;">
                    <label>Icon</label>
                    <input type="text" name="icon" class="spartan-input" value="⭐" style="width: 70px; text-align: center; font-size: 18px;">
                </div>

                <div class="spartan-form-field" style="margin-top: 14px; display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="is_shared" id="is_shared" value="1" style="width: 18px; height: 18px; cursor: pointer;">
                    <label for="is_shared" style="cursor: pointer; font-weight: 600; font-size: 13px;">{{ __('app.common.all') }}</label>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="spartan-btn-secondary" onclick="closeSaveViewModal()">{{ __('app.common.cancel') }}</button>
                <button type="submit" class="spartan-btn-primary">{{ __('app.common.save') }}</button>
            </div>
        </form>
    </div>
</div>

<style>
.saved-views-wrapper {
    margin-bottom: 12px;
}

.saved-views-tabs {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.saved-view-tab-wrapper {
    display: inline-flex;
    align-items: center;
    position: relative;
}

.saved-view-tab {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-muted);
    background: #ffffff;
    border: 1px solid var(--border);
    box-shadow: var(--shadow-xs);
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.15s ease;
}

.saved-view-tab:hover {
    color: var(--ink);
    background: #f8fafc;
    border-color: #cbd5e1;
}

.saved-view-tab.active {
    background: #eef2ff;
    color: var(--primary);
    border-color: #c7d2fe;
    font-weight: 700;
}

.default-star {
    color: #eab308;
    font-size: 13px;
    line-height: 1;
}

.shared-badge {
    font-size: 12px;
    opacity: 0.8;
}

.view-settings-dropdown {
    position: relative;
    margin-left: -5px;
    z-index: 10;
}

.view-settings-btn {
    background: #ffffff;
    border: 1px solid var(--border);
    border-left: none;
    padding: 6px 8px;
    font-size: 11px;
    font-weight: 700;
    border-radius: 0 8px 8px 0;
    cursor: pointer;
    box-shadow: var(--shadow-xs);
    color: var(--text-muted);
}

.view-dropdown-menu {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    background: #ffffff;
    border: 1px solid var(--border);
    box-shadow: var(--shadow-md);
    border-radius: 8px;
    padding: 6px;
    min-width: 190px;
    z-index: 100;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.menu-action-btn {
    width: 100%;
    text-align: left;
    background: transparent;
    border: none;
    padding: 6px 10px;
    font-size: 12.5px;
    font-weight: 500;
    color: var(--ink);
    cursor: pointer;
    border-radius: 6px;
    transition: background 0.1s ease;
}

.menu-action-btn:hover {
    background: #f1f5f9;
}

.menu-action-btn.delete-action:hover {
    background: #fee2e2;
    color: #ef4444;
}

.spartan-save-view-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    font-size: 12.5px;
    font-weight: 600;
    background: transparent;
    color: var(--text-muted);
    border: 1px dashed #cbd5e1;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.spartan-save-view-btn:hover {
    border-style: solid;
    border-color: var(--primary);
    color: var(--primary);
    background: #f8faff;
}

.spartan-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(4px);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.spartan-modal-box {
    background: #ffffff;
    border: 1px solid var(--border);
    box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.15);
    border-radius: 12px;
    width: 100%;
    max-width: 420px;
    padding: 22px;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.modal-title {
    font-size: 16px;
    font-weight: 700;
    margin: 0;
    color: var(--ink);
}

.modal-close-btn {
    background: transparent;
    border: none;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    color: var(--text-dim);
}

.spartan-input {
    width: 100%;
    padding: 8px 12px;
    font-size: 13.5px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    outline: none;
    box-shadow: var(--shadow-xs);
}

.spartan-input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 20px;
}

.spartan-btn-primary {
    padding: 7px 16px;
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    color: #ffffff;
    font-weight: 600;
    border: 1px solid #4338ca;
    border-radius: 6px;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
}

.spartan-btn-secondary {
    padding: 7px 16px;
    background: #ffffff;
    color: var(--text-muted);
    font-weight: 600;
    border: 1px solid var(--border);
    border-radius: 6px;
    cursor: pointer;
}
</style>

<script>
function toggleViewMenu(id) {
    const menu = document.getElementById('view-menu-' + id);
    if (menu) {
        menu.style.display = menu.style.display === 'none' ? 'flex' : 'none';
    }
}

function openSaveViewModal() {
    const modal = document.getElementById('save-view-modal');
    if (modal) modal.style.display = 'flex';
}

function closeSaveViewModal() {
    const modal = document.getElementById('save-view-modal');
    if (modal) modal.style.display = 'none';
}

// Close menus on outside click
document.addEventListener('click', (e) => {
    if (!e.target.closest('.view-settings-dropdown')) {
        document.querySelectorAll('.view-dropdown-menu').forEach(m => m.style.display = 'none');
    }
});
</script>
