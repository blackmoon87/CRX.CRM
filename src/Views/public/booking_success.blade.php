<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting Confirmed! | CRX Scheduler</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0B0F19;
            color: #F9FAFB;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background-image: radial-gradient(circle at top, rgba(16, 185, 129, 0.15) 0%, transparent 60%);
        }
        .success-card {
            background: #111827;
            border: 1px solid #1F2937;
            border-radius: 20px;
            padding: 3rem 2.5rem;
            max-width: 540px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
        }
        .success-badge {
            width: 72px;
            height: 72px;
            background: rgba(16, 185, 129, 0.15);
            border: 2px solid #10B981;
            color: #10B981;
            font-size: 2.2rem;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 0 30px rgba(16, 185, 129, 0.3);
        }
        h1 { font-size: 1.75rem; font-weight: 800; margin-bottom: 0.75rem; color: #FFFFFF; }
        p { color: #9CA3AF; font-size: 0.95rem; line-height: 1.6; margin-bottom: 2rem; }
        .details-box {
            background: #0B0F19;
            border: 1px solid #1F2937;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 2rem;
            text-align: left;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #1F2937;
            font-size: 0.88rem;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #9CA3AF; font-weight: 600; }
        .detail-val { color: #FFFFFF; font-weight: 700; }
        .ics-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: #4F46E5;
            color: #FFFFFF;
            font-weight: 700;
            text-decoration: none;
            padding: 0.85rem 1.5rem;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.15s ease;
        }
        .ics-btn:hover { background: #4338CA; transform: translateY(-1px); }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-badge">✓</div>
        <h1>You're Scheduled!</h1>
        <p>A calendar invitation and confirmation email have been sent to your email address.</p>

        @if(!empty($interaction))
            <div class="details-box">
                <div class="detail-row">
                    <span class="detail-label">Meeting</span>
                    <span class="detail-val">{{ $interaction['title'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date & Time</span>
                    <span class="detail-val">{{ date('l, F j, Y — g:i A', strtotime($interaction['scheduled_at'])) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Duration</span>
                    <span class="detail-val">{{ $interaction['duration_minutes'] }} Minutes</span>
                </div>
            </div>

            <a href="/interactions/{{ $interaction['id'] }}/ics" class="ics-btn">
                📅 Add to Google / Apple Calendar (.ics)
            </a>
        @endif
    </div>
</body>
</html>
