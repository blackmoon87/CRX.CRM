@extends('layouts.main')

@section('content')
<div class="content-header" style="margin-bottom: 2rem;">
    <div>
        <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.35rem;">
            📝 {{ __('app.entities.quotes.build_title') }}
        </h1>
        <p style="color: var(--text-dim); font-size: 0.95rem;">
            {{ __('app.entities.quotes.build_subtitle') }}
        </p>
    </div>
</div>

<form method="POST" action="/quotes" id="quote-form">
    @csrf

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 2rem;">
        <!-- Left: Details & Line Items -->
        <div style="background: #FFFFFF; border: 1px solid var(--border); border-radius: 12px; box-shadow: var(--shadow-sm); padding: 1.75rem;">
            <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1.25rem; color: var(--text-main);">
                {{ __('app.entities.quotes.proposal_header') }}
            </h3>

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.35rem;">{{ __('app.entities.quotes.quote_number') }} *</label>
                    <input type="text" name="quote_number" value="{{ $nextQuoteNum }}" required style="width: 100%; padding: 0.7rem 0.9rem; border: 1px solid var(--border); border-radius: 8px; font-weight: 700; font-family: monospace;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.35rem;">{{ __('app.entities.quotes.proposal_title') }} *</label>
                    <input type="text" name="title" value="Enterprise Software Solution & Support Agreement" required style="width: 100%; padding: 0.7rem 0.9rem; border: 1px solid var(--border); border-radius: 8px; font-weight: 600;">
                </div>
            </div>

            <!-- Line Items Table -->
            <div style="margin-top: 2rem; margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin: 0;">
                        {{ __('app.entities.quotes.line_items') }}
                    </h3>
                    <button type="button" class="btn btn-secondary" onclick="addLineItem()" style="font-size: 0.82rem; font-weight: 600;">
                        + {{ __('app.entities.quotes.add_item') }}
                    </button>
                </div>

                <div id="items-container" style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <!-- Line Item Row 1 -->
                    <div class="line-item-row" style="display: grid; grid-template-columns: 3fr 1fr 1.5fr 40px; gap: 0.75rem; align-items: center;">
                        <input type="text" name="item_description[]" value="Core Enterprise CRM License (Annual)" required placeholder="{{ __('app.entities.quotes.item_desc') }}" style="padding: 0.65rem 0.85rem; border: 1px solid var(--border); border-radius: 8px;">
                        <input type="number" step="1" min="1" name="item_quantity[]" value="1" onchange="recalculateTotals()" placeholder="{{ __('app.entities.quotes.qty') }}" style="padding: 0.65rem; border: 1px solid var(--border); border-radius: 8px; text-align: center;">
                        <input type="number" step="0.01" min="0" name="item_unit_price[]" value="4800.00" onchange="recalculateTotals()" placeholder="{{ __('app.entities.quotes.unit_price') }}" style="padding: 0.65rem; border: 1px solid var(--border); border-radius: 8px; font-weight: 600;">
                        <button type="button" onclick="this.parentElement.remove(); recalculateTotals();" style="background: none; border: none; color: #DC2626; font-size: 1.2rem; cursor: pointer;">&times;</button>
                    </div>

                    <!-- Line Item Row 2 -->
                    <div class="line-item-row" style="display: grid; grid-template-columns: 3fr 1fr 1.5fr 40px; gap: 0.75rem; align-items: center;">
                        <input type="text" name="item_description[]" value="Custom Integration & Onboarding Kickoff" placeholder="{{ __('app.entities.quotes.item_desc') }}" style="padding: 0.65rem 0.85rem; border: 1px solid var(--border); border-radius: 8px;">
                        <input type="number" step="1" min="1" name="item_quantity[]" value="1" onchange="recalculateTotals()" placeholder="{{ __('app.entities.quotes.qty') }}" style="padding: 0.65rem; border: 1px solid var(--border); border-radius: 8px; text-align: center;">
                        <input type="number" step="0.01" min="0" name="item_unit_price[]" value="1200.00" onchange="recalculateTotals()" placeholder="{{ __('app.entities.quotes.unit_price') }}" style="padding: 0.65rem; border: 1px solid var(--border); border-radius: 8px; font-weight: 600;">
                        <button type="button" onclick="this.parentElement.remove(); recalculateTotals();" style="background: none; border: none; color: #DC2626; font-size: 1.2rem; cursor: pointer;">&times;</button>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div style="margin-top: 1.75rem;">
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.35rem;">{{ __('app.entities.quotes.client_terms') }}</label>
                <textarea name="notes" rows="3" placeholder="{{ __('app.entities.quotes.terms_ph') }}" style="width: 100%; padding: 0.7rem 0.9rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem; resize: vertical;"></textarea>
            </div>
        </div>

        <!-- Right: Client Association & Calculation Box -->
        <div>
            <!-- Associations -->
            <div style="background: #FFFFFF; border: 1px solid var(--border); border-radius: 12px; box-shadow: var(--shadow-sm); padding: 1.5rem; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-main);">
                    {{ __('app.entities.quotes.target_client') }}
                </h3>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.35rem;">{{ __('app.entities.quotes.link_deal') }}</label>
                    <select name="opportunity_id" style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem;">
                        <option value="">{{ __('app.entities.quotes.select_deal') }}</option>
                        @foreach($opportunities as $op)
                            <option value="{{ $op['id'] }}">{{ $op['title'] }} (${{ number_format((float)$op['amount']) }})</option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.35rem;">{{ __('app.entities.quotes.contact_recipient') }}</label>
                    <select name="person_id" style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem;">
                        <option value="">{{ __('app.entities.quotes.select_contact') }}</option>
                        @foreach($people as $p)
                            <option value="{{ $p['id'] }}">{{ $p['first_name'] }} {{ $p['last_name'] }} ({{ $p['email'] }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.35rem;">{{ __('app.entities.quotes.valid_until') }}</label>
                    <input type="date" name="valid_until" value="{{ date('Y-m-d', strtotime('+30 days')) }}" style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem;">
                </div>
            </div>

            <!-- Financial Summary Box -->
            <div style="background: #FFFFFF; border: 1px solid var(--border); border-radius: 12px; box-shadow: var(--shadow-sm); padding: 1.5rem;">
                <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-main);">
                    {{ __('app.entities.quotes.financial_breakdown') }}
                </h3>

                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; font-size: 0.9rem; color: var(--text-dim);">
                    <span>{{ __('app.entities.quotes.subtotal') }}</span>
                    <strong id="display_subtotal" style="color: var(--text-main);">$6,000.00</strong>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin: 0.75rem 0;">
                    <div>
                        <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-dim);">{{ __('app.entities.quotes.tax') }}</label>
                        <input type="number" step="0.5" min="0" max="100" name="tax_percent" id="input_tax" value="0" onchange="recalculateTotals()" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border); border-radius: 6px;">
                    </div>
                    <div>
                        <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-dim);">{{ __('app.entities.quotes.discount') }}</label>
                        <input type="number" step="10" min="0" name="discount_amount" id="input_discount" value="0" onchange="recalculateTotals()" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border); border-radius: 6px;">
                    </div>
                </div>

                <div style="border-top: 1px solid var(--border); padding-top: 1rem; margin-top: 1rem; display: flex; justify-content: space-between; align-items: baseline;">
                    <span style="font-size: 1.05rem; font-weight: 700; color: var(--text-main);">{{ __('app.entities.quotes.grand_total') }}</span>
                    <span id="display_total" style="font-size: 1.4rem; font-weight: 800; color: var(--primary);">$6,000.00</span>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; padding: 0.85rem; font-size: 0.95rem; font-weight: 600;">
                    🚀 {{ __('app.entities.quotes.issue_btn') }}
                </button>
            </div>
        </div>
    </div>
</form>

<script>
const itemDescPlaceholder = @json(__('app.entities.quotes.item_desc'));
const itemQtyPlaceholder = @json(__('app.entities.quotes.qty'));
const itemUnitPricePlaceholder = @json(__('app.entities.quotes.unit_price'));

function addLineItem() {
    const container = document.getElementById('items-container');
    const row = document.createElement('div');
    row.className = 'line-item-row';
    row.style = 'display: grid; grid-template-columns: 3fr 1fr 1.5fr 40px; gap: 0.75rem; align-items: center;';
    row.innerHTML = `
        <input type="text" name="item_description[]" placeholder="${itemDescPlaceholder}" required style="padding: 0.7rem 0.9rem; border: 1.5px solid var(--border); border-radius: 8px;">
        <input type="number" step="1" min="1" name="item_quantity[]" value="1" onchange="recalculateTotals()" placeholder="${itemQtyPlaceholder}" style="padding: 0.7rem; border: 1.5px solid var(--border); border-radius: 8px; text-align: center;">
        <input type="number" step="0.01" min="0" name="item_unit_price[]" value="500.00" onchange="recalculateTotals()" placeholder="${itemUnitPricePlaceholder}" style="padding: 0.7rem; border: 1.5px solid var(--border); border-radius: 8px; font-weight: 700;">
        <button type="button" onclick="this.parentElement.remove(); recalculateTotals();" style="background: none; border: none; color: #DC2626; font-size: 1.2rem; cursor: pointer;">&times;</button>
    `;
    container.appendChild(row);
    recalculateTotals();
}

function recalculateTotals() {
    let subtotal = 0;
    const qtys = document.getElementsByName('item_quantity[]');
    const prices = document.getElementsByName('item_unit_price[]');

    for (let i = 0; i < qtys.length; i++) {
        const q = parseFloat(qtys[i].value) || 0;
        const p = parseFloat(prices[i].value) || 0;
        subtotal += (q * p);
    }

    const taxPercent = parseFloat(document.getElementById('input_tax').value) || 0;
    const discount = parseFloat(document.getElementById('input_discount').value) || 0;

    const taxAmount = (subtotal * taxPercent) / 100;
    const total = Math.max(0, (subtotal + taxAmount) - discount);

    document.getElementById('display_subtotal').textContent = '$' + subtotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('display_total').textContent = '$' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>
@endsection
