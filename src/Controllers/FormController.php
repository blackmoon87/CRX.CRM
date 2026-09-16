<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\LeadForm;
use App\Models\Person;
use App\Models\Company;
use App\Models\Note;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\AutomationService;
use App\Services\WorkspaceService;
use Spartan\Controller;

class FormController extends Controller
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
     * Admin: List all Lead Forms & Embed Generator
     */
    public function index(): void
    {
        $ctx = $this->getContext();
        $forms = (new LeadForm)->table()
            ->where('workspace_id', $ctx['workspaceId'])
            ->orderBy('id', 'DESC')
            ->get();

        $users = (new User)->all();

        $html = $this->view->render('settings.forms.index', [
            'forms' => $forms,
            'users' => $users,
        ]);

        $this->response->html($html);
    }

    /**
     * Admin: Create new Form
     */
    public function store(): void
    {
        $ctx = $this->getContext();
        $body = $this->request->getBody();

        $name = trim((string)($body['name'] ?? 'Website Inquiry Form'));
        $title = trim((string)($body['title'] ?? 'Contact Us'));
        $description = trim((string)($body['description'] ?? 'Fill out the form below to get in touch with our team.'));
        $btnText = trim((string)($body['submit_button_text'] ?? 'Send Message'));
        $successMsg = trim((string)($body['success_message'] ?? 'Thank you! We received your message and will reach out shortly.'));
        $redirectUrl = trim((string)($body['redirect_url'] ?? ''));
        $assignedUser = (int)($body['assigned_user_id'] ?? $ctx['userId']);

        $uuid = bin2hex(random_bytes(12));

        $fieldsSchema = [
            ['name' => 'first_name', 'label' => 'First Name', 'type' => 'text', 'required' => true],
            ['name' => 'last_name',  'label' => 'Last Name',  'type' => 'text', 'required' => false],
            ['name' => 'email',      'label' => 'Work Email', 'type' => 'email', 'required' => true],
            ['name' => 'phone',      'label' => 'Phone Number', 'type' => 'tel', 'required' => false],
            ['name' => 'company',    'label' => 'Company Name', 'type' => 'text', 'required' => false],
            ['name' => 'message',    'label' => 'Message / Project Scope', 'type' => 'textarea', 'required' => false],
        ];

        (new LeadForm)->table()->insert([
            'workspace_id'        => $ctx['workspaceId'],
            'uuid'                => $uuid,
            'name'                => $name,
            'title'               => $title,
            'description'         => $description,
            'fields_schema'       => json_encode($fieldsSchema),
            'submit_button_text'  => $btnText,
            'success_message'     => $successMsg,
            'redirect_url'        => $redirectUrl !== '' ? $redirectUrl : null,
            'assigned_user_id'    => $assignedUser > 0 ? $assignedUser : null,
            'submissions_count'   => 0,
            'is_active'           => 1,
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        $this->session->setFlash('success', "Lead form '{$name}' created with embed widget ready!");
        $this->redirect('/settings/forms');
    }

    /**
     * Public CORS-Enabled API: Handle Web-to-Lead submissions
     */
    public function submit(string|int|null $uuid = null): void
    {
        // Set CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }

        $uuid = trim((string)($uuid ?? $this->request->getParam('uuid') ?? ''));
        $form = (new LeadForm)->table()->where('uuid', $uuid)->where('is_active', 1)->first();

        if (!$form) {
            $this->response->setStatusCode(404)->json(['success' => false, 'error' => 'Form not found or inactive.']);
            return;
        }

        $body = $this->request->getBody();
        if (empty($body)) {
            $rawInput = file_get_contents('php://input');
            $body = json_decode($rawInput, true) ?: [];
        }

        $email = strtolower(trim((string)($body['email'] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->response->setStatusCode(422)->json(['success' => false, 'error' => 'A valid email address is required.']);
            return;
        }

        $firstName = trim((string)($body['first_name'] ?? ''));
        $lastName  = trim((string)($body['last_name'] ?? ''));
        if ($firstName === '' && !empty($body['name'])) {
            $parts = explode(' ', trim((string)$body['name']), 2);
            $firstName = $parts[0];
            $lastName  = $parts[1] ?? '';
        }
        if ($firstName === '') {
            $firstName = 'Website';
            $lastName  = 'Lead';
        }

        $phone       = trim((string)($body['phone'] ?? ''));
        $companyName = trim((string)($body['company'] ?? ''));
        $message     = trim((string)($body['message'] ?? ($body['notes'] ?? '')));

        $workspaceId = (int)$form['workspace_id'];
        $assignedUserId = (int)($form['assigned_user_id'] ?? 1);

        // 1. Check or create Company
        $companyId = null;
        if ($companyName !== '') {
            $comp = (new Company)->table()->where('workspace_id', $workspaceId)->where('name', $companyName)->first();
            if ($comp) {
                $companyId = (int)$comp['id'];
            } else {
                $companyId = (new Company)->table()->insert([
                    'workspace_id'     => $workspaceId,
                    'name'             => $companyName,
                    'created_at'       => date('Y-m-d H:i:s'),
                    'updated_at'       => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // 2. Find or create Contact in CRM
        $person = (new Person)->table()
            ->where('workspace_id', $workspaceId)
            ->where('email', $email)
            ->first();

        if ($person) {
            $personId = (int)$person['id'];
            (new Person)->table()->where('id', $personId)->update([
                'phone'      => !empty($phone) ? $phone : $person['phone'],
                'company_id' => $companyId ?: $person['company_id'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $personId = (new Person)->table()->insert([
                'workspace_id'     => $workspaceId,
                'company_id'       => $companyId,
                'first_name'       => $firstName,
                'last_name'        => $lastName,
                'email'            => $email,
                'phone'            => $phone,
                'status'           => 'lead',
                'assigned_user_id' => $assignedUserId,
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s'),
            ]);
        }

        // 3. Log submission note
        if ($message !== '') {
            (new Note)->table()->insert([
                'workspace_id' => $workspaceId,
                'entity_type'  => 'people',
                'entity_id'    => $personId,
                'user_id'      => $assignedUserId,
                'title'        => "Web Inquiry via {$form['name']}",
                'body'         => $message,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
        }

        // 4. Increment submission counter
        (new LeadForm)->table()->where('id', (int)$form['id'])->update([
            'submissions_count' => ((int)($form['submissions_count'] ?? 0)) + 1,
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        ActivityLogger::log(
            $workspaceId,
            $assignedUserId,
            'lead_form_submitted',
            'people',
            $personId,
            "Captured lead {$firstName} {$lastName} via web form '{$form['name']}'"
        );

        // 5. Fire Automation Event: lead.created
        (new AutomationService())->dispatch($workspaceId, 'lead.created', [
            'entity_type' => 'people',
            'entity_id'   => $personId,
            'name'        => "{$firstName} {$lastName}",
            'email'       => $email,
            'phone'       => $phone,
            'company'     => $companyName,
            'user_id'     => $assignedUserId,
        ]);

        // Response
        $isAjax = $this->request->isAjax() || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
        if ($isAjax) {
            $this->response->json([
                'success' => true,
                'message' => $form['success_message'] ?: 'Inquiry submitted successfully!',
                'redirect_url' => $form['redirect_url'],
            ]);
            return;
        }

        if (!empty($form['redirect_url'])) {
            $this->redirect($form['redirect_url']);
            return;
        }

        $this->response->html("
            <div style=\"font-family:sans-serif;text-align:center;padding:4rem 1rem;background:#0B0F19;color:#fff;min-height:100vh;\">
                <div style=\"max-width:480px;margin:0 auto;background:#111827;padding:2.5rem;border-radius:16px;border:1px solid #1F2937;\">
                    <div style=\"font-size:2.5rem;color:#10B981;margin-bottom:1rem;\">✓</div>
                    <h2 style=\"margin:0 0 0.75rem 0;\">" . htmlspecialchars($form['title']) . "</h2>
                    <p style=\"color:#9CA3AF;line-height:1.6;\">" . htmlspecialchars($form['success_message']) . "</p>
                </div>
            </div>
        ");
    }
}
