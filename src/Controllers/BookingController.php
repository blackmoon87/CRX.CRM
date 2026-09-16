<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\BookingSetting;
use App\Models\Interaction;
use App\Models\Person;
use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\MailService;
use App\Services\WorkspaceService;
use Spartan\Controller;

class BookingController extends Controller
{
    private function getContext(): array
    {
        $userId = (int)$this->auth->id();
        $wsService = new WorkspaceService($this->session);
        return [
            'userId'      => $userId,
            'workspaceId' => $wsService->getActiveWorkspaceId($userId),
            'workspace'   => $wsService->getActiveWorkspace($userId),
            'workspaces'  => $wsService->getUserWorkspaces($userId),
        ];
    }

    /**
     * Public Guest Facing: Booking calendar & time slots
     */
    public function show(string|int|null $slug = null): void
    {
        $slug = trim((string)($slug ?? $this->request->getParam('slug') ?? ''));
        if ($slug === '') {
            $this->response->setStatusCode(404)->html('Booking link not found.');
            return;
        }

        $setting = (new BookingSetting)->table()
            ->where('slug', $slug)
            ->where('is_active', 1)
            ->first();

        if (!$setting) {
            // Check if user exists with matching username or ID fallback
            $user = (new User)->table()->where('id', (int)$slug)->first();
            if ($user) {
                // Auto-provision default booking settings
                $settingId = (new BookingSetting)->table()->insert([
                    'user_id'            => $user['id'],
                    'workspace_id'       => 1,
                    'slug'               => strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $user['name'] ?: 'user-' . $user['id'])),
                    'title'              => "Meeting with " . ($user['name'] ?: 'Advisor'),
                    'description'        => "Book a 30-minute discovery or consultation call.",
                    'duration_minutes'   => 30,
                    'working_hours_start'=> '09:00',
                    'working_hours_end'  => '17:00',
                    'buffer_minutes'     => 15,
                    'is_active'          => 1,
                    'created_at'         => date('Y-m-d H:i:s'),
                    'updated_at'         => date('Y-m-d H:i:s'),
                ]);
                $setting = (new BookingSetting)->find($settingId);
            }
        }

        if (!$setting) {
            $this->response->setStatusCode(404)->html('Booking schedule not available or has expired.');
            return;
        }

        $host = (new User)->find((int)$setting['user_id']);

        // Determine selected date (default to next business day)
        $selectedDate = $this->request->getQuery('date');
        if (!$selectedDate || strtotime($selectedDate) < strtotime(date('Y-m-d'))) {
            // Pick next weekday
            $next = strtotime('+1 day');
            if (date('N', $next) >= 6) {
                $next = strtotime('next monday');
            }
            $selectedDate = date('Y-m-d', $next);
        }

        // Generate next 14 available days
        $availableDays = [];
        $cursor = strtotime(date('Y-m-d'));
        while (count($availableDays) < 12) {
            $cursor = strtotime('+1 day', $cursor);
            $dayOfWeek = (int)date('N', $cursor);
            if ($dayOfWeek < 6) { // Mon-Fri
                $dStr = date('Y-m-d', $cursor);
                $availableDays[] = [
                    'date'      => $dStr,
                    'day_name'  => date('D', $cursor),
                    'day_num'   => date('j', $cursor),
                    'month'     => date('M', $cursor),
                    'is_current'=> $dStr === $selectedDate,
                ];
            }
        }

        // Generate time slots for selectedDate
        $slots = $this->generateAvailableSlots(
            $setting,
            $selectedDate,
            (int)$setting['user_id']
        );

        $html = $this->view->render('public.booking', [
            'setting'       => $setting,
            'host'          => $host,
            'selectedDate'  => $selectedDate,
            'availableDays' => $availableDays,
            'slots'         => $slots,
        ]);

        $this->response->html($html);
    }

    /**
     * Public Guest Facing: Process Booking form submission
     */
    public function book(string|int|null $slug = null): void
    {
        $slug = trim((string)($slug ?? $this->request->getParam('slug') ?? ''));
        $setting = (new BookingSetting)->table()
            ->where('slug', $slug)
            ->where('is_active', 1)
            ->first();

        if (!$setting) {
            $this->response->setStatusCode(404)->html('Booking schedule unavailable.');
            return;
        }

        $body = $this->request->getBody();
        $guestName  = trim((string)($body['guest_name'] ?? ''));
        $guestEmail = strtolower(trim((string)($body['guest_email'] ?? '')));
        $guestPhone = trim((string)($body['guest_phone'] ?? ''));
        $slotTime   = trim((string)($body['slot_time'] ?? ''));
        $notes      = trim((string)($body['notes'] ?? ''));

        if ($guestName === '' || $guestEmail === '' || $slotTime === '') {
            $this->redirect("/book/{$slug}?error=" . urlencode('Please fill in all required fields.'));
            return;
        }

        $workspaceId = (int)$setting['workspace_id'];
        $hostUserId  = (int)$setting['user_id'];
        $duration    = (int)($setting['duration_minutes'] ?? 30);

        // 1. Find or create Contact in CRM
        $person = (new Person)->table()
            ->where('workspace_id', $workspaceId)
            ->where('email', $guestEmail)
            ->first();

        if (!$person) {
            $nameParts = explode(' ', $guestName, 2);
            $firstName = $nameParts[0];
            $lastName  = $nameParts[1] ?? '';
            $personId = (new Person)->table()->insert([
                'workspace_id'     => $workspaceId,
                'first_name'       => $firstName,
                'last_name'        => $lastName,
                'email'            => $guestEmail,
                'phone'            => $guestPhone,
                'status'           => 'lead',
                'assigned_user_id' => $hostUserId,
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s'),
            ]);
        } else {
            $personId = (int)$person['id'];
        }

        // 2. Create Meeting Interaction
        $interactionId = (new Interaction)->table()->insert([
            'workspace_id'     => $workspaceId,
            'user_id'          => $hostUserId,
            'entity_type'      => 'people',
            'entity_id'        => $personId,
            'type'             => 'meeting',
            'title'            => "{$setting['title']} with {$guestName}",
            'description'      => "Guest Email: {$guestEmail}\nPhone: {$guestPhone}\nNotes: {$notes}",
            'outcome'          => 'scheduled',
            'duration_minutes' => $duration,
            'scheduled_at'     => $slotTime,
            'metadata'         => json_encode(['booked_via' => 'public_scheduler', 'slug' => $slug]),
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        // 3. Create Urgent Task Alarm for Host
        (new Task)->table()->insert([
            'workspace_id'     => $workspaceId,
            'title'            => "📅 Client Meeting: {$guestName} ({$setting['title']})",
            'description'      => "Scheduled at {$slotTime} for {$duration} minutes.\nGuest: {$guestName} <{$guestEmail}>\nPhone: {$guestPhone}\nAgenda: {$notes}",
            'priority'         => 'urgent',
            'status'           => 'pending',
            'due_date'         => date('Y-m-d', strtotime($slotTime)),
            'entity_type'      => 'people',
            'entity_id'        => $personId,
            'assigned_user_id' => $hostUserId,
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        // 4. Log to Activity Timeline
        ActivityLogger::log(
            $workspaceId,
            $hostUserId,
            'meeting_booked',
            'people',
            $personId,
            "Booked meeting via public scheduler for {$slotTime}"
        );

        // 5. Send Instant Email Confirmations via PHPMailer
        $host = (new User)->find($hostUserId);
        $mailer = new MailService();

        // Email to Host
        if (!empty($host['email'])) {
            $mailer->sendAlert(
                $host['email'],
                "📅 New Meeting Booked: {$guestName}",
                "New Meeting Scheduled",
                "A client has booked an appointment through your public scheduler.",
                [
                    'Client Name'   => $guestName,
                    'Email'         => $guestEmail,
                    'Phone'         => $guestPhone ?: 'Not provided',
                    'Date & Time'   => $slotTime,
                    'Duration'      => "{$duration} minutes",
                    'Client Notes'  => $notes ?: 'None',
                ],
                url("/people/{$personId}"),
                'Open Contact in CRM'
            );
        }

        // Confirmation Email to Guest
        $mailer->sendAlert(
            $guestEmail,
            "Confirmed: {$setting['title']} with " . ($host['name'] ?? 'CRX Team'),
            "Your Meeting is Confirmed!",
            "Thank you, {$guestName}. Your meeting has been scheduled successfully.",
            [
                'Meeting'       => $setting['title'],
                'Date & Time'   => $slotTime,
                'Duration'      => "{$duration} minutes",
                'Host'          => $host['name'] ?? 'CRM Team',
            ]
        );

        $this->redirect("/book/{$slug}/success?interaction_id={$interactionId}");
    }

    /**
     * Public Guest Facing: Booking Success Confirmation View
     */
    public function success(string|int|null $slug = null): void
    {
        $slug = trim((string)($slug ?? $this->request->getParam('slug') ?? ''));
        $setting = (new BookingSetting)->table()->where('slug', $slug)->first();
        $interactionId = (int)$this->request->getQuery('interaction_id');
        $interaction = (new Interaction)->find($interactionId);

        $html = $this->view->render('public.booking_success', [
            'setting'     => $setting,
            'interaction' => $interaction,
            'slug'        => $slug,
        ]);

        $this->response->html($html);
    }

    /**
     * Authenticated Admin: Configure user's personal booking scheduler settings
     */
    public function settings(): void
    {
        $ctx = $this->getContext();
        $setting = (new BookingSetting)->table()
            ->where('user_id', $ctx['userId'])
            ->where('workspace_id', $ctx['workspaceId'])
            ->first();

        if (!$setting) {
            $user = (new User)->find($ctx['userId']);
            $defaultSlug = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $user['name'] ?: 'user-' . $ctx['userId']));
            $id = (new BookingSetting)->table()->insert([
                'user_id'            => $ctx['userId'],
                'workspace_id'       => $ctx['workspaceId'],
                'slug'               => $defaultSlug,
                'title'              => "Discovery Call with " . ($user['name'] ?: 'Team'),
                'description'        => "30-minute 1-on-1 discovery and consultation call.",
                'duration_minutes'   => 30,
                'working_hours_start'=> '09:00',
                'working_hours_end'  => '17:00',
                'buffer_minutes'     => 15,
                'is_active'          => 1,
                'created_at'         => date('Y-m-d H:i:s'),
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);
            $setting = (new BookingSetting)->find($id);
        }

        $html = $this->view->render('settings.booking', [
            'setting' => $setting,
        ]);

        $this->response->html($html);
    }

    /**
     * Authenticated Admin: Update scheduler settings
     */
    public function updateSettings(): void
    {
        $ctx = $this->getContext();
        $body = $this->request->getBody();

        $slug = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)($body['slug'] ?? ''))));
        if ($slug === '') {
            $slug = 'user-' . $ctx['userId'];
        }

        $title       = trim((string)($body['title'] ?? 'Consultation Call'));
        $description = trim((string)($body['description'] ?? ''));
        $duration    = max(15, min(120, (int)($body['duration_minutes'] ?? 30)));
        $start       = trim((string)($body['working_hours_start'] ?? '09:00'));
        $end         = trim((string)($body['working_hours_end'] ?? '17:00'));
        $isActive    = isset($body['is_active']) ? 1 : 0;

        (new BookingSetting)->table()
            ->where('user_id', $ctx['userId'])
            ->where('workspace_id', $ctx['workspaceId'])
            ->update([
                'slug'                => $slug,
                'title'               => $title,
                'description'         => $description,
                'duration_minutes'    => $duration,
                'working_hours_start' => $start,
                'working_hours_end'   => $end,
                'is_active'           => $isActive,
                'updated_at'          => date('Y-m-d H:i:s'),
            ]);

        $this->session->setFlash('success', 'Booking scheduler settings updated successfully.');
        $this->redirect('/settings/booking');
    }

    /**
     * Helper to compute available slots without collision
     */
    private function generateAvailableSlots(array $setting, string $date, int $userId): array
    {
        $duration = (int)($setting['duration_minutes'] ?? 30);
        $startStr = $setting['working_hours_start'] ?: '09:00';
        $endStr   = $setting['working_hours_end'] ?: '17:00';

        $startTime = strtotime("{$date} {$startStr}:00");
        $endTime   = strtotime("{$date} {$endStr}:00");

        // Fetch existing meetings on that date
        $existing = (new Interaction)->table()
            ->where('type', 'meeting')
            ->where('scheduled_at', '>=', "{$date} 00:00:00")
            ->where('scheduled_at', '<=', "{$date} 23:59:59")
            ->get();

        $busyRanges = [];
        foreach ($existing as $m) {
            if (!empty($m['scheduled_at'])) {
                $mStart = strtotime($m['scheduled_at']);
                $mEnd   = $mStart + (max(15, (int)($m['duration_minutes'] ?? 30)) * 60);
                $busyRanges[] = ['start' => $mStart, 'end' => $mEnd];
            }
        }

        $now = time();
        $slots = [];
        $slotCursor = $startTime;

        while ($slotCursor + ($duration * 60) <= $endTime) {
            $slotEnd = $slotCursor + ($duration * 60);

            // Skip past slots for today
            if ($slotCursor > $now) {
                // Check if collides with any existing meeting
                $hasCollision = false;
                foreach ($busyRanges as $busy) {
                    if ($slotCursor < $busy['end'] && $slotEnd > $busy['start']) {
                        $hasCollision = true;
                        break;
                    }
                }

                if (!$hasCollision) {
                    $slots[] = [
                        'time'     => date('H:i', $slotCursor),
                        'display'  => date('h:i A', $slotCursor),
                        'datetime' => date('Y-m-d H:i:s', $slotCursor),
                    ];
                }
            }

            $slotCursor += ($duration * 60);
        }

        return $slots;
    }
}
