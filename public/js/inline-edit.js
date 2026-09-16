/**
 * CRX Spreadsheet-Style Inline Cell Editing Controller
 * Pure Vanilla JS — Spartan 3D Isometric Micro-Feedback
 */

(function () {
    if (window.__CRX_INLINE_EDIT_LOADED__) return;
    window.__CRX_INLINE_EDIT_LOADED__ = true;

    function initInlineEdit() {
        document.querySelectorAll('.editable-cell').forEach(cell => {
            // Prevent duplicate listener attachments
            if (cell.dataset.inlineInitialized) return;
            cell.dataset.inlineInitialized = 'true';

            cell.style.cursor = 'pointer';
            cell.title = 'Double-click to edit directly';

            cell.addEventListener('dblclick', (e) => {
                e.stopPropagation();
                startEditing(cell);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initInlineEdit);
    } else {
        initInlineEdit();
    }

    // Expose init function globally for dynamically re-rendered tables
    window.initInlineEdit = initInlineEdit;

    function startEditing(cell) {
        if (cell.classList.contains('is-editing')) return;
        cell.classList.add('is-editing');

        const entity = cell.dataset.entity;
        const id = cell.dataset.id;
        const field = cell.dataset.field;
        const type = cell.dataset.type || 'text';
        const rawValue = cell.dataset.raw || cell.textContent.trim();

        const originalContent = cell.innerHTML;

        if (type === 'select') {
            let options = {};
            try {
                options = JSON.parse(cell.dataset.options || '{}');
            } catch (e) {
                options = {};
            }

            const select = document.createElement('select');
            select.className = 'spartan-inline-select';
            for (const [val, label] of Object.entries(options)) {
                const opt = document.createElement('option');
                opt.value = val;
                opt.textContent = label;
                if (val === rawValue || label === rawValue) opt.selected = true;
                select.appendChild(opt);
            }

            cell.innerHTML = '';
            cell.appendChild(select);
            select.focus();

            select.addEventListener('change', () => {
                saveEdit(cell, entity, id, field, select.value, select.options[select.selectedIndex].text, originalContent);
            });

            select.addEventListener('blur', () => {
                cell.classList.remove('is-editing');
                cell.innerHTML = originalContent;
            });

            select.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    cell.classList.remove('is-editing');
                    cell.innerHTML = originalContent;
                }
            });
        } else {
            const input = document.createElement('input');
            input.type = type === 'number' ? 'number' : 'text';
            input.value = rawValue === '—' ? '' : rawValue;
            input.className = 'spartan-inline-input';

            cell.innerHTML = '';
            cell.appendChild(input);
            input.focus();
            input.select();

            let isSaving = false;

            const commit = () => {
                if (isSaving) return;
                isSaving = true;
                const newVal = input.value.trim();
                if (newVal === rawValue) {
                    cell.classList.remove('is-editing');
                    cell.innerHTML = originalContent;
                    return;
                }
                saveEdit(cell, entity, id, field, newVal, newVal, originalContent);
            };

            input.addEventListener('blur', commit);

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    commit();
                } else if (e.key === 'Escape') {
                    isSaving = true;
                    cell.classList.remove('is-editing');
                    cell.innerHTML = originalContent;
                }
            });
        }
    }

    function saveEdit(cell, entity, id, field, value, displayValue, originalContent) {
        cell.classList.add('inline-saving');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        fetch('/api/inline-update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                entity: entity,
                id: parseInt(id),
                field: field,
                value: value,
                _csrf: csrfToken,
            }),
        })
        .then(r => r.json())
        .then(res => {
            cell.classList.remove('inline-saving');
            cell.classList.remove('is-editing');

            if (res.success) {
                cell.dataset.raw = res.value;
                cell.textContent = displayValue || '—';
                flashFeedback(cell, 'success');
            } else {
                cell.innerHTML = originalContent;
                flashFeedback(cell, 'error');
                if (window.SpartanDialog) {
                    SpartanDialog.toast(res.error || 'Failed to update field.', 'error');
                } else {
                    alert(res.error || 'Failed to update field.');
                }
            }
        })
        .catch(err => {
            cell.classList.remove('inline-saving');
            cell.classList.remove('is-editing');
            cell.innerHTML = originalContent;
            flashFeedback(cell, 'error');
        });
    }

    function flashFeedback(element, status) {
        const className = status === 'success' ? 'inline-success-flash' : 'inline-error-flash';
        element.classList.add(className);
        setTimeout(() => {
            element.classList.remove(className);
        }, 600);
    }

    // Inject Styles once
    if (!document.getElementById('spartan-inline-edit-style')) {
        const styleEl = document.createElement('style');
        styleEl.id = 'spartan-inline-edit-style';
        styleEl.textContent = `
        .editable-cell {
            transition: background 0.15s ease;
            border-radius: 4px;
            padding: 2px 4px;
        }
        .editable-cell:hover {
            background: rgba(99, 102, 241, 0.08);
            outline: 1px dashed var(--primary, #6366f1);
        }
        .spartan-inline-input {
            width: 100%;
            font-size: inherit;
            font-family: inherit;
            font-weight: 600;
            padding: 3px 6px;
            background: var(--surface, #ffffff);
            color: var(--text, #0f172a);
            border: 2px solid var(--primary, #6366f1);
            box-shadow: 2px 2px 0 var(--border, #0f172a);
            border-radius: 4px;
            outline: none;
        }
        .spartan-inline-select {
            font-size: inherit;
            font-family: inherit;
            font-weight: 600;
            padding: 3px 6px;
            background: var(--surface, #ffffff);
            color: var(--text, #0f172a);
            border: 2px solid var(--primary, #6366f1);
            box-shadow: 2px 2px 0 var(--border, #0f172a);
            border-radius: 4px;
            outline: none;
        }
        .inline-saving {
            position: relative;
            opacity: 0.7;
            pointer-events: none;
        }
        .inline-saving::after {
            content: '';
            display: inline-block;
            width: 12px;
            height: 12px;
            margin-left: 6px;
            border: 2px solid #0284C7;
            border-radius: 50%;
            border-top-color: transparent;
            animation: inlineSpin 0.6s linear infinite;
            vertical-align: middle;
        }
        @keyframes inlineSpin {
            to { transform: rotate(360deg); }
        }
        .inline-success-flash {
            outline: 2px solid #22c55e !important;
            background: rgba(34, 197, 94, 0.15) !important;
            animation: spartanFlash 0.6s ease;
        }
        .inline-error-flash {
            outline: 2px solid #ef4444 !important;
            background: rgba(239, 68, 68, 0.15) !important;
            animation: spartanShake 0.4s ease;
        }
        @keyframes spartanFlash {
            0% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        @keyframes spartanShake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-3px); }
            40%, 80% { transform: translateX(3px); }
        }
        `;
        document.head.appendChild(styleEl);
    }
})();
