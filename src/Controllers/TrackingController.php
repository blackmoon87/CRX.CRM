<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\EmailTracking;
use App\Services\ActivityLogger;
use Spartan\Controller;

class TrackingController extends Controller
{
    /**
     * 1x1 Transparent PNG Tracking Pixel for Email Opens
     */
    public function pixel(string|int|null $token = null): void
    {
        $token = trim((string)($token ?? $this->request->getParam('token') ?? ''));

        if ($token !== '') {
            $tracking = (new EmailTracking)->table()->where('tracking_token', $token)->first();
            if ($tracking) {
                $now = date('Y-m-d H:i:s');
                $newCount = ((int)($tracking['open_count'] ?? 0)) + 1;
                $firstOpened = $tracking['first_opened_at'] ?? $now;

                (new EmailTracking)->table()->where('id', (int)$tracking['id'])->update([
                    'open_count'      => $newCount,
                    'first_opened_at' => $firstOpened,
                    'last_opened_at'  => $now,
                    'ip_address'      => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    'user_agent'      => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                    'updated_at'      => $now,
                ]);

                // Log engagement to contact timeline if linked
                if (!empty($tracking['entity_type']) && !empty($tracking['entity_id'])) {
                    ActivityLogger::log(
                        (int)$tracking['workspace_id'],
                        1,
                        'email_opened',
                        $tracking['entity_type'],
                        (int)$tracking['entity_id'],
                        "Recipient opened email [{$tracking['subject']}] (Total opens: {$newCount})"
                    );
                }
            }
        }

        // Return pure 1x1 transparent PNG bytes
        $png1x1 = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');

        $this->response->setHeader('Content-Type', 'image/png');
        $this->response->setHeader('Content-Length', (string)strlen($png1x1));
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
        $this->response->setHeader('Pragma', 'no-cache');
        $this->response->setHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT');
        $this->response->setContent($png1x1);
    }

    /**
     * Link Click Tracking & Safe Redirection
     */
    public function click(string|int|null $token = null): void
    {
        $token = trim((string)($token ?? $this->request->getParam('token') ?? ''));
        $targetUrl = $this->request->getQuery('url');

        if ($token !== '') {
            $tracking = (new EmailTracking)->table()->where('tracking_token', $token)->first();
            if ($tracking) {
                $now = date('Y-m-d H:i:s');
                $newClicks = ((int)($tracking['click_count'] ?? 0)) + 1;

                (new EmailTracking)->table()->where('id', (int)$tracking['id'])->update([
                    'click_count'      => $newClicks,
                    'last_clicked_at'  => $now,
                    'last_clicked_url' => substr((string)$targetUrl, 0, 500),
                    'updated_at'       => $now,
                ]);

                if (!empty($tracking['entity_type']) && !empty($tracking['entity_id'])) {
                    ActivityLogger::log(
                        (int)$tracking['workspace_id'],
                        1,
                        'email_link_clicked',
                        $tracking['entity_type'],
                        (int)$tracking['entity_id'],
                        "Recipient clicked link in [{$tracking['subject']}]: {$targetUrl}"
                    );
                }
            }
        }

        if (empty($targetUrl) || !filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            $this->redirect('/');
            return;
        }

        $this->redirect($targetUrl);
    }
}
