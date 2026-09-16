<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $setting['title'] }} | CRX Scheduler</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-page: #0B0F19;
            --bg-card: #111827;
            --bg-card-hover: #1F2937;
            --border: #1F2937;
            --border-highlight: #374151;
            --primary: #4F46E5;
            --primary-hover: #4338CA;
            --primary-glow: rgba(79, 70, 229, 0.35);
            --text-main: #F9FAFB;
            --text-dim: #9CA3AF;
            --success: #10B981;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background-image: radial-gradient(circle at top, rgba(79, 70, 229, 0.12) 0%, transparent 60%);
        }
        .booking-shell {
            width: 100%;
            max-width: 960px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            overflow: hidden;
            display: grid;
            grid-template-columns: 340px 1fr;
        }
        @media (max-width: 768px) {
            .booking-shell { grid-template-columns: 1fr; }
        }
        /* Left Host Profile & Summary */
        .host-sidebar {
            background: rgba(17, 24, 39, 0.7);
            border-right: 1px solid var(--border);
            padding: 2.5rem 2rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #818CF8;
            background: rgba(99, 102, 241, 0.12);
            border: 1px solid rgba(99, 102, 241, 0.3);
            padding: 0.3rem 0.7rem;
            border-radius: 9999px;
            margin-bottom: 1.5rem;
            width: fit-content;
        }
        .host-avatar {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            background: linear-gradient(135deg, #6366F1, #4F46E5);
            color: #FFFFFF;
            font-size: 1.6rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.4);
            margin-bottom: 1.25rem;
        }
        .meeting-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #FFFFFF;
            line-height: 1.25;
            margin-bottom: 0.75rem;
        }
        .meeting-desc {
            font-size: 0.9rem;
            color: var(--text-dim);
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.88rem;
            color: #D1D5DB;
            margin-bottom: 0.85rem;
            font-weight: 600;
        }
        .meta-icon {
            font-size: 1.1rem;
            color: #818CF8;
        }

        /* Right Calendar & Slot Selector */
        .schedule-panel {
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
        }
        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .days-carousel {
            display: flex;
            gap: 0.65rem;
            overflow-x: auto;
            padding-bottom: 0.85rem;
            margin-bottom: 1.75rem;
            scrollbar-width: thin;
        }
        .day-chip {
            flex: 0 0 76px;
            background: var(--bg-page);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 0.85rem 0.5rem;
            text-align: center;
            text-decoration: none;
            color: var(--text-dim);
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .day-chip:hover {
            border-color: var(--border-highlight);
            color: #FFFFFF;
            background: var(--bg-card-hover);
        }
        .day-chip.active {
            background: var(--primary);
            border-color: var(--primary);
            color: #FFFFFF;
            box-shadow: 0 4px 14px var(--primary-glow);
        }
        .day-chip .day-name { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.25rem; }
        .day-chip .day-num { font-size: 1.25rem; font-weight: 800; }
        .day-chip .day-month { font-size: 0.7rem; font-weight: 600; opacity: 0.8; }

        /* Slots Grid */
        .slots-container {
            flex: 1;
            margin-bottom: 2rem;
        }
        .slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 0.75rem;
            max-height: 240px;
            overflow-y: auto;
            padding-right: 0.25rem;
        }
        .slot-btn {
            background: var(--bg-page);
            border: 1px solid var(--border);
            color: #E0E7FF;
            font-size: 0.9rem;
            font-weight: 700;
            padding: 0.8rem 0.5rem;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .slot-btn:hover {
            border-color: #6366F1;
            color: #FFFFFF;
            transform: translateY(-1px);
        }
        .slot-btn.selected {
            background: var(--primary);
            border-color: var(--primary);
            color: #FFFFFF;
            box-shadow: 0 4px 14px var(--primary-glow);
        }
        .no-slots {
            padding: 2rem;
            text-align: center;
            background: var(--bg-page);
            border-radius: 12px;
            border: 1px dashed var(--border);
            color: var(--text-dim);
            font-size: 0.9rem;
        }

        /* Booking Guest Details Form */
        .booking-form-wrap {
            display: none;
            background: var(--bg-page);
            border: 1px solid var(--border-highlight);
            border-radius: 14px;
            padding: 1.5rem;
            margin-top: 1rem;
            animation: fadeIn 0.25s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        @media (max-width: 540px) {
            .form-grid { grid-template-columns: 1fr; }
        }
        .input-group {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }
        .input-label {
            font-size: 0.78rem;
            font-weight: 700;
            color: #D1D5DB;
        }
        .input-field {
            background: #111827;
            border: 1px solid #374151;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            color: #FFFFFF;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.15s ease;
        }
        .input-field:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        }
        .confirm-btn {
            width: 100%;
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
            color: #FFFFFF;
            font-size: 0.95rem;
            font-weight: 800;
            border: none;
            border-radius: 10px;
            padding: 0.95rem;
            cursor: pointer;
            transition: all 0.15s ease;
            box-shadow: 0 10px 20px var(--primary-glow);
        }
        .confirm-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 24px var(--primary-glow);
        }
    </style>
</head>
<body>

<div class="booking-shell">
    <!-- Host Profile Summary -->
    <div class="host-sidebar">
        <div>
            <div class="brand-badge">⚡ Instant CRM Booking</div>
            <div class="host-avatar">
                {{ strtoupper(substr($host['name'] ?? 'CRX', 0, 2)) }}
            </div>
            <h1 class="meeting-title">{{ $setting['title'] }}</h1>
            <p class="meeting-desc">{{ $setting['description'] ?: 'Schedule a dedicated one-on-one session with our team.' }}</p>

            <div class="meta-item">
                <span class="meta-icon">⏱️</span>
                <span>{{ $setting['duration_minutes'] }} Minutes Duration</span>
            </div>
            <div class="meta-item">
                <span class="meta-icon">👤</span>
                <span>Host: {{ $host['name'] ?? 'Team Member' }}</span>
            </div>
            <div class="meta-item">
                <span class="meta-icon">🌍</span>
                <span>Timezone: <span id="user-tz">UTC</span></span>
            </div>
        </div>
        
        <div style="font-size: 0.75rem; color: #6B7280; padding-top: 1.5rem; border-top: 1px solid var(--border);">
            Powered by <strong>CRX Revenue Engine</strong>
        </div>
    </div>

    <!-- Calendar & Time Slot Selector -->
    <div class="schedule-panel">
        <div class="section-title">
            <span>1. Select a Date</span>
            <span style="font-size: 0.8rem; font-weight: 600; color: #818CF8;">{{ date('F Y', strtotime($selectedDate)) }}</span>
        </div>

        <!-- Available Date Chips -->
        <div class="days-carousel">
            @foreach($availableDays as $d)
                <a href="/book/{{ $setting['slug'] }}?date={{ $d['date'] }}" class="day-chip {{ $d['is_current'] ? 'active' : '' }}">
                    <div class="day-name">{{ $d['day_name'] }}</div>
                    <div class="day-num">{{ $d['day_num'] }}</div>
                    <div class="day-month">{{ $d['month'] }}</div>
                </a>
            @endforeach
        </div>

        <div class="section-title">
            <span>2. Available Times ({{ date('D, M j', strtotime($selectedDate)) }})</span>
            <span style="font-size: 0.78rem; font-weight: 600; color: #9CA3AF;">{{ count($slots) }} Slots Open</span>
        </div>

        <!-- Slots Container -->
        <div class="slots-container">
            @if(empty($slots))
                <div class="no-slots">
                    <p style="font-weight: 700; color: #FFFFFF; margin-bottom: 0.35rem;">No open slots on this date</p>
                    <p>Please select another day from the calendar above.</p>
                </div>
            @else
                <div class="slots-grid">
                    @foreach($slots as $s)
                        <div class="slot-btn" onclick="selectSlot('{{ $s['datetime'] }}', '{{ $s['display'] }}', this)">
                            {{ $s['display'] }}
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Guest Details Form (Unfolds on Slot Click) -->
        <div id="booking-form-wrap" class="booking-form-wrap">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                <h4 style="font-size: 1rem; font-weight: 800; color: #FFFFFF; margin: 0;">3. Your Details & Confirmation</h4>
                <span id="selected-slot-badge" style="background: #312E81; color: #C7D2FE; font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.65rem; border-radius: 6px;"></span>
            </div>

            <form method="POST" action="/book/{{ $setting['slug'] }}">
                <input type="hidden" name="slot_time" id="input_slot_time">

                <div class="form-grid">
                    <div class="input-group">
                        <label class="input-label" for="guest_name">Full Name *</label>
                        <input type="text" id="guest_name" name="guest_name" class="input-field" required placeholder="Jane Doe">
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="guest_email">Work Email *</label>
                        <input type="email" id="guest_email" name="guest_email" class="input-field" required placeholder="jane@company.com">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="input-group">
                        <label class="input-label" for="guest_phone">Phone / WhatsApp</label>
                        <input type="tel" id="guest_phone" name="guest_phone" class="input-field" placeholder="+1 (555) 000-0000">
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="notes">Meeting Agenda / Questions</label>
                        <input type="text" id="notes" name="notes" class="input-field" placeholder="Briefly describe what you'd like to discuss">
                    </div>
                </div>

                <button type="submit" class="confirm-btn">
                    🚀 Confirm & Book Meeting
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// Auto detect user timezone
try {
    const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
    if (tz) document.getElementById('user-tz').textContent = tz;
} catch (e) {}

function selectSlot(datetime, displayTime, btnEl) {
    document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
    btnEl.classList.add('selected');

    document.getElementById('input_slot_time').value = datetime;
    document.getElementById('selected-slot-badge').textContent = 'Selected: ' + displayTime;

    const formWrap = document.getElementById('booking-form-wrap');
    formWrap.style.display = 'block';
    formWrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    document.getElementById('guest_name').focus();
}
</script>

</body>
</html>
