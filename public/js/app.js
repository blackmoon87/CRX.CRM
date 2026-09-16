/**
 * CRX CRM — Client Interactions
 */

document.addEventListener('DOMContentLoaded', () => {
    // Workspace switcher
    const wsSelect = document.getElementById('workspaceSelect');
    if (wsSelect) {
        wsSelect.addEventListener('change', () => {
            const form = document.getElementById('workspaceSwitchForm');
            if (form) form.submit();
        });
    }

    // Drag and Drop for Kanban Deals
    const dealCards = document.querySelectorAll('.deal-card[draggable="true"]');
    const columns = document.querySelectorAll('.column-body');

    dealCards.forEach(card => {
        card.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('text/plain', card.dataset.id);
            card.style.opacity = '0.5';
        });

        card.addEventListener('dragend', () => {
            card.style.opacity = '1';
        });
    });

    columns.forEach(col => {
        col.addEventListener('dragover', (e) => {
            e.preventDefault();
            col.style.background = 'rgba(56, 189, 248, 0.05)';
        });

        col.addEventListener('dragleave', () => {
            col.style.background = 'transparent';
        });

        col.addEventListener('drop', async (e) => {
            e.preventDefault();
            col.style.background = 'transparent';
            const dealId = e.dataTransfer.getData('text/plain');
            const targetStage = col.dataset.stage;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (!dealId || !targetStage) return;

            const card = document.querySelector(`.deal-card[data-id="${dealId}"]`);
            if (card) {
                col.appendChild(card);
            }

            try {
                const res = await fetch('/opportunities/update-stage', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken || '',
                    },
                    body: JSON.stringify({
                        deal_id: dealId,
                        stage: targetStage,
                        _csrf: csrfToken,
                    }),
                });
                const data = await res.json();
                if (!data.success) {
                    window.location.reload();
                }
            } catch (err) {
                console.error('Stage update failed', err);
                window.location.reload();
            }
        });
    });
});
