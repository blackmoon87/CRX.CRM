/**
 * CRX CRM — Spartan 3D Modern Dialog & Notification Engine
 * Replaces native ugly browser alert(), confirm(), and prompt() with
 * beautiful, accessible, tactile Spartan 3D modal dialogs and toasts.
 */

(function (window) {
    'use strict';

    // Inject Spartan Dialog CSS styles once
    const styleId = 'spartan-dialog-styles';
    if (!document.getElementById(styleId)) {
        const style = document.createElement('style');
        style.id = styleId;
        style.textContent = `
            .spartan-dialog-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(15, 23, 42, 0.65);
                backdrop-filter: blur(4px);
                -webkit-backdrop-filter: blur(4px);
                z-index: 999999;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 16px;
                opacity: 0;
                transition: opacity 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .spartan-dialog-backdrop.is-visible {
                opacity: 1;
            }
            .spartan-dialog-card {
                background: var(--bg-surface, #ffffff);
                color: var(--text-main, #0f172a);
                border: 2px solid var(--border, #0f172a);
                border-radius: var(--r, 14px);
                box-shadow: 6px 6px 0 var(--border, #0f172a);
                width: 100%;
                max-width: 440px;
                padding: 24px;
                transform: scale(0.92) translateY(12px);
                transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1);
                font-family: var(--font-stack, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
            }
            .spartan-dialog-backdrop.is-visible .spartan-dialog-card {
                transform: scale(1) translateY(0);
            }
            .spartan-dialog-header {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 12px;
            }
            .spartan-dialog-icon {
                width: 40px;
                height: 40px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
                flex-shrink: 0;
                border: 2px solid var(--border, #0f172a);
                box-shadow: 2px 2px 0 var(--border, #0f172a);
            }
            .spartan-dialog-icon.danger {
                background: #FEE2E2;
                color: #DC2626;
                border-color: #DC2626;
                box-shadow: 2px 2px 0 #DC2626;
            }
            .spartan-dialog-icon.warning {
                background: #FEF3C7;
                color: #D97706;
                border-color: #D97706;
                box-shadow: 2px 2px 0 #D97706;
            }
            .spartan-dialog-icon.info {
                background: #E0F2FE;
                color: #0284C7;
                border-color: #0284C7;
                box-shadow: 2px 2px 0 #0284C7;
            }
            .spartan-dialog-icon.success {
                background: #DCFCE7;
                color: #16A34A;
                border-color: #16A34A;
                box-shadow: 2px 2px 0 #16A34A;
            }
            .spartan-dialog-title {
                font-size: 1.15rem;
                font-weight: 800;
                margin: 0;
                line-height: 1.3;
            }
            .spartan-dialog-body {
                font-size: 0.95rem;
                color: var(--text-muted, #334155);
                line-height: 1.5;
                margin-bottom: 20px;
                word-break: break-word;
            }
            .spartan-dialog-actions {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
            }
            .spartan-dialog-btn {
                padding: 9px 18px;
                font-size: 0.88rem;
                font-weight: 700;
                border-radius: var(--r-sm, 8px);
                cursor: pointer;
                border: 2px solid var(--border, #0f172a);
                box-shadow: 3px 3px 0 var(--border, #0f172a);
                transition: all 0.1s ease;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }
            .spartan-dialog-btn:hover {
                transform: translate(-1px, -1px);
                box-shadow: 4px 4px 0 var(--border, #0f172a);
            }
            .spartan-dialog-btn:active {
                transform: translate(2px, 2px);
                box-shadow: 1px 1px 0 var(--border, #0f172a);
            }
            .spartan-dialog-btn-primary {
                background: var(--primary, #0284C7);
                color: #ffffff;
            }
            .spartan-dialog-btn-danger {
                background: #DC2626;
                color: #ffffff;
                border-color: #991B1B;
                box-shadow: 3px 3px 0 #991B1B;
            }
            .spartan-dialog-btn-danger:hover {
                box-shadow: 4px 4px 0 #991B1B;
            }
            .spartan-dialog-btn-secondary {
                background: var(--bg-surface, #ffffff);
                color: var(--text-main, #0f172a);
            }
            
            /* Toasts Container */
            .spartan-toast-container {
                position: fixed;
                top: 24px;
                right: 24px;
                z-index: 9999999;
                display: flex;
                flex-direction: column;
                gap: 10px;
                pointer-events: none;
            }
            .spartan-toast {
                pointer-events: auto;
                background: var(--bg-surface, #ffffff);
                color: var(--text-main, #0f172a);
                border: 2px solid var(--border, #0f172a);
                box-shadow: 4px 4px 0 var(--border, #0f172a);
                border-radius: var(--r-sm, 8px);
                padding: 12px 18px;
                display: flex;
                align-items: center;
                gap: 12px;
                min-width: 280px;
                max-width: 400px;
                animation: spartanToastIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
                font-size: 0.9rem;
                font-weight: 600;
            }
            .spartan-toast.fade-out {
                opacity: 0;
                transform: translateX(30px);
                transition: all 0.2s ease;
            }
            @keyframes spartanToastIn {
                from { opacity: 0; transform: translateY(-16px) scale(0.95); }
                to { opacity: 1; transform: translateY(0) scale(1); }
            }
        `;
        document.head.appendChild(style);
    }

    const SpartanDialog = {
        /**
         * Show sleek alert dialog
         */
        alert(message, titleOrOpts = 'Notice') {
            return new Promise((resolve) => {
                let title = typeof titleOrOpts === 'string' ? titleOrOpts : (titleOrOpts.title || 'Notice');
                let type = typeof titleOrOpts === 'object' && titleOrOpts.type ? titleOrOpts.type : 'info';
                
                let icon = 'ℹ️';
                if (type === 'danger' || /error|failed|alert/i.test(title)) { icon = '⚠️'; type = 'danger'; }
                else if (type === 'success') { icon = '✅'; }
                else if (type === 'warning') { icon = '⚡'; }

                const backdrop = document.createElement('div');
                backdrop.className = 'spartan-dialog-backdrop';
                backdrop.innerHTML = `
                    <div class="spartan-dialog-card" role="dialog" aria-modal="true">
                        <div class="spartan-dialog-header">
                            <div class="spartan-dialog-icon ${type}">${icon}</div>
                            <h3 class="spartan-dialog-title">${escapeHtml(title)}</h3>
                        </div>
                        <div class="spartan-dialog-body">${escapeHtml(message)}</div>
                        <div class="spartan-dialog-actions">
                            <button type="button" class="spartan-dialog-btn spartan-dialog-btn-primary" id="spartan-alert-ok">Got it</button>
                        </div>
                    </div>
                `;

                document.body.appendChild(backdrop);
                requestAnimationFrame(() => backdrop.classList.add('is-visible'));

                const okBtn = backdrop.querySelector('#spartan-alert-ok');
                okBtn.focus();

                const cleanup = () => {
                    backdrop.classList.remove('is-visible');
                    setTimeout(() => backdrop.remove(), 200);
                    document.removeEventListener('keydown', keyHandler);
                    resolve();
                };

                const keyHandler = (e) => {
                    if (e.key === 'Escape' || e.key === 'Enter') {
                        e.preventDefault();
                        cleanup();
                    }
                };

                okBtn.addEventListener('click', cleanup);
                document.addEventListener('keydown', keyHandler);
            });
        },

        /**
         * Show sleek confirm dialog
         */
        confirm(message, options = {}) {
            return new Promise((resolve) => {
                const title = options.title || 'Confirm Action';
                const confirmText = options.confirmText || 'Confirm';
                const cancelText = options.cancelText || 'Cancel';
                
                const isDanger = options.danger !== undefined 
                    ? options.danger 
                    : /delete|remove|purge|recycle|revoke|destroy|cancel/i.test(message + ' ' + title);

                const icon = isDanger ? '🗑️' : '❓';
                const type = isDanger ? 'danger' : 'info';
                const confirmBtnClass = isDanger ? 'spartan-dialog-btn-danger' : 'spartan-dialog-btn-primary';

                const backdrop = document.createElement('div');
                backdrop.className = 'spartan-dialog-backdrop';
                backdrop.innerHTML = `
                    <div class="spartan-dialog-card" role="dialog" aria-modal="true">
                        <div class="spartan-dialog-header">
                            <div class="spartan-dialog-icon ${type}">${icon}</div>
                            <h3 class="spartan-dialog-title">${escapeHtml(title)}</h3>
                        </div>
                        <div class="spartan-dialog-body">${escapeHtml(message)}</div>
                        <div class="spartan-dialog-actions">
                            <button type="button" class="spartan-dialog-btn spartan-dialog-btn-secondary" id="spartan-confirm-cancel">${escapeHtml(cancelText)}</button>
                            <button type="button" class="spartan-dialog-btn ${confirmBtnClass}" id="spartan-confirm-ok">${escapeHtml(confirmText)}</button>
                        </div>
                    </div>
                `;

                document.body.appendChild(backdrop);
                requestAnimationFrame(() => backdrop.classList.add('is-visible'));

                const okBtn = backdrop.querySelector('#spartan-confirm-ok');
                const cancelBtn = backdrop.querySelector('#spartan-confirm-cancel');
                okBtn.focus();

                const cleanup = (confirmed) => {
                    backdrop.classList.remove('is-visible');
                    setTimeout(() => backdrop.remove(), 200);
                    document.removeEventListener('keydown', keyHandler);
                    resolve(confirmed);
                };

                const keyHandler = (e) => {
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        cleanup(false);
                    }
                };

                okBtn.addEventListener('click', () => cleanup(true));
                cancelBtn.addEventListener('click', () => cleanup(false));
                document.addEventListener('keydown', keyHandler);
            });
        },

        /**
         * Floating corner toast notification
         */
        toast(message, type = 'info', duration = 3200) {
            let container = document.querySelector('.spartan-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'spartan-toast-container';
                document.body.appendChild(container);
            }

            const icons = {
                success: '✅',
                error: '❌',
                danger: '⚠️',
                warning: '⚡',
                info: 'ℹ️'
            };
            const icon = icons[type] || 'ℹ️';

            const toast = document.createElement('div');
            toast.className = `spartan-toast ${type}`;
            toast.innerHTML = `<span>${icon}</span> <span>${escapeHtml(message)}</span>`;

            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('fade-out');
                setTimeout(() => toast.remove(), 220);
            }, duration);
        }
    };

    function escapeHtml(str) {
        if (typeof str !== 'string') return String(str || '');
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Expose global APIs
    window.SpartanDialog = SpartanDialog;

    // Gracefully override window.alert
    window.alert = function (msg) {
        SpartanDialog.alert(msg);
    };

    /**
     * Automatic Global Form Interceptor for `onsubmit="return confirm('...')"`
     * Detects traditional confirm() calls and seamlessly switches them to SpartanDialog.confirm()!
     */
    document.addEventListener('DOMContentLoaded', () => {
        // Intercept forms with inline onsubmit containing confirm
        document.querySelectorAll('form[onsubmit]').forEach(form => {
            const attr = form.getAttribute('onsubmit') || '';
            const match = attr.match(/return\s+confirm\((['"])(.*?)\1\)/);
            if (match) {
                const confirmMsg = match[2];
                form.removeAttribute('onsubmit'); // Remove native handler
                form.addEventListener('submit', function (e) {
                    if (form.dataset.spartanConfirmed === 'true') {
                        return; // already confirmed, allow native submission
                    }
                    e.preventDefault();
                    SpartanDialog.confirm(confirmMsg).then(confirmed => {
                        if (confirmed) {
                            form.dataset.spartanConfirmed = 'true';
                            form.submit();
                        }
                    });
                });
            }
        });

        // Also intercept buttons with onclick containing confirm
        document.querySelectorAll('button[onclick]').forEach(btn => {
            const attr = btn.getAttribute('onclick') || '';
            const match = attr.match(/return\s+confirm\((['"])(.*?)\1\)/);
            if (match) {
                const confirmMsg = match[2];
                btn.removeAttribute('onclick');
                btn.addEventListener('click', function (e) {
                    const form = btn.closest('form');
                    if (form) {
                        if (form.dataset.spartanConfirmed === 'true') return;
                        e.preventDefault();
                        SpartanDialog.confirm(confirmMsg).then(confirmed => {
                            if (confirmed) {
                                form.dataset.spartanConfirmed = 'true';
                                btn.click();
                            }
                        });
                    }
                });
            }
        });
    });

})(window);
