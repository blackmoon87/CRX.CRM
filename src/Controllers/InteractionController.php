<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Interaction;
use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\MailService;
use App\Services\WorkspaceService;
use Spartan\Controller;

class InteractionController extends Controller
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

    public function store(): void
    {
        $ctx = $this->getContext();
        $body = $this->request->getBody();

        $entityType = trim((string)($body['entity_type'] ?? ''));
        $entityId = (int)($body['entity_id'] ?? 0);
        $type = trim((string)($body['type'] ?? 'note'));
        $description = trim((string)($body['description'] ?? ''));

        if ($entityType === '' || $entityId <= 0 || $description === '') {
            $this->session->setFlash('error', 'Please enter interaction details.');
            $this->redirectBack("/{$entityType}/{$entityId}");
            return;
        }

        $duration = !empty($body['duration_minutes']) ? (int)$body['duration_minutes'] : null;
        $outcome = !empty($body['outcome']) ? trim((string)$body['outcome']) : null;
        $title = !empty($body['title']) ? trim((string)$body['title']) : ucfirst($type) . ' Log';
        $scheduledAt = !empty($body['scheduled_at']) ? trim((string)$body['scheduled_at']) : null;

        $id = (new Interaction)->table()->insert([
            'workspace_id'     => $ctx['workspaceId'],
            'user_id'          => $ctx['userId'],
            'entity_type'      => $entityType,
            'entity_id'        => $entityId,
            'type'             => $type,
            'title'            => $title,
            'description'      => $description,
            'outcome'          => $outcome,
            'duration_minutes' => $duration,
            'scheduled_at'     => $scheduledAt,
            'metadata'         => null,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        // If meeting or scheduled event, automatically create an active alarm task and dispatch email alert
        if (!empty($scheduledAt)) {
            $dueDate = date('Y-m-d', strtotime($scheduledAt));
            (new Task)->table()->insert([
                'workspace_id'     => $ctx['workspaceId'],
                'title'            => "📅 Meeting Alarm: {$title}",
                'description'      => "Scheduled Meeting at {$scheduledAt}\n\nAgenda / Details: {$description}",
                'priority'         => 'urgent',
                'due_date'         => $dueDate,
                'status'           => 'pending',
                'entity_type'      => $entityType,
                'entity_id'        => $entityId,
                'assigned_user_id' => $ctx['userId'],
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s'),
            ]);

            // Dispatch instant email alert to user
            $user = (new User)->find($ctx['userId']);
            if (!empty($user['email'])) {
                $targetUrl = function_exists('url') ? url("/{$entityType}/{$entityId}") : "/{$entityType}/{$entityId}";
                (new MailService())->sendAlert(
                    $user['email'],
                    "📅 Meeting Alarm Scheduled: {$title}",
                    "Meeting Alarm Notification",
                    "A new meeting '{$title}' has been scheduled for {$scheduledAt}.",
                    [
                        'Meeting Title' => $title,
                        'Date & Time'   => $scheduledAt,
                        'Duration'      => "{$duration} mins",
                        'Related Entity'=> ucfirst($entityType) . " #{$entityId}",
                        'Agenda'        => !empty($description) ? $description : 'No additional details provided.',
                    ],
                    $targetUrl,
                    'Open Record in CRX CRM'
                );
            }
        }

        ActivityLogger::log(
            $ctx['workspaceId'],
            $ctx['userId'],
            'logged_' . $type,
            $entityType,
            $entityId,
            "Logged {$type}: {$title}" . (!empty($scheduledAt) ? " (Scheduled: {$scheduledAt})" : '')
        );

        $flashMsg = ($type === 'meeting' && !empty($scheduledAt))
            ? "Meeting scheduled for {$scheduledAt}! Alarm reminder task activated."
            : ucfirst($type) . ' logged successfully!';

        $this->session->setFlash('success', $flashMsg);
        $this->redirectBack("/{$entityType}/{$entityId}");
    }

    public function ics(string|int|null $id = null): void
    {
        $ctx = $this->getContext();
        $id = (int)($id ?? $this->request->getParam('id'));

        $act = (new Interaction)->table()
            ->where('id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->first();

        if (!$act || empty($act['scheduled_at'])) {
            $this->session->setFlash('error', 'Meeting not found or has no scheduled date.');
            $this->redirectBack('/dashboard');
            return;
        }

        $start = strtotime($act['scheduled_at']);
        $end = $start + (max(30, (int)($act['duration_minutes'] ?? 30)) * 60);

        $dtStart = gmdate('Ymd\THis\Z', $start);
        $dtEnd   = gmdate('Ymd\THis\Z', $end);
        $summary = addcslashes($act['title'], ",;\\");
        $desc    = addcslashes($act['description'], ",;\\");

        $ics = "BEGIN:VCALENDAR\r\n"
             . "VERSION:2.0\r\n"
             . "PRODID:-//CRX CRM//Meeting Calendar//EN\r\n"
             . "CALSCALE:GREGORIAN\r\n"
             . "METHOD:REQUEST\r\n"
             . "BEGIN:VEVENT\r\n"
             . "UID:crx-meeting-{$id}-" . time() . "@crx.local\r\n"
             . "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n"
             . "DTSTART:{$dtStart}\r\n"
             . "DTEND:{$dtEnd}\r\n"
             . "SUMMARY:{$summary}\r\n"
             . "DESCRIPTION:{$desc}\r\n"
             . "STATUS:CONFIRMED\r\n"
             . "BEGIN:VALARM\r\n"
             . "TRIGGER:-PT15M\r\n"
             . "ACTION:DISPLAY\r\n"
             . "DESCRIPTION:Meeting Reminder: {$summary}\r\n"
             . "END:VALARM\r\n"
             . "END:VEVENT\r\n"
             . "END:VCALENDAR\r\n";

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="meeting_' . $id . '.ics"');
        echo $ics;
        exit;
    }

    public function upcomingAlarms(): void
    {
        $ctx = $this->getContext();
        $endOfDay = date('Y-m-d 23:59:59');

        $upcoming = (new Interaction)->table()
            ->where('workspace_id', $ctx['workspaceId'])
            ->where('type', 'meeting')
            ->where('scheduled_at', '>=', date('Y-m-d H:i:s', strtotime('-1 hour')))
            ->where('scheduled_at', '<=', $endOfDay)
            ->orderBy('scheduled_at', 'ASC')
            ->get();

        $this->response->json([
            'success' => true,
            'count'   => count($upcoming),
            'alarms'  => $upcoming,
        ]);
    }

    public function destroy(string|int|null $id = null): void
    {
        $ctx = $this->getContext();
        $id = (int)($id ?? $this->request->getParam('id'));

        $interaction = (new Interaction)->table()
            ->where('id', $id)
            ->where('workspace_id', $ctx['workspaceId'])
            ->first();

        if ($interaction) {
            (new Interaction)->table()->where('id', $id)->delete();
            $this->session->setFlash('success', 'Activity removed.');
            $this->redirectBack("/{$interaction['entity_type']}/{$interaction['entity_id']}");
            return;
        }

        $this->session->setFlash('error', 'Activity not found.');
        $this->redirectBack('/dashboard');
    }
}

