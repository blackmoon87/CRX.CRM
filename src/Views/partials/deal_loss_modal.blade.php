{{-- Spartan 3D Deal Outcome & Attribution Modal --}}
<div id="deal-outcome-modal" class="spartan-modal-backdrop" style="display: none;">
    <div class="spartan-modal-box">
        <div class="modal-header">
            <h3 class="modal-title" id="outcome-modal-title">📊 Close Deal Attribution</h3>
            <button type="button" class="modal-close-btn" onclick="closeOutcomeModal()">✕</button>
        </div>
        <form id="outcome-modal-form" method="POST" action="/opportunities/update-stage">
            <input type="hidden" name="_csrf" value="{{ $_SESSION['_csrf'] ?? '' }}">
            <input type="hidden" name="deal_id" id="outcome-deal-id" value="">
            <input type="hidden" name="stage" id="outcome-stage" value="">
            <input type="hidden" name="_redirect" value="{{ $_SERVER['REQUEST_URI'] ?? '/opportunities' }}">

            <div class="modal-body">
                <div class="spartan-form-field">
                    <label id="outcome-reason-label" style="font-weight: 700; font-size: 13px;">Reason / Category</label>
                    <select name="lost_reason" id="outcome-reason-select" class="spartan-input" style="background: var(--bg); font-weight: 600;">
                        <option value="Price / Budget Too High">💰 Price / Budget Too High</option>
                        <option value="Competitor Won">⚔️ Competitor Won</option>
                        <option value="Missing Required Features">🧩 Missing Required Features</option>
                        <option value="Timing / Project Postponed">⏳ Timing / Project Postponed</option>
                        <option value="Ghosted / Unresponsive">👻 Ghosted / Unresponsive</option>
                        <option value="Other">📝 Other</option>
                    </select>
                </div>

                <div class="spartan-form-field" id="competitor-field-group" style="margin-top: 12px;">
                    <label style="font-weight: 700; font-size: 13px;">Competitor Name (optional)</label>
                    <input type="text" name="competitor_name" class="spartan-input" placeholder="e.g. Acme Corp / Competitor X">
                </div>

                <div class="spartan-form-field" style="margin-top: 12px;">
                    <label style="font-weight: 700; font-size: 13px;">Executive Notes & Insights</label>
                    <textarea name="notes" class="spartan-input" rows="3" placeholder="Key takeaways, objections, or lessons learned..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="spartan-btn-secondary" onclick="closeOutcomeModal()">Cancel</button>
                <button type="submit" class="spartan-btn-primary" id="outcome-submit-btn">Confirm Close</button>
            </div>
        </form>
    </div>
</div>

<script>
function promptDealOutcome(dealId, newStage) {
    const modal = document.getElementById('deal-outcome-modal');
    const dealInput = document.getElementById('outcome-deal-id');
    const stageInput = document.getElementById('outcome-stage');
    const title = document.getElementById('outcome-modal-title');
    const select = document.getElementById('outcome-reason-select');
    const submitBtn = document.getElementById('outcome-submit-btn');

    if (!modal || !dealInput || !stageInput) return;

    dealInput.value = dealId;
    stageInput.value = newStage;

    if (newStage === 'closed_won') {
        title.textContent = '🎉 Win Attribution & Key Success Factor';
        submitBtn.textContent = '🏆 Mark Closed Won';
        submitBtn.style.background = 'var(--success, #22c55e)';
        select.innerHTML = `
            <option value="Superior Feature Set">⭐ Superior Feature Set</option>
            <option value="Best Price / ROI Value">💰 Best Price / ROI Value</option>
            <option value="Relationship & Trust">🤝 Relationship & Trust</option>
            <option value="Faster Delivery / Implementation">⚡ Faster Delivery / Implementation</option>
            <option value="Other Win Factor">📝 Other Win Factor</option>
        `;
    } else {
        title.textContent = '📉 Loss Attribution & Reason Analysis';
        submitBtn.textContent = '❌ Mark Closed Lost';
        submitBtn.style.background = 'var(--danger, #ef4444)';
        select.innerHTML = `
            <option value="Price / Budget Too High">💰 Price / Budget Too High</option>
            <option value="Competitor Won">⚔️ Competitor Won</option>
            <option value="Missing Required Features">🧩 Missing Required Features</option>
            <option value="Timing / Project Postponed">⏳ Timing / Project Postponed</option>
            <option value="Ghosted / Unresponsive">👻 Ghosted / Unresponsive</option>
            <option value="Other">📝 Other</option>
        `;
    }

    modal.style.display = 'flex';
}

function closeOutcomeModal() {
    const modal = document.getElementById('deal-outcome-modal');
    if (modal) modal.style.display = 'none';
}
</script>
