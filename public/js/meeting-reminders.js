/**
 * CRX Active Meeting Alarm & Reminder Engine
 * Pure Vanilla JS + Web Audio API Chime — Zero External Assets
 */

(function () {
    let notifiedIds = new Set();

    // Subtle audio chime synthesis using Web Audio API
    function playAlarmChime() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();

            // Two-tone friendly chime: Note 1 (E5, 659Hz) then Note 2 (A5, 880Hz)
            const osc1 = ctx.createOscillator();
            const osc2 = ctx.createOscillator();
            const gainNode = ctx.createGain();

            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(659.25, ctx.currentTime);
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(880.00, ctx.currentTime + 0.15);

            gainNode.gain.setValueAtTime(0.15, ctx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.6);

            osc1.connect(gainNode);
            osc2.connect(gainNode);
            gainNode.connect(ctx.destination);

            osc1.start(ctx.currentTime);
            osc1.stop(ctx.currentTime + 0.15);
            osc2.start(ctx.currentTime + 0.15);
            osc2.stop(ctx.currentTime + 0.6);
        } catch (e) {
            // Audio context blocked or unsupported
        }
    }

    function checkAlarms() {
        fetch('/api/upcoming-alarms')
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.alarms || data.alarms.length === 0) {
                    removeAlarmBanner();
                    return;
                }

                const now = new Date().getTime();

                // Find the soonest upcoming meeting
                for (const meeting of data.alarms) {
                    if (!meeting.scheduled_at) continue;

                    const meetingTime = new Date(meeting.scheduled_at.replace(' ', 'T')).getTime();
                    const diffMins = Math.round((meetingTime - now) / 60000);

                    // If meeting is within 30 minutes (or past up to 10 minutes)
                    if (diffMins >= -10 && diffMins <= 30) {
                        showAlarmBanner(meeting, diffMins);

                        if (!notifiedIds.has(meeting.id)) {
                            notifiedIds.add(meeting.id);
                            playAlarmChime();

                            // Trigger Browser Desktop Notification if allowed
                            if ('Notification' in window && Notification.permission === 'granted') {
                                new Notification(`⏰ CRX Meeting Alarm: ${meeting.title}`, {
                                    body: `Scheduled for ${meeting.scheduled_at} (${diffMins > 0 ? 'in ' + diffMins + ' mins' : 'Starting now!'})`,
                                    icon: '/favicon.ico',
                                });
                            }
                        }
                        break;
                    }
                }
            })
            .catch(() => {});
    }

    function showAlarmBanner(meeting, diffMins) {
        let banner = document.getElementById('crx-meeting-alarm-banner');
        if (!banner) {
            banner = document.createElement('div');
            banner.id = 'crx-meeting-alarm-banner';
            banner.className = 'spartan-alarm-banner';
            document.body.prepend(banner);
        }

        const timeLabel = diffMins <= 0 ? '🚨 STARTING NOW!' : `⏰ In ${diffMins} minutes`;
        const viewUrl = `/${meeting.entity_type}/${meeting.entity_id}`;

        banner.innerHTML = `
            <div class="alarm-banner-content">
                <span class="alarm-badge">${timeLabel}</span>
                <span class="alarm-title"><strong>Meeting Alert:</strong> ${escapeHtml(meeting.title)}</span>
                <span class="alarm-time">(${meeting.scheduled_at})</span>
            </div>
            <div class="alarm-actions">
                <a href="${viewUrl}" class="spartan-alarm-btn">View Record</a>
                <a href="/interactions/${meeting.id}/ics" class="spartan-alarm-btn" title="Download iCal">📥 iCal</a>
                <button type="button" class="spartan-alarm-close" onclick="document.getElementById('crx-meeting-alarm-banner').remove()">✕</button>
            </div>
        `;
    }

    function removeAlarmBanner() {
        const banner = document.getElementById('crx-meeting-alarm-banner');
        if (banner) banner.remove();
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Request notification permission once on user interaction
    document.addEventListener('click', () => {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }, { once: true });

    document.addEventListener('DOMContentLoaded', () => {
        checkAlarms();
        setInterval(checkAlarms, 30000); // Check every 30 seconds
    });
})();

// Inject Alarm Banner CSS
const alarmStyle = document.createElement('style');
alarmStyle.textContent = `
.spartan-alarm-banner {
    position: sticky;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100000;
    background: #fef08a;
    color: #854d0e;
    border-bottom: 2px solid #0f172a;
    box-shadow: 0 4px 0 rgba(15, 23, 42, 0.15);
    padding: 8px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13px;
    font-weight: 700;
    animation: alarmPulse 2s infinite ease-in-out;
}

@keyframes alarmPulse {
    0%, 100% { background: #fef08a; }
    50% { background: #fde047; }
}

.alarm-banner-content {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.alarm-badge {
    background: #ef4444;
    color: #ffffff;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 800;
    border: 1.5px solid #0f172a;
    box-shadow: 1.5px 1.5px 0 #0f172a;
}

.alarm-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.spartan-alarm-btn {
    padding: 3px 10px;
    font-size: 12px;
    font-weight: 700;
    background: #ffffff;
    color: #0f172a;
    border: 1.5px solid #0f172a;
    box-shadow: 1.5px 1.5px 0 #0f172a;
    border-radius: 4px;
    text-decoration: none;
    cursor: pointer;
}

.spartan-alarm-btn:hover {
    transform: translate(-1px, -1px);
    box-shadow: 2.5px 2.5px 0 #0f172a;
}

.spartan-alarm-close {
    background: transparent;
    border: none;
    font-weight: 800;
    font-size: 14px;
    color: #0f172a;
    cursor: pointer;
    padding: 2px 6px;
}
`;
document.head.appendChild(alarmStyle);
