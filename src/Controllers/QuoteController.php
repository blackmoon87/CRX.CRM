<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Opportunity;
use App\Models\Person;
use App\Models\Company;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\MailService;
use App\Services\WorkspaceService;
use Spartan\Controller;

class QuoteController extends Controller
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
     * Admin: Quotes List
     */
    public function index(): void
    {
        $ctx = $this->getContext();
        $quotes = (new Quote)->table()
            ->where('workspace_id', $ctx['workspaceId'])
            ->orderBy('id', 'DESC')
            ->get();

        $html = $this->view->render('quotes.index', [
            'quotes' => $quotes,
        ]);

        $this->response->html($html);
    }

    /**
     * Admin: Create Quote Form
     */
    public function create(): void
    {
        $ctx = $this->getContext();

        if (!user_can('quotes.create') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', __('app.common.unauthorized_action') ?? 'You do not have permission to create quotes.');
            $this->redirect('/quotes');
            return;
        }

        $opportunities = (new Opportunity)->table()->where('workspace_id', $ctx['workspaceId'])->get();
        $people = (new Person)->table()->where('workspace_id', $ctx['workspaceId'])->get();
        $companies = (new Company)->table()->where('workspace_id', $ctx['workspaceId'])->get();

        $html = $this->view->render('quotes.create', [
            'opportunities' => $opportunities,
            'people'        => $people,
            'companies'     => $companies,
            'nextQuoteNum'  => 'QT-' . strtoupper(substr(uniqid(), -6)),
        ]);

        $this->response->html($html);
    }

    /**
     * Admin: Store Quote & Line Items
     */
    public function store(): void
    {
        $ctx = $this->getContext();

        if (!user_can('quotes.create') && !in_array(active_user_role(), ['owner', 'admin'], true)) {
            $this->session->setFlash('error', __('app.common.unauthorized_action') ?? 'You do not have permission to create quotes.');
            $this->redirect('/quotes');
            return;
        }

        $body = $this->request->getBody();

        $quoteNum = trim((string)($body['quote_number'] ?? 'QT-' . rand(1000, 9999)));
        $title    = trim((string)($body['title'] ?? 'Enterprise Software & Services'));
        $oppId    = !empty($body['opportunity_id']) ? (int)$body['opportunity_id'] : null;
        $personId = !empty($body['person_id']) ? (int)$body['person_id'] : null;
        $companyId= !empty($body['company_id']) ? (int)$body['company_id'] : null;
        $notes    = trim((string)($body['notes'] ?? ''));
        $validUntil = !empty($body['valid_until']) ? $body['valid_until'] : date('Y-m-d', strtotime('+30 days'));

        $taxPercent = max(0, (float)($body['tax_percent'] ?? 0));
        $discount   = max(0, (float)($body['discount_amount'] ?? 0));

        // Process line items
        $descriptions = (array)($body['item_description'] ?? []);
        $quantities   = (array)($body['item_quantity'] ?? []);
        $unitPrices   = (array)($body['item_unit_price'] ?? []);

        $subtotal = 0.00;
        $parsedItems = [];
        foreach ($descriptions as $i => $desc) {
            $desc = trim((string)$desc);
            if ($desc === '') continue;
            $qty = max(1, (float)($quantities[$i] ?? 1));
            $unit = max(0, (float)($unitPrices[$i] ?? 0));
            $total = $qty * $unit;
            $subtotal += $total;

            $parsedItems[] = [
                'description' => $desc,
                'quantity'    => $qty,
                'unit_price'  => $unit,
                'total_price' => $total,
            ];
        }

        if (empty($parsedItems)) {
            $parsedItems[] = [
                'description' => 'Professional Software Services',
                'quantity'    => 1,
                'unit_price'  => 1000.00,
                'total_price' => 1000.00,
            ];
            $subtotal = 1000.00;
        }

        $taxAmount = ($subtotal * $taxPercent) / 100.0;
        $totalAmount = max(0, ($subtotal + $taxAmount) - $discount);
        $publicToken = bin2hex(random_bytes(16));

        $quoteId = (new Quote)->table()->insert([
            'workspace_id'    => $ctx['workspaceId'],
            'opportunity_id'  => $oppId,
            'person_id'       => $personId,
            'company_id'      => $companyId,
            'quote_number'    => $quoteNum,
            'title'           => $title,
            'status'          => 'sent',
            'subtotal'        => $subtotal,
            'tax_percent'     => $taxPercent,
            'tax_amount'      => $taxAmount,
            'discount_amount' => $discount,
            'total_amount'    => $totalAmount,
            'currency'        => 'USD',
            'public_token'    => $publicToken,
            'valid_until'     => $validUntil,
            'notes'           => $notes,
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        foreach ($parsedItems as $item) {
            (new QuoteItem)->table()->insert([
                'quote_id'    => $quoteId,
                'description' => $item['description'],
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'],
                'total_price' => $item['total_price'],
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        ActivityLogger::log(
            $ctx['workspaceId'],
            $ctx['userId'],
            'quote_created',
            'quotes',
            $quoteId,
            "Created Quote #{$quoteNum} ({$title}) for \${$totalAmount}"
        );

        $this->session->setFlash('success', "Quote #{$quoteNum} generated successfully!");
        $this->redirect("/quotes/{$quoteId}");
    }

    /**
     * Admin: Show Quote Printable details
     */
    public function show(string|int|null $id = null): void
    {
        $ctx = $this->getContext();
        $id = (int)($id ?? $this->request->getParam('id'));

        $quote = (new Quote)->table()->where('id', $id)->where('workspace_id', $ctx['workspaceId'])->first();
        if (!$quote) {
            $this->session->setFlash('error', 'Quote not found.');
            $this->redirect('/quotes');
            return;
        }

        $items = (new QuoteItem)->table()->where('quote_id', $id)->get();
        $opp = $quote['opportunity_id'] ? (new Opportunity)->find((int)$quote['opportunity_id']) : null;
        $person = $quote['person_id'] ? (new Person)->find((int)$quote['person_id']) : null;
        $company = $quote['company_id'] ? (new Company)->find((int)$quote['company_id']) : null;

        $html = $this->view->render('quotes.show', [
            'quote'   => $quote,
            'items'   => $items,
            'opp'     => $opp,
            'person'  => $person,
            'company' => $company,
        ]);

        $this->response->html($html);
    }

    /**
     * Public Guest Facing: Client View Proposal & Acceptance
     */
    public function publicView(string|int|null $token = null): void
    {
        $token = trim((string)($token ?? $this->request->getParam('token') ?? ''));
        $quote = (new Quote)->table()->where('public_token', $token)->first();

        if (!$quote) {
            $this->response->setStatusCode(404)->html('Quote or proposal link expired.');
            return;
        }

        $items = (new QuoteItem)->table()->where('quote_id', (int)$quote['id'])->get();
        $opp = $quote['opportunity_id'] ? (new Opportunity)->find((int)$quote['opportunity_id']) : null;
        $person = $quote['person_id'] ? (new Person)->find((int)$quote['person_id']) : null;
        $company = $quote['company_id'] ? (new Company)->find((int)$quote['company_id']) : null;

        $html = $this->view->render('public.quote', [
            'quote'   => $quote,
            'items'   => $items,
            'opp'     => $opp,
            'person'  => $person,
            'company' => $company,
        ]);

        $this->response->html($html);
    }

    /**
     * Public Guest Facing: 1-Click Accept Quote
     */
    public function accept(string|int|null $token = null): void
    {
        $token = trim((string)($token ?? $this->request->getParam('token') ?? ''));
        $quote = (new Quote)->table()->where('public_token', $token)->first();

        if (!$quote) {
            $this->response->setStatusCode(404)->json(['success' => false, 'error' => 'Quote not found.']);
            return;
        }

        $now = date('Y-m-d H:i:s');
        (new Quote)->table()->where('id', (int)$quote['id'])->update([
            'status'      => 'accepted',
            'accepted_at' => $now,
            'updated_at'  => $now,
        ]);

        // Auto-advance linked Opportunity to closed_won!
        if (!empty($quote['opportunity_id'])) {
            (new Opportunity)->table()
                ->where('id', (int)$quote['opportunity_id'])
                ->update([
                    'stage'      => 'closed_won',
                    'status'     => 'closed',
                    'updated_at' => $now,
                ]);

            ActivityLogger::log(
                (int)$quote['workspace_id'],
                1,
                'deal_won_via_quote',
                'opportunities',
                (int)$quote['opportunity_id'],
                "Deal automatically won upon client acceptance of Quote #{$quote['quote_number']} (\${$quote['total_amount']})"
            );
        }

        // Notify team via MailService
        $adminEmail = config('mail.from.address') ?? 'admin@crx.local';
        (new MailService())->sendAlert(
            $adminEmail,
            "🏆 Quote #{$quote['quote_number']} ACCEPTED by Client!",
            "Proposal Won!",
            "Client has accepted Quote #{$quote['quote_number']} ({$quote['title']}) totaling \${$quote['total_amount']}.",
            [
                'Quote #'    => $quote['quote_number'],
                'Total'      => '$' . number_format((float)$quote['total_amount'], 2),
                'Accepted At'=> $now,
            ],
            url("/quotes/{$quote['id']}"),
            'View Won Quote in CRM'
        );

        $this->redirect("/quote/{$token}?accepted=true");
    }
}
