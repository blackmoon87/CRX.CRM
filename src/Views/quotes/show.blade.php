@extends('layouts.main')

@section('content')
<div class="content-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.35rem;">
            <a href="/quotes" style="color: var(--text-dim); text-decoration: none; font-size: 0.9rem;">&larr; {{ __('app.entities.quotes.back_to_quotes') }}</a>
            <span style="color: #CBD5E1;">/</span>
            <span style="font-family: monospace; font-weight: 800; color: #0284C7;">{{ $quote['quote_number'] }}</span>
        </div>
        <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin: 0;">
            {{ $quote['title'] }}
        </h1>
    </div>

    <div style="display: flex; gap: 0.75rem;">
        <button type="button" class="btn btn-secondary" onclick="window.print()" style="font-weight: 700;">
            🖨️ {{ __('app.entities.quotes.print_pdf') }}
        </button>
        <button type="button" class="btn btn-secondary" onclick="navigator.clipboard.writeText('{{ url('/quote/' . $quote['public_token']) }}'); this.textContent='{{ __('app.entities.quotes.link_copied') }}'; setTimeout(()=>this.textContent='🔗 {{ __('app.entities.quotes.copy_link') }}', 2000);" style="font-weight: 700;">
            🔗 {{ __('app.entities.quotes.copy_link') }}
        </button>
        <a href="/quote/{{ $quote['public_token'] }}" target="_blank" class="btn btn-primary" style="font-weight: 800;">
            ↗ {{ __('app.entities.quotes.open_proposal') }}
        </a>
    </div>
</div>

<!-- Invoice Document Presentation -->
<div style="background: #FFFFFF; border: 1px solid var(--border); border-radius: 16px; box-shadow: var(--shadow-md); padding: 3.5rem 3rem; max-width: 900px; margin: 0 auto;">
    <!-- Document Header -->
    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #E2E8F0; padding-bottom: 2rem; margin-bottom: 2.5rem;">
        <div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.25rem;">
                {{ __('app.entities.quotes.proposal_badge') }}
            </div>
            <div style="font-family: monospace; font-size: 1.05rem; font-weight: 700; color: var(--primary);">
                #{{ $quote['quote_number'] }}
            </div>
        </div>

        <div style="text-align: right;">
            @if($quote['status'] === 'accepted')
                <div style="display: inline-block; background: #DCFCE7; border: 1.5px solid #16A34A; color: #15803D; font-size: 0.85rem; font-weight: 900; text-transform: uppercase; padding: 0.35rem 0.85rem; border-radius: 8px; margin-bottom: 0.5rem;">
                    🏆 {{ __('app.entities.quotes.accepted_won') }}
                </div>
            @else
                <div style="display: inline-block; background: #E0F2FE; border: 1.5px solid #0284C7; color: #0369A1; font-size: 0.85rem; font-weight: 900; text-transform: uppercase; padding: 0.35rem 0.85rem; border-radius: 8px; margin-bottom: 0.5rem;">
                    {{ __('app.entities.quotes.status_label') }}: {{ strtoupper($quote['status']) }}
                </div>
            @endif
            <div style="font-size: 0.85rem; color: var(--text-dim);">{{ __('app.entities.quotes.issued_date') }}: <strong>{{ date('M j, Y', strtotime($quote['created_at'])) }}</strong></div>
            <div style="font-size: 0.85rem; color: var(--text-dim);">{{ __('app.entities.quotes.valid_until') }}: <strong>{{ $quote['valid_until'] ? date('M j, Y', strtotime($quote['valid_until'])) : '30 Days' }}</strong></div>
        </div>
    </div>

    <!-- Client & Deal Associations -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2.5rem;">
        <div>
            <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-dim); letter-spacing: 0.05em; margin-bottom: 0.4rem;">
                {{ __('app.entities.quotes.prepared_for') }}
            </div>
            <div style="font-size: 1.1rem; font-weight: 800; color: var(--text-main);">
                {{ $person ? ($person['first_name'] . ' ' . $person['last_name']) : ($company['name'] ?? __('app.entities.quotes.prospective_client')) }}
            </div>
            @if($person && !empty($person['email']))
                <div style="font-size: 0.9rem; color: var(--text-dim);">{{ $person['email'] }}</div>
            @endif
            @if($company)
                <div style="font-size: 0.9rem; color: var(--text-dim); font-weight: 600;">{{ $company['name'] }}</div>
            @endif
        </div>

        @if($opp)
            <div>
                <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-dim); letter-spacing: 0.05em; margin-bottom: 0.4rem;">
                    {{ __('app.entities.quotes.linked_deal') }}
                </div>
                <div style="font-size: 1.1rem; font-weight: 800; color: var(--text-main);">
                    <a href="/opportunities" style="color: inherit; text-decoration: underline;">{{ $opp['title'] }}</a>
                </div>
                <div style="font-size: 0.9rem; color: #16A34A; font-weight: 700;">
                    {{ __('app.entities.quotes.pipeline_stage') }}: {{ ucfirst(str_replace('_', ' ', $opp['stage'])) }}
                </div>
            </div>
        @endif
    </div>

    <!-- Line Items Table -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
        <thead>
            <tr style="background: #F8FAFC; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); text-align: left;">
                <th style="padding: 0.85rem 1rem; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-dim);">{{ __('app.entities.quotes.item_desc') }}</th>
                <th style="padding: 0.85rem 1rem; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-dim); text-align: center;">{{ __('app.entities.quotes.qty') }}</th>
                <th style="padding: 0.85rem 1rem; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-dim); text-align: right;">{{ __('app.entities.quotes.unit_price') }}</th>
                <th style="padding: 0.85rem 1rem; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-dim); text-align: right;">{{ __('app.common.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $it)
                <tr style="border-bottom: 1px solid var(--border-subtle);">
                    <td style="padding: 1rem; font-weight: 600; color: var(--text-main);">{{ $it['description'] }}</td>
                    <td style="padding: 1rem; text-align: center; color: var(--text-dim); font-weight: 500;">{{ (float)$it['quantity'] }}</td>
                    <td style="padding: 1rem; text-align: right; color: var(--text-dim);">${{ number_format((float)$it['unit_price'], 2) }}</td>
                    <td style="padding: 1rem; text-align: right; font-weight: 700; color: var(--text-main);">${{ number_format((float)$it['total_price'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals Summary -->
    <div style="display: flex; justify-content: flex-end; margin-bottom: 2.5rem;">
        <div style="width: 280px;">
            <div style="display: flex; justify-content: space-between; padding: 0.4rem 0; font-size: 0.9rem; color: var(--text-dim);">
                <span>{{ __('app.entities.quotes.subtotal') }}</span>
                <span>${{ number_format((float)$quote['subtotal'], 2) }}</span>
            </div>
            @if((float)$quote['tax_amount'] > 0)
                <div style="display: flex; justify-content: space-between; padding: 0.4rem 0; font-size: 0.9rem; color: var(--text-dim);">
                    <span>{{ __('app.entities.quotes.tax') }} ({{ (float)$quote['tax_percent'] }}%)</span>
                    <span>+${{ number_format((float)$quote['tax_amount'], 2) }}</span>
                </div>
            @endif
            @if((float)$quote['discount_amount'] > 0)
                <div style="display: flex; justify-content: space-between; padding: 0.4rem 0; font-size: 0.9rem; color: #16A34A;">
                    <span>{{ __('app.entities.quotes.discount') }}</span>
                    <span>-${{ number_format((float)$quote['discount_amount'], 2) }}</span>
                </div>
            @endif
            <div style="border-top: 1px solid var(--border); margin-top: 0.5rem; padding-top: 0.75rem; display: flex; justify-content: space-between; align-items: baseline;">
                <span style="font-weight: 700; font-size: 1.1rem; color: var(--text-main);">{{ __('app.entities.quotes.grand_total') }}</span>
                <span style="font-weight: 800; font-size: 1.4rem; color: var(--primary);">${{ number_format((float)$quote['total_amount'], 2) }}</span>
            </div>
        </div>
    </div>

    @if(!empty($quote['notes']))
        <div style="background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 10px; padding: 1.25rem;">
            <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-dim); margin-bottom: 0.4rem;">
                {{ __('app.entities.quotes.terms_conditions') }}
            </div>
            <div style="font-size: 0.88rem; color: var(--text-dim); line-height: 1.6;">
                {{ $quote['notes'] }}
            </div>
        </div>
    @endif
</div>
@endsection
