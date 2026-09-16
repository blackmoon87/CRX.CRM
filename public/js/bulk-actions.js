/**
 * CRX Bulk Actions Controller (Pure Vanilla JS)
 * Spartan 3D Isometric Design System
 */

(function () {
    if (window.__CRX_BULK_ACTIONS_LOADED__) return;
    window.__CRX_BULK_ACTIONS_LOADED__ = true;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBulkActions);
    } else {
        initBulkActions();
    }

function initBulkActions() {
    const masterCheckbox = document.getElementById('select-all-rows');
    const rowCheckboxes = document.querySelectorAll('.bulk-row-check');
    const bulkBar = document.getElementById('bulk-action-bar');
    const countDisplay = document.getElementById('bulk-selected-count');

    if (!rowCheckboxes.length) return;

    let lastChecked = null;

    function updateBulkState() {
        const checkedBoxes = document.querySelectorAll('.bulk-row-check:checked');
        const count = checkedBoxes.length;

        if (countDisplay) {
            countDisplay.textContent = count;
        }

        if (bulkBar) {
            if (count > 0) {
                bulkBar.style.display = 'block';
            } else {
                bulkBar.style.display = 'none';
            }
        }

        if (masterCheckbox) {
            if (count === 0) {
                masterCheckbox.checked = false;
                masterCheckbox.indeterminate = false;
            } else if (count === rowCheckboxes.length) {
                masterCheckbox.checked = true;
                masterCheckbox.indeterminate = false;
            } else {
                masterCheckbox.checked = false;
                masterCheckbox.indeterminate = true;
            }
        }

        // Highlight selected rows
        rowCheckboxes.forEach(cb => {
            const tr = cb.closest('tr');
            if (tr) {
                if (cb.checked) {
                    tr.classList.add('row-selected');
                } else {
                    tr.classList.remove('row-selected');
                }
            }
        });
    }

    if (masterCheckbox) {
        masterCheckbox.addEventListener('change', () => {
            const isChecked = masterCheckbox.checked;
            rowCheckboxes.forEach(cb => {
                cb.checked = isChecked;
            });
            updateBulkState();
        });
    }

    rowCheckboxes.forEach((cb, idx) => {
        cb.addEventListener('click', (e) => {
            // Shift + click range selection
            if (e.shiftKey && lastChecked) {
                let start = Array.from(rowCheckboxes).indexOf(lastChecked);
                let end = Array.from(rowCheckboxes).indexOf(cb);
                const [low, high] = [Math.min(start, end), Math.max(start, end)];

                for (let i = low; i <= high; i++) {
                    rowCheckboxes[i].checked = lastChecked.checked;
                }
            }
            lastChecked = cb;
            updateBulkState();
        });
    });

    updateBulkState();
}

function deselectAllRows() {
    const rowCheckboxes = document.querySelectorAll('.bulk-row-check');
    const masterCheckbox = document.getElementById('select-all-rows');
    const bulkBar = document.getElementById('bulk-action-bar');

    rowCheckboxes.forEach(cb => {
        cb.checked = false;
        const tr = cb.closest('tr');
        if (tr) tr.classList.remove('row-selected');
    });

    if (masterCheckbox) {
        masterCheckbox.checked = false;
        masterCheckbox.indeterminate = false;
    }

    if (bulkBar) {
        bulkBar.style.display = 'none';
    }
}

function submitBulkAction(action) {
    const checkedBoxes = document.querySelectorAll('.bulk-row-check:checked');
    if (checkedBoxes.length === 0) {
        if (window.SpartanDialog) {
            SpartanDialog.toast('Please select at least one record.', 'warning');
        } else {
            alert('Please select at least one record.');
        }
        return;
    }

    const form = document.getElementById('bulk-action-form');
    const actionInput = document.getElementById('bulk-action-input');
    const idsContainer = document.getElementById('bulk-ids-container');

    if (!form || !actionInput || !idsContainer) return;

    if (action === 'assign') {
        const userSelect = document.getElementById('bulk-assign-user');
        if (!userSelect || !userSelect.value) {
            if (window.SpartanDialog) {
                SpartanDialog.toast('Please select a team member to assign.', 'warning');
            } else {
                alert('Please select a team member to assign.');
            }
            return;
        }
    }

    if (action === 'status') {
        const statusSelect = document.getElementById('bulk-status-val');
        if (!statusSelect || !statusSelect.value) {
            if (window.SpartanDialog) {
                SpartanDialog.toast('Please choose a status to apply.', 'warning');
            } else {
                alert('Please choose a status to apply.');
            }
            return;
        }
    }

    // Populate hidden IDs
    idsContainer.innerHTML = '';
    checkedBoxes.forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = cb.value;
        idsContainer.appendChild(input);
    });

    actionInput.value = action;
    form.submit();
}

function confirmBulkDelete() {
    const checkedBoxes = document.querySelectorAll('.bulk-row-check:checked');
    if (checkedBoxes.length === 0) {
        if (window.SpartanDialog) {
            SpartanDialog.toast('Please select at least one record.', 'warning');
        } else {
            alert('Please select at least one record.');
        }
        return;
    }

    const msg = `Are you sure you want to move ${checkedBoxes.length} selected records to the Recycle Bin?`;
    if (window.SpartanDialog) {
        SpartanDialog.confirm(msg, {
            title: 'Move to Recycle Bin',
            confirmText: 'Move to Recycle Bin',
            danger: true
        }).then(confirmed => {
            if (confirmed) {
                submitBulkAction('delete');
            }
        });
    } else if (confirm(msg)) {
        submitBulkAction('delete');
    }
}

    window.initBulkActions = initBulkActions;
    window.deselectAllRows = deselectAllRows;
    window.submitBulkAction = submitBulkAction;
    window.confirmBulkDelete = confirmBulkDelete;
})();
