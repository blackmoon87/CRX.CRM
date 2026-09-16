<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Spartan\Application;
use App\Models\EmailTracking;


class MailService
{
    private array $config;

    public function __construct(?array $config = null)
    {
        if ($config !== null) {
            $this->config = $config;
        } elseif (function_exists('config')) {
            $this->config = config('mail') ?? [];
        } elseif (isset(Application::$app->config['mail'])) {
            $this->config = Application::$app->config['mail'];
        } else {
            $this->config = [];
        }
    }

    /**
     * Send a formatted Alert Email with responsive HTML template.
     */
    public function sendAlert(
        string $to,
        string $subject,
        string $title,
        string $message,
        array $details = [],
        ?string $actionUrl = null,
        string $actionText = 'View in CRX CRM'
    ): array {
        $htmlBody = $this->buildAlertTemplate($title, $message, $details, $actionUrl, $actionText);
        $plainText = strip_tags("{$title}\n\n{$message}\n\n" . json_encode($details, JSON_PRETTY_PRINT));

        return $this->send($to, $subject, $htmlBody, $plainText);
    }

    /**
     * Send an email via PHPMailer or Fallback Driver.
     */
    public function send(
        string $to,
        string $subject,
        string $htmlBody,
        string $altBody = '',
        array $attachments = [],
        ?array $tracking = null
    ): array {
        $trackingToken = null;
        if ($tracking !== null && ($tracking['enabled'] ?? true)) {
            $trackRes = $this->embedTracking(
                $htmlBody,
                $to,
                $subject,
                $tracking['entity_type'] ?? null,
                $tracking['entity_id'] ?? null,
                $tracking['workspace_id'] ?? 1
            );
            $htmlBody = $trackRes['body'];
            $trackingToken = $trackRes['token'];
        }

        $driver = strtolower($this->config['driver'] ?? 'log');
        $fromEmail = $this->config['from']['address'] ?? 'notifications@crx.local';
        $fromName  = $this->config['from']['name'] ?? 'CRX CRM Alerts';

        // 1. If driver is 'log' or SMTP is unconfigured, write to storage/logs/mail.log
        if ($driver === 'log' || ($driver === 'smtp' && empty($this->config['username']) && empty($this->config['host']))) {
            $logResult = $this->logEmail($to, $subject, $htmlBody, $fromEmail, $fromName);
            if ($trackingToken) {
                $logResult['tracking_token'] = $trackingToken;
            }
            return $logResult;
        }

        // 2. Real SMTP Delivery via PHPMailer
        try {
            $mail = new PHPMailer(true);

            // Server settings
            if ($driver === 'smtp') {
                $mail->isSMTP();
                $mail->Host       = $this->config['host'] ?? '127.0.0.1';
                $mail->SMTPAuth   = !empty($this->config['username']);
                $mail->Username   = $this->config['username'] ?? '';
                $mail->Password   = $this->config['password'] ?? '';
                $mail->Port       = (int)($this->config['port'] ?? 587);

                $enc = strtolower($this->config['encryption'] ?? 'tls');
                if ($enc === 'ssl') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                } elseif ($enc === 'tls') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                } else {
                    $mail->SMTPAutoTLS = false;
                }

                if (!empty($this->config['debug'])) {
                    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                }
            } else {
                $mail->isMail();
            }

            $mail->CharSet = 'UTF-8';
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);

            // Attachments
            foreach ($attachments as $filePath) {
                if (file_exists($filePath)) {
                    $mail->addAttachment($filePath);
                }
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $altBody ?: strip_tags($htmlBody);

            $mail->send();

            $this->logActivity("Email successfully delivered to {$to} [Subject: {$subject}] via {$driver}");

            return [
                'success' => true,
                'driver'  => $driver,
                'to'      => $to,
                'subject' => $subject,
                'message' => 'Message has been sent successfully.',
            ];
        } catch (PHPMailerException $e) {
            $errorMsg = "Mailer Error [{$to}]: " . $e->getMessage();
            $this->logActivity($errorMsg, 'ERROR');

            return [
                'success' => false,
                'driver'  => $driver,
                'to'      => $to,
                'subject' => $subject,
                'error'   => $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            $this->logActivity("Unexpected Mailer Exception: " . $e->getMessage(), 'CRITICAL');
            return [
                'success' => false,
                'driver'  => $driver,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Local driver: Log sent email to storage/logs/mail.log
     */
    private function logEmail(string $to, string $subject, string $htmlBody, string $fromEmail, string $fromName): array
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }

        $entry = sprintf(
            "[%s] [DRIVER: LOG] From: \"%s\" <%s> | To: <%s> | Subject: %s\nBODY:\n%s\n%s\n",
            date('Y-m-d H:i:s'),
            $fromName,
            $fromEmail,
            $to,
            $subject,
            $htmlBody,
            str_repeat('-', 80)
        );

        @file_put_contents($logDir . '/mail.log', $entry, FILE_APPEND);

        return [
            'success' => true,
            'driver'  => 'log',
            'to'      => $to,
            'subject' => $subject,
            'message' => 'Email saved to storage/logs/mail.log (Log Driver)',
        ];
    }

    private function logActivity(string $msg, string $level = 'INFO'): void
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        @file_put_contents(
            $logDir . '/mail.log',
            sprintf("[%s] [%s] %s\n", date('Y-m-d H:i:s'), $level, $msg),
            FILE_APPEND
        );
    }

    /**
     * Build responsive high-contrast HTML email alert template
     */
    private function buildAlertTemplate(
        string $title,
        string $message,
        array $details,
        ?string $actionUrl,
        string $actionText
    ): string {
        $detailRows = '';
        foreach ($details as $label => $val) {
            $valStr = is_array($val) ? json_encode($val) : htmlspecialchars((string)$val);
            $detailRows .= "
                <tr>
                    <td style=\"padding: 8px 12px; border-bottom: 1px solid #2d3748; color: #94a3b8; font-size: 13px; font-weight: 600;\">" . htmlspecialchars((string)$label) . "</td>
                    <td style=\"padding: 8px 12px; border-bottom: 1px solid #2d3748; color: #f8fafc; font-size: 13px;\">{$valStr}</td>
                </tr>";
        }

        $buttonHtml = '';
        if (!empty($actionUrl)) {
            $buttonHtml = "
                <div style=\"margin: 28px 0; text-align: center;\">
                    <a href=\"" . htmlspecialchars($actionUrl) . "\" style=\"background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-weight: bold; font-size: 14px; display: inline-block; box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);\">
                        " . htmlspecialchars($actionText) . "
                    </a>
                </div>";
        }

        $year = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
</head>
<body style="margin: 0; padding: 24px; background-color: #0b0f19; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 600px; margin: 0 auto; background: #131b2e; border: 1px solid #1e293b; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); padding: 24px; border-bottom: 1px solid #3730a3; text-align: left;">
            <div style="display: inline-block; background: #4f46e5; color: #ffffff; font-size: 10px; font-weight: 800; letter-spacing: 1px; padding: 3px 8px; border-radius: 4px; text-transform: uppercase; margin-bottom: 8px;">CRX SYSTEM ALERT</div>
            <h1 style="margin: 0; color: #ffffff; font-size: 20px; font-weight: 700;">{$title}</h1>
        </div>

        <!-- Body Content -->
        <div style="padding: 24px; color: #cbd5e1; font-size: 14px; line-height: 1.6;">
            <p style="margin-top: 0; color: #e2e8f0; font-size: 15px;">{$message}</p>

            <!-- Metadata Table -->
            <div style="background: #0d1322; border: 1px solid #1e293b; border-radius: 8px; overflow: hidden; margin: 20px 0;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    {$detailRows}
                </table>
            </div>

            {$buttonHtml}

            <p style="font-size: 12px; color: #64748b; margin-top: 24px; border-top: 1px solid #1e293b; padding-top: 16px;">
                This automated notification was generated by CRX Workflow & Alert Engine. You can modify notification rules in CRM Settings.
            </p>
        </div>

        <!-- Footer -->
        <div style="background: #090d16; padding: 16px 24px; text-align: center; color: #475569; font-size: 11px;">
            &copy; {$year} CRX CRM. High-Performance Enterprise Platform.
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Injects 1x1 transparent open pixel and rewrites links for click tracking.
     */
    public function embedTracking(
        string $htmlBody,
        string $to,
        string $subject,
        ?string $entityType = null,
        int|string|null $entityId = null,
        int $workspaceId = 1
    ): array {
        $token = bin2hex(random_bytes(16));
        $cleanEntityId = $entityId !== null ? (int)$entityId : null;

        (new EmailTracking)->table()->insert([
            'workspace_id'    => $workspaceId,
            'entity_type'     => $entityType,
            'entity_id'       => $cleanEntityId,
            'tracking_token'  => $token,
            'recipient_email' => $to,
            'subject'         => $subject,
            'open_count'      => 0,
            'click_count'     => 0,
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        $baseUrl = function_exists('url') ? url('/') : 'http://localhost:8000';
        $pixelUrl = rtrim($baseUrl, '/') . "/track/open/{$token}";
        $pixelTag = "<img src=\"{$pixelUrl}\" width=\"1\" height=\"1\" alt=\"\" style=\"display:none;width:1px;height:1px;border:0;\" />";

        // Rewrite links: href="http..." -> /track/click/{token}?url=http...
        $trackedBody = preg_replace_callback('/href=([\'"])(https?:\/\/[^\'"]+)\1/i', function ($matches) use ($baseUrl, $token) {
            $quote = $matches[1];
            $origUrl = $matches[2];
            if (strpos($origUrl, '/track/') !== false) {
                return $matches[0];
            }
            $clickUrl = rtrim($baseUrl, '/') . "/track/click/{$token}?url=" . urlencode($origUrl);
            return "href={$quote}{$clickUrl}{$quote}";
        }, $htmlBody);

        if (stripos($trackedBody, '</body>') !== false) {
            $trackedBody = str_ireplace('</body>', $pixelTag . '</body>', $trackedBody);
        } else {
            $trackedBody .= $pixelTag;
        }

        return [
            'token' => $token,
            'body'  => $trackedBody,
        ];
    }
}

