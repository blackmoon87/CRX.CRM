<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal #{{ $quote['quote_number'] }} | {{ $quote['title'] }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0B0F19;
            color: #F9FAFB;
            min-height: 100vh;
            padding: 2.5rem 1rem;
            background-image: radial-gradient(circle at top, rgba(79, 70, 229, 0.12) 0%, transparent 60%);
        }
        .proposal-container {
            max-width: 860px;
            margin: 0 auto;
            background: #111827;
            border: 1px solid #1F2937;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            overflow: hidden;
        }
        .proposal-header {
            background: linear-gradient(135deg, #1E1B4B 0%, #312E81 100%);
            padding: 2.5rem;
            border-bottom: 1px solid #3730A3;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .badge-status {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 0.3rem 0.75rem;
            border-radius: 9999px;
            margin-bottom: 0.75rem;
        }
        .badge-accepted {
            background: #065F46;
            color: #6EE7B7;
            border: 1px solid #10B981;
        }
        .badge-pending {
            background: rgba(99, 102, 241, 0.2);
            color: #C7D2FE;
            border: 1px solid #6366F1;
        }
        .proposal-body {
            padding: 2.5rem;
        }
        .line-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2rem 0;
        }
        .line-table th {
            text-align: left;
            padding: 0.85rem 1rem;
            border-bottom: 1px solid #374151;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #9CA3AF;
        }
        .line-table td {
            padding: 1rem;
            border-bottom: 1px solid #1F2937;
            font-size: 0.95rem;
        }
        .totals-block {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 2.5rem;
        }
        .totals-table {
            width: 300px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 0.45rem 0;
            font-size: 0.9rem;
            color: #9CA3AF;
        }
        .grand-total {
            border-top: 1px solid #374151;
            padding-top: 0.85rem;
            margin-top: 0.5rem;
            font-size: 1.35rem;
            font-weight: 900;
            color: #FFFFFF;
        }
        .acceptance-box {
            background: #0D1322;
            border: 1px solid #1F2937;
            border-radius: 14px;
            padding: 2rem;
            text-align: center;
        }
        .accept-btn {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: #FFFFFF;
            font-size: 1.05rem;
            font-weight: 800;
            border: none;
            border-radius: 10px;
            padding: 1rem 2.5rem;
            cursor: pointer;
            transition: all 0.15s ease;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.35);
        }
        .accept-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(16, 185, 129, 0.45);
        }
    </style>
</head>
<body>

<div class="proposal-container">
    <!-- Header -->
    <div class="proposal-header">
        <div>
            @if($quote['status'] === 'accepted')
                <div class="badge-status badge-accepted">✓ PROPOSAL ACCEPTED & CONFIRMED</div>
            @else
                <div class="badge-status badge-pending">OFFICIAL CLIENT PROPOSAL</div>
            @endif
            <h1 style="font-size: 1.8rem; font-weight: 900; color: #FFFFFF; margin-bottom: 0.35rem;">
                {{ $quote['title'] }}
            </h1>
            <div style="font-family: monospace; font-size: 1rem; color: #A5B4FC; font-weight: 700;">
                Quote Ref: #{{ $quote['quote_number'] }}
            </div>
        </div>

        <div style="text-align: right; color: #C7D2FE; font-size: 0.85rem;">
            <div>Valid Until: <strong>{{ $quote['valid_until'] ? date('M j, Y', strtotime($quote['valid_until'])) : '30 Days' }}</strong></div>
            <div style="margin-top: 0.35rem;">Status: <strong>{{ strtoupper($quote['status']) }}</strong></div>
        </div>
    </div>

    <!-- Body -->
    <div class="proposal-body">
        <div style="display: flex; justify-content: space-between; margin-bottom: 2rem; border-bottom: 1px solid #1F2937; padding-bottom: 1.5rem;">
            <div>
                <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #6B7280; margin-bottom: 0.35rem;">Prepared For</div>
                <div style="font-size: 1.15rem; font-weight: 800; color: #FFFFFF;">
                    {{ $person ? ($person['first_name'] . ' ' . $person['last_name']) : ($company['name'] ?? 'Client') }}
                </div>
                @if($person && !empty($person['email']))
                    <div style="font-size: 0.88rem; color: #9CA3AF;">{{ $person['email'] }}</div>
                @endif
            </div>

            <div style="text-align: right;">
                <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #6B7280; margin-bottom: 0.35rem;">Issued Date</div>
                <div style="font-size: 0.95rem; font-weight: 700; color: #FFFFFF;">
                    {{ date('F j, Y', strtotime($quote['created_at'])) }}
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <table class="line-table">
            <thead>
                <tr>
                    <th>Item & Scope of Work</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Unit Price</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $it)
                    <tr>
                        <td style="font-weight: 700; color: #F3F4F6;">{{ $it['description'] }}</td>
                        <td style="text-align: center; color: #9CA3AF;">{{ (float)$it['quantity'] }}</td>
                        <td style="text-align: right; color: #9CA3AF;">${{ number_format((float)$it['unit_price'], 2) }}</td>
                        <td style="text-align: right; font-weight: 800; color: #FFFFFF;">${{ number_format((float)$it['total_price'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-block">
            <div class="totals-table">
                <div class="totals-row">
                    <span>Subtotal</span>
                    <span style="color: #FFFFFF;">${{ number_format((float)$quote['subtotal'], 2) }}</span>
                </div>
                @if((float)$quote['tax_amount'] > 0)
                    <div class="totals-row">
                        <span>Tax ({{ (float)$quote['tax_percent'] }}%)</span>
                        <span style="color: #FFFFFF;">+${{ number_format((float)$quote['tax_amount'], 2) }}</span>
                    </div>
                @endif
                @if((float)$quote['discount_amount'] > 0)
                    <div class="totals-row" style="color: #10B981;">
                        <span>Discount</span>
                        <span>-${{ number_format((float)$quote['discount_amount'], 2) }}</span>
                    </div>
                @endif
                <div class="totals-row grand-total">
                    <span>Total Investment</span>
                    <span style="color: #60A5FA;">${{ number_format((float)$quote['total_amount'], 2) }} USD</span>
                </div>
            </div>
        </div>

        @if(!empty($quote['notes']))
            <div style="background: #0B0F19; border: 1px solid #1F2937; border-radius: 12px; padding: 1.25rem; margin-bottom: 2rem;">
                <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #9CA3AF; margin-bottom: 0.35rem;">Terms & Milestones</div>
                <div style="font-size: 0.88rem; color: #D1D5DB; line-height: 1.6;">{{ $quote['notes'] }}</div>
            </div>
        @endif

        <!-- Acceptance Call to Action -->
        <div class="acceptance-box">
            @if($quote['status'] === 'accepted')
                <div style="font-size: 2.5rem; color: #10B981; margin-bottom: 0.5rem;">✓</div>
                <h3 style="font-size: 1.35rem; font-weight: 800; color: #FFFFFF; margin-bottom: 0.35rem;">
                    Thank you! This proposal was accepted on {{ date('F j, Y', strtotime($quote['accepted_at'])) }}
                </h3>
                <p style="color: #9CA3AF; font-size: 0.9rem;">Our onboarding team has been notified and will initiate project kickoff.</p>
            @else
                <h3 style="font-size: 1.35rem; font-weight: 800; color: #FFFFFF; margin-bottom: 0.5rem;">
                    Ready to proceed with this proposal?
                </h3>
                <p style="color: #9CA3AF; font-size: 0.9rem; max-width: 460px; margin: 0 auto 1.5rem auto;">
                    Click below to digitally accept and approve the scope of work and investment.
                </p>

                <form method="POST" action="/quote/{{ $quote['public_token'] }}/accept">
                    <button type="submit" class="accept-btn">
                        ✍️ Accept & Confirm Proposal
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

</body>
</html>
