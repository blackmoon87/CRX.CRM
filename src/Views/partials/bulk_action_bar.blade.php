@php
    $workspaceUsers = (new \App\Models\User)->table()->select('id', 'name', 'email')->get();
@endphp

<div id="bulk-action-bar" class="bulk-action-bar" style="display: none;">
    <form id="bulk-action-form" method="POST" action="{{ $bulkRoute }}" style="display: contents;">
        <input type="hidden" name="bulk_action" id="bulk-action-input" value="">
        <input type="hidden" name="_csrf" value="{{ $_SESSION['_csrf'] ?? '' }}">
        <div id="bulk-ids-container" style="display: none;"></div>

        <div class="bulk-bar-container">
            <div class="bulk-counter-pill">
                <span id="bulk-selected-count">0</span> {{ __('app.common.results') }}
            </div>

            <!-- Reassign Owner Action -->
            <div class="bulk-action-group">
                <select name="assigned_user_id" id="bulk-assign-user" class="bulk-select">
                    <option value="">👤 {{ __('app.common.name') }}...</option>
                    @foreach($workspaceUsers as $u)
                        <option value="{{ $u['id'] }}">{{ $u['name'] ?: $u['email'] }}</option>
                    @endforeach
                </select>
                <button type="button" class="spartan-btn-sm" onclick="submitBulkAction('assign')">{{ __('app.common.save') }}</button>
            </div>

            <!-- Status Action if applicable -->
            @if(!empty($statuses))
                <div class="bulk-action-group">
                    <select name="status" id="bulk-status-val" class="bulk-select">
                        <option value="">🏷️ {{ __('app.bulk.change_status') }}...</option>
                        @foreach($statuses as $val => $lbl)
                            <option value="{{ $val }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="spartan-btn-sm" onclick="submitBulkAction('status')">{{ __('app.common.save') }}</button>
                </div>
            @endif

            <!-- Email Campaign to Selected -->
            @if(($entityType ?? '') === 'people')
                <button type="button" class="spartan-btn-sm" style="background:#2563EB;color:#fff;font-weight:700;" onclick="openBulkEmailModal()" title="{{ __('app.bulk.send_email') }}">
                    ✉️ {{ __('app.bulk.send_email') }}
                </button>
            @endif

            <!-- Export Selected -->
            @if(user_can(($entityType ?? 'contacts') . '.export') || in_array(active_user_role(), ['owner', 'admin'], true))
            <button type="button" class="spartan-btn-sm" onclick="submitBulkAction('export')" title="{{ __('app.common.export_csv') }}">
                📤 {{ __('app.common.export_csv') }}
            </button>
            @endif

            <!-- Move to Recycle Bin -->
            @if(user_can(($entityType ?? 'contacts') . '.delete') || in_array(active_user_role(), ['owner', 'admin'], true))
            <button type="button" class="spartan-btn-sm spartan-btn-danger" onclick="confirmBulkDelete()" title="{{ __('app.nav.recycle_bin') }}">
                🗑️ {{ __('app.nav.recycle_bin') }}
            </button>
            @endif

            <!-- Deselect All -->
            <button type="button" class="spartan-btn-sm spartan-btn-ghost" onclick="deselectAllRows()" title="{{ __('app.bulk.clear') }}">
                ✕ {{ __('app.bulk.clear') }}
            </button>
        </div>
    </form>
</div>

@if(($entityType ?? '') === 'people')
<div id="bulk-email-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.5);backdrop-filter:blur(4px);z-index:10000;align-items:center;justify-content:center;">
    <div style="background:#ffffff;border:1px solid var(--border);border-radius:12px;box-shadow:0 20px 25px -5px rgba(15, 23, 42, 0.2);width:100%;max-width:520px;padding:22px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <h3 style="margin:0;font-size:1.15rem;font-weight:700;color:var(--text-main);">✉️ {{ __('app.bulk.email_modal.title') }}</h3>
            <button type="button" onclick="closeBulkEmailModal()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:var(--text-dim);">✕</button>
        </div>
        <p style="font-size:0.85rem;color:var(--text-dim);margin-bottom:14px;">
            Send tracked emails to <strong id="bulk-email-target-count" style="color:var(--primary);">0</strong> selected contacts.
            Variables supported: <code>{first_name}</code>, <code>{last_name}</code>, <code>{name}</code>.
        </p>
        <div style="margin-bottom:12px;">
            <label style="display:block;font-size:0.85rem;font-weight:700;margin-bottom:4px;color:var(--text-main);">{{ __('app.bulk.email_modal.subject') }}</label>
            <input type="text" id="bulk-email-subject-input" class="form-control" placeholder="{{ __('app.bulk.email_modal.subject_ph') }}" style="width:100%;" required>
        </div>
        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:0.85rem;font-weight:700;margin-bottom:4px;color:var(--text-main);">{{ __('app.bulk.email_modal.body') }}</label>
            <textarea id="bulk-email-body-input" class="form-control" rows="5" placeholder="{{ __('app.bulk.email_modal.body_ph') }}" style="width:100%;font-family:inherit;" required></textarea>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;">
            <button type="button" class="btn btn-secondary" onclick="closeBulkEmailModal()">{{ __('app.common.cancel') }}</button>
            <button type="button" class="btn btn-primary" onclick="submitBulkEmail()">🚀 {{ __('app.bulk.email_modal.send_btn') }}</button>
        </div>
    </div>
</div>

<script>
function openBulkEmailModal() {
    var checked = document.querySelectorAll('.bulk-row-check:checked');
    if (checked.length === 0) {
        if (window.SpartanDialog) {
            SpartanDialog.toast('Please select at least one contact.', 'warning');
        } else {
            alert('Please select at least one contact.');
        }
        return;
    }
    document.getElementById('bulk-email-target-count').innerText = checked.length;
    document.getElementById('bulk-email-modal').style.display = 'flex';
}
function closeBulkEmailModal() {
    document.getElementById('bulk-email-modal').style.display = 'none';
}
function submitBulkEmail() {
    var sub = document.getElementById('bulk-email-subject-input').value.trim();
    var msg = document.getElementById('bulk-email-body-input').value.trim();
    if (!sub || !msg) {
        if (window.SpartanDialog) {
            SpartanDialog.toast('Please enter both subject and message body.', 'warning');
        } else {
            alert('Please enter both subject and message body.');
        }
        return;
    }
    var form = document.getElementById('bulk-action-form');
    
    var sInput = document.createElement('input');
    sInput.type = 'hidden';
    sInput.name = 'email_subject';
    sInput.value = sub;
    form.appendChild(sInput);

    var mInput = document.createElement('input');
    mInput.type = 'hidden';
    mInput.name = 'email_message';
    mInput.value = msg;
    form.appendChild(mInput);

    submitBulkAction('email');
}
</script>
@endif

<style>
.bulk-action-bar {
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 9999;
    animation: bulkSlideUp 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes bulkSlideUp {
    from {
        opacity: 0;
        transform: translate(-50%, 15px);
    }
    to {
        opacity: 1;
        transform: translate(-50%, 0);
    }
}

.bulk-bar-container {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #ffffff;
    padding: 8px 16px;
    border: 1px solid var(--border);
    box-shadow: 0 12px 30px -4px rgba(15, 23, 42, 0.16), 0 4px 6px -2px rgba(15, 23, 42, 0.05);
    border-radius: 12px;
    white-space: nowrap;
}

.bulk-counter-pill {
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    color: #ffffff;
    font-size: 11.5px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 6px;
    letter-spacing: 0.03em;
    box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
}

.bulk-action-group {
    display: flex;
    align-items: center;
    gap: 6px;
}

.bulk-select {
    padding: 6px 10px;
    font-size: 12.5px;
    font-weight: 500;
    background: #f8fafc;
    color: var(--ink);
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    outline: none;
    cursor: pointer;
    transition: border-color 0.15s ease;
}

.bulk-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.spartan-btn-sm {
    padding: 6px 12px;
    font-size: 12.5px;
    font-weight: 600;
    background: #ffffff;
    color: var(--text-muted);
    border: 1px solid var(--border);
    border-radius: 6px;
    cursor: pointer;
    box-shadow: var(--shadow-xs);
    transition: all 0.12s ease;
}

.spartan-btn-sm:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: var(--ink);
}

.spartan-btn-danger {
    background: #fef2f2 !important;
    color: #b91c1c !important;
    border-color: #fecaca !important;
}

.spartan-btn-danger:hover {
    background: #fee2e2 !important;
    border-color: #f87171 !important;
    color: #991b1b !important;
}

.spartan-btn-ghost {
    background: transparent;
    color: var(--text-dim);
    border-color: var(--border);
    box-shadow: none;
}

.spartan-btn-ghost:hover {
    color: var(--danger);
    border-color: #fecaca;
    background: #fef2f2;
}
</style>
