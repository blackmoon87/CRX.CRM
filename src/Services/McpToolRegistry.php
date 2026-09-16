<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Person;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\Note;
use App\Models\Interaction;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\CustomField;
use App\Models\ActivityLog;
use App\Models\Workspace;
use App\Models\User;
use App\Services\AiManagerService;
use App\Services\AutomationService;

class McpToolRegistry
{
    /**
     * Return all 37 Relaticle-compatible MCP Tool definitions.
     */
    public function getTools(): array
    {
        return [
            // --- System & Discovery Tools (5) ---
            [
                'name' => 'whoami',
                'description' => 'Returns current user identity, permissions, and active workspace context.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => new \stdClass(),
                ],
            ],
            [
                'name' => 'get_crm_summary',
                'description' => 'Returns comprehensive summary statistics of the CRM (companies, contacts, active deal pipeline values by stage, pending tasks).',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => new \stdClass(),
                ],
            ],
            [
                'name' => 'get_crm_schema',
                'description' => 'Returns metadata on entities, allowed opportunity stages, and custom fields configured for this workspace.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => new \stdClass(),
                ],
            ],
            [
                'name' => 'search',
                'description' => 'Search globally across companies, contacts, opportunities, and notes.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Keyword to search for across CRM entities'],
                    ],
                    'required' => ['query'],
                ],
            ],
            [
                'name' => 'fetch',
                'description' => 'Universal entity fetcher. Retrieve any CRM record by entity type and ID.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'entity_type' => ['type' => 'string', 'description' => 'companies, people, opportunities, tasks, notes'],
                        'id'          => ['type' => 'integer', 'description' => 'Record ID'],
                    ],
                    'required' => ['entity_type', 'id'],
                ],
            ],

            // --- Company Tools (5) ---
            [
                'name' => 'list_companies',
                'description' => 'List companies/accounts with optional filtering by industry or search term.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'search'   => ['type' => 'string', 'description' => 'Filter by company name or domain'],
                        'industry' => ['type' => 'string', 'description' => 'Filter by industry sector'],
                        'limit'    => ['type' => 'integer', 'description' => 'Max number of records (default 25)'],
                    ],
                ],
            ],
            [
                'name' => 'get_company',
                'description' => 'Retrieve detailed information for a company, including associated contacts, deals, and notes.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Company ID'],
                    ],
                    'required' => ['id'],
                ],
            ],
            [
                'name' => 'create_company',
                'description' => 'Create a new company / account in the active workspace.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'name'           => ['type' => 'string', 'description' => 'Company name'],
                        'domain'         => ['type' => 'string', 'description' => 'Domain name (e.g. acme.com)'],
                        'industry'       => ['type' => 'string', 'description' => 'Industry category'],
                        'annual_revenue' => ['type' => 'number', 'description' => 'Annual revenue in USD'],
                        'phone'          => ['type' => 'string', 'description' => 'Phone number'],
                        'email'          => ['type' => 'string', 'description' => 'Contact email'],
                        'website'        => ['type' => 'string', 'description' => 'Website URL'],
                        'address'        => ['type' => 'string', 'description' => 'Street address'],
                        'city'           => ['type' => 'string', 'description' => 'City name'],
                        'state'          => ['type' => 'string', 'description' => 'State / province / region'],
                        'postal_code'    => ['type' => 'string', 'description' => 'Postal or ZIP code'],
                        'country'        => ['type' => 'string', 'description' => 'Country name'],
                        'description'    => ['type' => 'string', 'description' => 'Company background and notes'],
                    ],
                    'required' => ['name'],
                ],
            ],
            [
                'name' => 'update_company',
                'description' => 'Update an existing company by ID.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id'             => ['type' => 'integer', 'description' => 'Company ID'],
                        'name'           => ['type' => 'string'],
                        'domain'         => ['type' => 'string'],
                        'industry'       => ['type' => 'string'],
                        'annual_revenue' => ['type' => 'number'],
                        'phone'          => ['type' => 'string'],
                        'email'          => ['type' => 'string'],
                        'website'        => ['type' => 'string'],
                        'address'        => ['type' => 'string'],
                        'city'           => ['type' => 'string'],
                        'state'          => ['type' => 'string'],
                        'postal_code'    => ['type' => 'string'],
                        'country'        => ['type' => 'string'],
                        'description'    => ['type' => 'string'],
                    ],
                    'required' => ['id'],
                ],
            ],
            [
                'name' => 'delete_company',
                'description' => 'Delete a company by ID.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Company ID to remove'],
                    ],
                    'required' => ['id'],
                ],
            ],

            // --- People / Contact Tools (5) ---
            [
                'name' => 'list_people',
                'description' => 'List contacts/leads in the workspace with optional filters.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer', 'description' => 'Filter by company ID'],
                        'status'     => ['type' => 'string', 'description' => 'lead, customer, inactive'],
                        'search'     => ['type' => 'string', 'description' => 'Search by name or email'],
                        'limit'      => ['type' => 'integer'],
                    ],
                ],
            ],
            [
                'name' => 'get_person',
                'description' => 'Retrieve complete contact information, associated company, and deals.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Contact Person ID'],
                    ],
                    'required' => ['id'],
                ],
            ],
            [
                'name' => 'create_person',
                'description' => 'Create a new contact / person in the workspace.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'first_name' => ['type' => 'string', 'description' => 'First name'],
                        'last_name'  => ['type' => 'string', 'description' => 'Last name'],
                        'email'      => ['type' => 'string', 'description' => 'Email address'],
                        'phone'      => ['type' => 'string', 'description' => 'Phone number'],
                        'job_title'  => ['type' => 'string', 'description' => 'Job title or role'],
                        'address'    => ['type' => 'string', 'description' => 'Street address'],
                        'city'       => ['type' => 'string', 'description' => 'City name'],
                        'state'      => ['type' => 'string', 'description' => 'State / region / province'],
                        'postal_code'=> ['type' => 'string', 'description' => 'Postal or ZIP code'],
                        'country'    => ['type' => 'string', 'description' => 'Country name'],
                        'company_id' => ['type' => 'integer', 'description' => 'Associated company ID'],
                        'status'     => ['type' => 'string', 'description' => 'Status (lead, customer, inactive)'],
                    ],
                    'required' => ['first_name'],
                ],
            ],
            [
                'name' => 'update_person',
                'description' => 'Update contact information by ID.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id'         => ['type' => 'integer', 'description' => 'Person ID'],
                        'first_name' => ['type' => 'string'],
                        'last_name'  => ['type' => 'string'],
                        'email'      => ['type' => 'string'],
                        'phone'      => ['type' => 'string'],
                        'job_title'  => ['type' => 'string'],
                        'address'    => ['type' => 'string'],
                        'city'       => ['type' => 'string'],
                        'state'      => ['type' => 'string'],
                        'postal_code'=> ['type' => 'string'],
                        'country'    => ['type' => 'string'],
                        'status'     => ['type' => 'string'],
                        'company_id' => ['type' => 'integer'],
                    ],
                    'required' => ['id'],
                ],
            ],
            [
                'name' => 'delete_person',
                'description' => 'Delete a contact person by ID.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Person ID to delete'],
                    ],
                    'required' => ['id'],
                ],
            ],

            // --- Opportunity / Deal Tools (6) ---
            [
                'name' => 'list_opportunities',
                'description' => 'List deals / pipeline opportunities with stages and monetary values.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'stage'      => ['type' => 'string', 'description' => 'Filter by stage (lead_in, qualified, proposal, negotiation, closed_won, closed_lost)'],
                        'company_id' => ['type' => 'integer'],
                        'person_id'  => ['type' => 'integer'],
                        'limit'      => ['type' => 'integer'],
                    ],
                ],
            ],
            [
                'name' => 'get_opportunity',
                'description' => 'Retrieve detailed opportunity data, financials, win probability, and relations.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Opportunity ID'],
                    ],
                    'required' => ['id'],
                ],
            ],
            [
                'name' => 'create_opportunity',
                'description' => 'Create a new deal opportunity in the pipeline.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'name'                => ['type' => 'string', 'description' => 'Deal name'],
                        'amount'              => ['type' => 'number', 'description' => 'Monetary value'],
                        'currency'            => ['type' => 'string', 'description' => 'Currency code (default USD)'],
                        'stage'               => ['type' => 'string', 'description' => 'Stage (lead_in, qualified, proposal, negotiation, closed_won, closed_lost)'],
                        'probability'         => ['type' => 'integer', 'description' => 'Win probability percentage (0-100)'],
                        'company_id'          => ['type' => 'integer'],
                        'person_id'           => ['type' => 'integer'],
                        'expected_close_date' => ['type' => 'string', 'description' => 'Target date (YYYY-MM-DD)'],
                    ],
                    'required' => ['name', 'amount'],
                ],
            ],
            [
                'name' => 'update_opportunity',
                'description' => 'Update opportunity details or transition between pipeline stages.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id'                  => ['type' => 'integer', 'description' => 'Opportunity ID'],
                        'name'                => ['type' => 'string'],
                        'amount'              => ['type' => 'number'],
                        'currency'            => ['type' => 'string'],
                        'stage'               => ['type' => 'string'],
                        'probability'         => ['type' => 'integer'],
                        'expected_close_date' => ['type' => 'string'],
                        'status'              => ['type' => 'string'],
                    ],
                    'required' => ['id'],
                ],
            ],
            [
                'name' => 'delete_opportunity',
                'description' => 'Delete an opportunity from the pipeline.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Opportunity ID to delete'],
                    ],
                    'required' => ['id'],
                ],
            ],
            [
                'name' => 'aggregate_opportunities',
                'description' => 'Compute aggregate deal metrics and pipeline values grouped by stage, currency, or status.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'group_by' => ['type' => 'string', 'description' => 'Group aggregate by: stage (default), status, or currency'],
                    ],
                ],
            ],

            // --- Task Tools (5) ---
            [
                'name' => 'list_tasks',
                'description' => 'List tasks and reminders with optional status and priority filters.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'status'      => ['type' => 'string', 'description' => 'pending, in_progress, completed, cancelled'],
                        'priority'    => ['type' => 'string', 'description' => 'low, medium, high, urgent'],
                        'entity_type' => ['type' => 'string', 'description' => 'companies, people, opportunities'],
                        'entity_id'   => ['type' => 'integer'],
                    ],
                ],
            ],
            [
                'name' => 'get_task',
                'description' => 'Retrieve a single task details.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Task ID'],
                    ],
                    'required' => ['id'],
                ],
            ],
            [
                'name' => 'create_task',
                'description' => 'Create a task or action item.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'title'       => ['type' => 'string', 'description' => 'Task title'],
                        'description' => ['type' => 'string'],
                        'due_date'    => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                        'priority'    => ['type' => 'string', 'description' => 'low, medium, high, urgent'],
                        'entity_type' => ['type' => 'string', 'description' => 'companies, people, opportunities'],
                        'entity_id'   => ['type' => 'integer'],
                    ],
                    'required' => ['title'],
                ],
            ],
            [
                'name' => 'update_task',
                'description' => 'Update an existing task status, priority, or content.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id'          => ['type' => 'integer', 'description' => 'Task ID'],
                        'title'       => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'due_date'    => ['type' => 'string'],
                        'priority'    => ['type' => 'string'],
                        'status'      => ['type' => 'string'],
                    ],
                    'required' => ['id'],
                ],
            ],
            [
                'name' => 'delete_task',
                'description' => 'Delete a task by ID.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Task ID to delete'],
                    ],
                    'required' => ['id'],
                ],
            ],

            // --- Note Tools (5) ---
            [
                'name' => 'list_notes',
                'description' => 'List notes attached to an entity.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'entity_type' => ['type' => 'string', 'description' => 'companies, people, opportunities'],
                        'entity_id'   => ['type' => 'integer'],
                    ],
                    'required' => ['entity_type', 'entity_id'],
                ],
            ],
            [
                'name' => 'get_note',
                'description' => 'Retrieve a note by ID.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Note ID'],
                    ],
                    'required' => ['id'],
                ],
            ],
            [
                'name' => 'create_note',
                'description' => 'Add a note or activity log to an entity.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'entity_type' => ['type' => 'string', 'description' => 'companies, people, opportunities'],
                        'entity_id'   => ['type' => 'integer'],
                        'title'       => ['type' => 'string'],
                        'body'        => ['type' => 'string', 'description' => 'Note content'],
                    ],
                    'required' => ['entity_type', 'entity_id', 'body'],
                ],
            ],
            [
                'name' => 'update_note',
                'description' => 'Update note subject or body.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id'    => ['type' => 'integer', 'description' => 'Note ID'],
                        'title' => ['type' => 'string'],
                        'body'  => ['type' => 'string'],
                    ],
                    'required' => ['id'],
                ],
            ],
            [
                'name' => 'delete_note',
                'description' => 'Delete a note by ID.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Note ID to delete'],
                    ],
                    'required' => ['id'],
                ],
            ],

            // --- Relationship Graph Tools (4) ---
            [
                'name' => 'attach_record',
                'description' => 'Associate two records in the CRM graph (e.g. link contact to company, or deal to contact/company).',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'parent_type' => ['type' => 'string', 'description' => 'companies, people'],
                        'parent_id'   => ['type' => 'integer'],
                        'child_type'  => ['type' => 'string', 'description' => 'people, opportunities, tasks, notes'],
                        'child_id'    => ['type' => 'integer'],
                    ],
                    'required' => ['parent_type', 'parent_id', 'child_type', 'child_id'],
                ],
            ],
            [
                'name' => 'detach_record',
                'description' => 'Dissociate two records in the CRM graph.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'child_type' => ['type' => 'string', 'description' => 'people, opportunities, tasks, notes'],
                        'child_id'   => ['type' => 'integer'],
                    ],
                    'required' => ['child_type', 'child_id'],
                ],
            ],
            [
                'name' => 'list_relationships',
                'description' => 'Retrieve all records associated with a specific entity.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'entity_type' => ['type' => 'string', 'description' => 'companies, people, opportunities'],
                        'entity_id'   => ['type' => 'integer'],
                    ],
                    'required' => ['entity_type', 'entity_id'],
                ],
            ],
            [
                'name' => 'get_relationship_schema',
                'description' => 'Describes all allowed relationship pairs and foreign key bindings in CRX.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => new \stdClass(),
                ],
            ],

            // --- Audit & Custom Field Discovery (2) ---
            [
                'name' => 'list_activity',
                'description' => 'Query workspace audit trail and activity log entries.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'entity_type' => ['type' => 'string', 'description' => 'Optional filter by entity type'],
                        'entity_id'   => ['type' => 'integer', 'description' => 'Optional filter by entity ID'],
                        'limit'       => ['type' => 'integer', 'description' => 'Max records (default 25)'],
                    ],
                ],
            ],
            [
                'name' => 'list_custom_fields',
                'description' => 'List custom field schemas configured for entities in the workspace.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'entity_type' => ['type' => 'string', 'description' => 'Filter by entity: companies, people, opportunities'],
                    ],
                ],
            ],

            // --- Pro CRM Suite Tools (6) ---
            [
                'name' => 'log_interaction',
                'description' => 'Log an omnichannel interaction (call, meeting, email, note) with outcome, duration, and details.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'entity_type'      => ['type' => 'string', 'description' => 'companies, people, opportunities'],
                        'entity_id'        => ['type' => 'integer', 'description' => 'Target entity record ID'],
                        'type'             => ['type' => 'string', 'description' => 'call, meeting, email, note'],
                        'title'            => ['type' => 'string', 'description' => 'Subject or title'],
                        'description'      => ['type' => 'string', 'description' => 'Interaction summary or agenda'],
                        'outcome'          => ['type' => 'string', 'description' => 'connected, voicemail, busy, wrong_number (for calls)'],
                        'duration_minutes' => ['type' => 'integer', 'description' => 'Duration in minutes (optional)'],
                        'scheduled_at'     => ['type' => 'string', 'description' => 'YYYY-MM-DD HH:MM:SS (for meetings)'],
                    ],
                    'required' => ['entity_type', 'entity_id', 'type', 'description'],
                ],
            ],
            [
                'name' => 'get_activity_timeline',
                'description' => 'Retrieve reverse-chronological omnichannel interaction timeline for a specific entity.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'entity_type' => ['type' => 'string', 'description' => 'companies, people, opportunities'],
                        'entity_id'   => ['type' => 'integer', 'description' => 'Entity ID'],
                        'limit'       => ['type' => 'integer', 'description' => 'Max records (default 25)'],
                    ],
                    'required' => ['entity_type', 'entity_id'],
                ],
            ],
            [
                'name' => 'get_deal_rotting_alerts',
                'description' => 'Detect and return deals in the pipeline that have remained stagnant past their stage rotting thresholds.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'pipeline_id' => ['type' => 'integer', 'description' => 'Optional pipeline filter'],
                    ],
                ],
            ],
            [
                'name' => 'get_sales_funnel_analytics',
                'description' => 'Calculate sales funnel conversion rates, stage velocity, win/loss rates, and weighted revenue forecast.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'pipeline_id' => ['type' => 'integer', 'description' => 'Optional pipeline filter'],
                    ],
                ],
            ],
            [
                'name' => 'list_recycle_bin',
                'description' => 'List all soft-deleted records currently sitting in the workspace Recycle Bin.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'entity_type' => ['type' => 'string', 'description' => 'Optional filter: companies, people, opportunities, tasks'],
                    ],
                ],
            ],
            [
                'name' => 'restore_from_recycle_bin',
                'description' => 'Restore a soft-deleted record from the Recycle Bin back to active status.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'entity_type' => ['type' => 'string', 'description' => 'companies, people, opportunities, tasks'],
                        'id'          => ['type' => 'integer', 'description' => 'Record ID to restore'],
                    ],
                    'required' => ['entity_type', 'id'],
                ],
            ],

            // --- AI Manager & Executive Co-Pilot Tools (9) ---
            [
                'name' => 'ai_manager_briefing',
                'description' => 'Autonomous morning executive briefing for the AI Manager. Generates KPIs, pipeline health, rotting high-value deals, urgent tasks, quote pipeline status, and immediate strategic recommendations.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => new \stdClass(),
                ],
            ],
            [
                'name' => 'ai_lead_score',
                'description' => 'Calculates predictive AI Lead Score (0-100) and letter grade (A+ to D) for a contact based on email engagement, interactions, company verification, and deal values.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'person_id' => ['type' => 'integer', 'description' => 'Contact ID to score'],
                    ],
                    'required' => ['person_id'],
                ],
            ],
            [
                'name' => 'ai_recommend_next_action',
                'description' => 'Prescribes the optimal Next-Best-Action for an opportunity, lead, or account based on activity staleness, missing proposals, or uncompleted tasks.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'entity_type' => ['type' => 'string', 'description' => 'opportunities or people'],
                        'id'          => ['type' => 'integer', 'description' => 'Record ID'],
                    ],
                    'required' => ['entity_type', 'id'],
                ],
            ],
            [
                'name' => 'ai_create_quote',
                'description' => 'AI CPQ proposal generator. Builds a formal quote with line items, tax, discounts, and generates a client acceptance URL for instant digital sign-off.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'title'           => ['type' => 'string', 'description' => 'Proposal title / scope'],
                        'items'           => [
                            'type' => 'array',
                            'description' => 'List of items: [{"description":"Item name", "quantity":1, "unit_price":100}]',
                            'items' => ['type' => 'object'],
                        ],
                        'opportunity_id'  => ['type' => 'integer', 'description' => 'Optional linked deal ID'],
                        'person_id'       => ['type' => 'integer', 'description' => 'Optional linked contact ID'],
                        'company_id'      => ['type' => 'integer', 'description' => 'Optional linked company ID'],
                        'tax_percent'     => ['type' => 'number', 'description' => 'Tax percentage (e.g. 15.0)'],
                        'discount_amount' => ['type' => 'number', 'description' => 'Flat discount amount'],
                        'currency'        => ['type' => 'string', 'description' => 'Currency code (default USD)'],
                        'notes'           => ['type' => 'string', 'description' => 'Terms / notes for the client'],
                        'valid_days'      => ['type' => 'integer', 'description' => 'Validity period in days (default 30)'],
                    ],
                    'required' => ['title', 'items'],
                ],
            ],
            [
                'name' => 'ai_send_tracked_email',
                'description' => 'AI outbound email dispatcher. Sends personalized client emails with invisible open-pixel tracking and link click redirection automatically configured.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'to'          => ['type' => 'string', 'description' => 'Recipient email address'],
                        'subject'     => ['type' => 'string', 'description' => 'Email subject line'],
                        'html_body'   => ['type' => 'string', 'description' => 'Rich HTML body content'],
                        'person_id'   => ['type' => 'integer', 'description' => 'Optional contact ID to link'],
                        'entity_type' => ['type' => 'string', 'description' => 'people, companies, or opportunities'],
                        'entity_id'   => ['type' => 'integer', 'description' => 'Associated record ID'],
                    ],
                    'required' => ['to', 'subject', 'html_body'],
                ],
            ],
            [
                'name' => 'ai_get_booking_link',
                'description' => 'Fetches the live public meeting scheduler link and booking parameters for the user or workspace.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'user_id' => ['type' => 'integer', 'description' => 'Optional specific user ID'],
                    ],
                ],
            ],
            [
                'name' => 'ai_detect_duplicates',
                'description' => 'Scans contacts (by email/phone) or companies (by name/domain) for duplicate clusters.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'entity_type' => ['type' => 'string', 'description' => 'people or companies'],
                    ],
                    'required' => ['entity_type'],
                ],
            ],
            [
                'name' => 'ai_merge_records',
                'description' => 'Safely merges a duplicate record into a primary record, re-linking all deals, tasks, notes, interactions, and quotes before soft-deleting the duplicate.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'entity_type'  => ['type' => 'string', 'description' => 'people or companies'],
                        'primary_id'   => ['type' => 'integer', 'description' => 'Target surviving record ID'],
                        'duplicate_id' => ['type' => 'integer', 'description' => 'Duplicate record ID to merge and soft-delete'],
                    ],
                    'required' => ['entity_type', 'primary_id', 'duplicate_id'],
                ],
            ],
            [
                'name' => 'ai_trigger_automation',
                'description' => 'Manually fires an automated workflow event (e.g. lead.created, deal.won, quote.accepted) through the CRX automation engine.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'event'   => ['type' => 'string', 'description' => 'Event name (e.g. lead.created, deal.stage_changed)'],
                        'payload' => ['type' => 'object', 'description' => 'Payload dictionary containing entity_type, entity_id, etc.'],
                    ],
                    'required' => ['event'],
                ],
            ],
            [
                'name' => 'ai_segment_customers',
                'description' => 'Multi-parameter customer grouping and targeting engine. Filters and groups accounts/contacts across geography (country, state, city), firmographics (industry, size, revenue), status, and activity without separate tables.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'entity_type'    => ['type' => 'string', 'description' => 'people or companies (default people)'],
                        'country'        => ['type' => 'string', 'description' => 'Filter by country'],
                        'state'          => ['type' => 'string', 'description' => 'Filter by state / region'],
                        'city'           => ['type' => 'string', 'description' => 'Filter by city'],
                        'postal_code'    => ['type' => 'string', 'description' => 'Filter by ZIP / postal code'],
                        'industry'       => ['type' => 'string', 'description' => 'Filter by industry sector'],
                        'status'         => ['type' => 'string', 'description' => 'lead, contact, customer, churned'],
                        'job_title'      => ['type' => 'string', 'description' => 'Filter by title (e.g. VP, Director, CTO)'],
                        'size'           => ['type' => 'string', 'description' => 'Company size: 1-10, 11-50, 51-200, 201-500, 500+'],
                        'min_revenue'    => ['type' => 'number', 'description' => 'Minimum annual revenue in USD'],
                        'has_deals'      => ['type' => 'boolean', 'description' => 'Filter by presence of deals in pipeline'],
                        'has_open_tasks' => ['type' => 'boolean', 'description' => 'Filter by presence of open/pending tasks'],
                        'tag'            => ['type' => 'string', 'description' => 'Filter by tag name (e.g. VIP, Enterprise, High-Intent)'],
                        'group_by'       => ['type' => 'string', 'description' => 'Grouping field: tags, country, state, city, industry, status, size, company_name'],
                        'limit'          => ['type' => 'integer', 'description' => 'Max records to return (default 50)'],
                    ],
                ],
            ],
            [
                'name' => 'ai_bulk_email_group',
                'description' => 'Dispatches personalized, tracked email campaigns to all contacts matching a target segment (e.g. tag, country, state, industry, status). Replaces {first_name}, {name} tags.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'subject'          => ['type' => 'string', 'description' => 'Campaign email subject line'],
                        'html_body'        => ['type' => 'string', 'description' => 'Email body supporting variables: {first_name}, {name}'],
                        'segment_criteria' => [
                            'type'        => 'object',
                            'description' => 'Filters such as {"tag":"VIP Client"} or {"state":"Texas"} or {"status":"lead"}',
                        ],
                        'max_recipients'   => ['type' => 'integer', 'description' => 'Max number of contacts to email (default 100)'],
                    ],
                    'required' => ['subject', 'html_body'],
                ],
            ],
        ];
    }

    /**
     * Execute any of the 37 MCP tools.
     */
    public function execute(string $toolName, array $arguments, int $workspaceId, ?int $userId): array
    {
        switch ($toolName) {
            // --- System & Discovery ---
            case 'whoami':
                $user = $userId ? (new User)->table()->find($userId) : null;
                $ws   = (new Workspace)->table()->find($workspaceId);
                return [
                    'user' => $user ? ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email']] : null,
                    'workspace' => $ws ? ['id' => $ws['id'], 'name' => $ws['name'], 'slug' => $ws['slug']] : null,
                ];

            case 'get_crm_summary':
            case 'get_pipeline_summary':
                $companyCount = (new Company)->table()->where('workspace_id', $workspaceId)->count();
                $peopleCount  = (new Person)->table()->where('workspace_id', $workspaceId)->count();
                $dealCount    = (new Opportunity)->table()->where('workspace_id', $workspaceId)->count();
                $pendingTasks = (new Task)->table()->where('workspace_id', $workspaceId)->where('status', 'completed', '!=')->count();

                $deals = (new Opportunity)->table()->where('workspace_id', $workspaceId)->get();
                $pipelineValue = 0;
                $stageBreakdown = [];
                foreach ($deals as $d) {
                    $val = (float)$d['amount'];
                    $pipelineValue += $val;
                    $st = (string)$d['stage'];
                    $stageBreakdown[$st] = ($stageBreakdown[$st] ?? 0) + $val;
                }

                return [
                    'metrics' => [
                        'total_companies'   => $companyCount,
                        'total_people'      => $peopleCount,
                        'total_deals'       => $dealCount,
                        'total_pipeline'    => $pipelineValue,
                        'pending_tasks'     => $pendingTasks,
                    ],
                    'pipeline_by_stage' => $stageBreakdown,
                ];

            case 'get_crm_schema':
                $customFields = (new CustomField)->table()->where('workspace_id', $workspaceId)->get();
                return [
                    'stages' => Opportunity::STAGES,
                    'entities' => ['companies', 'people', 'opportunities', 'tasks', 'notes'],
                    'custom_fields' => $customFields,
                ];

            case 'search':
                $q = '%' . ($arguments['query'] ?? '') . '%';
                $companies = (new Company)->table()
                    ->where('workspace_id', $workspaceId)
                    ->where('name', $q, 'LIKE')
                    ->limit(5)->get();
                $people = (new Person)->table()
                    ->where('workspace_id', $workspaceId)
                    ->where('first_name', $q, 'LIKE')
                    ->limit(5)->get();
                $deals = (new Opportunity)->table()
                    ->where('workspace_id', $workspaceId)
                    ->where('name', $q, 'LIKE')
                    ->limit(5)->get();

                return [
                    'companies'     => $companies,
                    'people'        => $people,
                    'opportunities' => $deals,
                ];

            case 'fetch':
                $type = (string)($arguments['entity_type'] ?? '');
                $id = (int)($arguments['id'] ?? 0);
                $model = match($type) {
                    'companies'     => new Company,
                    'people'        => new Person,
                    'opportunities' => new Opportunity,
                    'tasks'         => new Task,
                    'notes'         => new Note,
                    default         => null,
                };
                if (!$model || !$id) {
                    return ['error' => 'Invalid entity type or ID'];
                }
                $record = $model->table()->where('id', $id)->where('workspace_id', $workspaceId)->first();
                return $record ? ['found' => true, 'entity_type' => $type, 'record' => $record] : ['found' => false];

            // --- Companies ---
            case 'list_companies':
                $limit = min((int)($arguments['limit'] ?? 25), 100);
                $query = (new Company)->table()->where('workspace_id', $workspaceId);
                if (!empty($arguments['search'])) {
                    $s = '%' . $arguments['search'] . '%';
                    $query->where('name', $s, 'LIKE');
                }
                if (!empty($arguments['industry'])) {
                    $query->where('industry', $arguments['industry']);
                }
                return $query->limit($limit)->get();

            case 'get_company':
                $id = (int)($arguments['id'] ?? 0);
                $company = (new Company)->table()->where('id', $id)->where('workspace_id', $workspaceId)->first();
                if (!$company) return ['error' => 'Company not found'];

                $people = (new Person)->table()->where('company_id', $id)->where('workspace_id', $workspaceId)->get();
                $deals  = (new Opportunity)->table()->where('company_id', $id)->where('workspace_id', $workspaceId)->get();
                $notes  = (new Note)->table()->where('entity_type', 'companies')->where('entity_id', $id)->get();

                return [
                    'company'       => $company,
                    'people'        => $people,
                    'opportunities' => $deals,
                    'notes'         => $notes,
                ];

            case 'create_company':
                $insertData = [
                    'workspace_id'    => $workspaceId,
                    'name'            => $arguments['name'],
                    'domain'          => $arguments['domain'] ?? null,
                    'industry'        => $arguments['industry'] ?? null,
                    'annual_revenue'  => $arguments['annual_revenue'] ?? null,
                    'phone'           => $arguments['phone'] ?? null,
                    'email'           => $arguments['email'] ?? null,
                    'website'         => $arguments['website'] ?? null,
                    'address'         => $arguments['address'] ?? null,
                    'city'            => $arguments['city'] ?? null,
                    'state'           => $arguments['state'] ?? null,
                    'postal_code'     => $arguments['postal_code'] ?? null,
                    'country'         => $arguments['country'] ?? null,
                    'description'     => $arguments['description'] ?? null,
                    'assigned_user_id'=> $userId,
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ];
                $id = (new Company)->table()->insert($insertData);
                ActivityLogger::log($workspaceId, $userId, 'created', 'companies', (int)$id, "Created company via MCP: {$arguments['name']}");
                return ['success' => true, 'id' => (int)$id];

            case 'update_company':
                $id = (int)($arguments['id'] ?? 0);
                $allowed = ['name', 'domain', 'industry', 'annual_revenue', 'phone', 'email', 'website', 'address', 'city', 'state', 'postal_code', 'country', 'description'];
                $update = ['updated_at' => date('Y-m-d H:i:s')];
                foreach ($allowed as $f) {
                    if (array_key_exists($f, $arguments)) $update[$f] = $arguments[$f];
                }
                (new Company)->table()->where('id', $id)->where('workspace_id', $workspaceId)->update($update);
                ActivityLogger::log($workspaceId, $userId, 'updated', 'companies', $id, "Updated company via MCP #{$id}");
                return ['success' => true, 'id' => $id];

            case 'delete_company':
                $id = (int)($arguments['id'] ?? 0);
                (new Company)->table()->where('id', $id)->where('workspace_id', $workspaceId)->delete();
                ActivityLogger::log($workspaceId, $userId, 'deleted', 'companies', $id, "Deleted company via MCP #{$id}");
                return ['success' => true, 'id' => $id];

            // --- People ---
            case 'list_people':
                $limit = min((int)($arguments['limit'] ?? 25), 100);
                $query = (new Person)->table()->where('workspace_id', $workspaceId);
                if (!empty($arguments['company_id'])) {
                    $query->where('company_id', (int)$arguments['company_id']);
                }
                if (!empty($arguments['status'])) {
                    $query->where('status', $arguments['status']);
                }
                if (!empty($arguments['search'])) {
                    $s = '%' . $arguments['search'] . '%';
                    $query->where('first_name', $s, 'LIKE');
                }
                return $query->limit($limit)->get();

            case 'get_person':
                $id = (int)($arguments['id'] ?? 0);
                $person = (new Person)->table()->where('id', $id)->where('workspace_id', $workspaceId)->first();
                if (!$person) return ['error' => 'Contact person not found'];

                $company = !empty($person['company_id']) ? (new Company)->table()->find($person['company_id']) : null;
                $deals   = (new Opportunity)->table()->where('person_id', $id)->where('workspace_id', $workspaceId)->get();
                $notes   = (new Note)->table()->where('entity_type', 'people')->where('entity_id', $id)->get();

                return [
                    'person'        => $person,
                    'company'       => $company,
                    'opportunities' => $deals,
                    'notes'         => $notes,
                ];

            case 'create_person':
                $insertData = [
                    'workspace_id'    => $workspaceId,
                    'company_id'      => !empty($arguments['company_id']) ? (int)$arguments['company_id'] : null,
                    'first_name'      => $arguments['first_name'],
                    'last_name'       => $arguments['last_name'] ?? null,
                    'email'           => $arguments['email'] ?? null,
                    'phone'           => $arguments['phone'] ?? null,
                    'job_title'       => $arguments['job_title'] ?? null,
                    'address'         => $arguments['address'] ?? null,
                    'city'            => $arguments['city'] ?? null,
                    'state'           => $arguments['state'] ?? null,
                    'postal_code'     => $arguments['postal_code'] ?? null,
                    'country'         => $arguments['country'] ?? null,
                    'status'          => $arguments['status'] ?? 'lead',
                    'assigned_user_id'=> $userId,
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ];
                $id = (new Person)->table()->insert($insertData);
                ActivityLogger::log($workspaceId, $userId, 'created', 'people', (int)$id, "Created contact via MCP: {$arguments['first_name']}");
                return ['success' => true, 'id' => (int)$id];

            case 'update_person':
                $id = (int)($arguments['id'] ?? 0);
                $allowed = ['first_name', 'last_name', 'email', 'phone', 'job_title', 'address', 'city', 'state', 'postal_code', 'country', 'status', 'company_id'];
                $update = ['updated_at' => date('Y-m-d H:i:s')];
                foreach ($allowed as $f) {
                    if (array_key_exists($f, $arguments)) $update[$f] = $arguments[$f];
                }
                (new Person)->table()->where('id', $id)->where('workspace_id', $workspaceId)->update($update);
                ActivityLogger::log($workspaceId, $userId, 'updated', 'people', $id, "Updated contact via MCP #{$id}");
                return ['success' => true, 'id' => $id];

            case 'delete_person':
                $id = (int)($arguments['id'] ?? 0);
                (new Person)->table()->where('id', $id)->where('workspace_id', $workspaceId)->delete();
                ActivityLogger::log($workspaceId, $userId, 'deleted', 'people', $id, "Deleted contact via MCP #{$id}");
                return ['success' => true, 'id' => $id];

            // --- Opportunities ---
            case 'list_opportunities':
                $limit = min((int)($arguments['limit'] ?? 25), 100);
                $query = (new Opportunity)->table()->where('workspace_id', $workspaceId);
                if (!empty($arguments['stage'])) {
                    $query->where('stage', $arguments['stage']);
                }
                if (!empty($arguments['company_id'])) {
                    $query->where('company_id', (int)$arguments['company_id']);
                }
                if (!empty($arguments['person_id'])) {
                    $query->where('person_id', (int)$arguments['person_id']);
                }
                return $query->limit($limit)->get();

            case 'get_opportunity':
                $id = (int)($arguments['id'] ?? 0);
                $deal = (new Opportunity)->table()->where('id', $id)->where('workspace_id', $workspaceId)->first();
                if (!$deal) return ['error' => 'Opportunity not found'];

                $company = !empty($deal['company_id']) ? (new Company)->table()->find($deal['company_id']) : null;
                $person  = !empty($deal['person_id']) ? (new Person)->table()->find($deal['person_id']) : null;
                $notes   = (new Note)->table()->where('entity_type', 'opportunities')->where('entity_id', $id)->get();

                return [
                    'opportunity' => $deal,
                    'company'     => $company,
                    'person'      => $person,
                    'notes'       => $notes,
                ];

            case 'create_opportunity':
                $insertData = [
                    'workspace_id'        => $workspaceId,
                    'company_id'          => !empty($arguments['company_id']) ? (int)$arguments['company_id'] : null,
                    'person_id'           => !empty($arguments['person_id']) ? (int)$arguments['person_id'] : null,
                    'name'                => $arguments['name'],
                    'amount'              => (float)($arguments['amount'] ?? 0),
                    'currency'            => $arguments['currency'] ?? 'USD',
                    'stage'               => $arguments['stage'] ?? Opportunity::STAGE_LEAD,
                    'probability'         => !empty($arguments['probability']) ? (int)$arguments['probability'] : 20,
                    'expected_close_date' => $arguments['expected_close_date'] ?? null,
                    'status'              => 'open',
                    'assigned_user_id'    => $userId,
                    'created_at'          => date('Y-m-d H:i:s'),
                    'updated_at'          => date('Y-m-d H:i:s'),
                ];
                $id = (new Opportunity)->table()->insert($insertData);
                ActivityLogger::log($workspaceId, $userId, 'created', 'opportunities', (int)$id, "Created deal via MCP: {$arguments['name']}");
                return ['success' => true, 'id' => (int)$id];

            case 'update_opportunity':
                $id = (int)($arguments['id'] ?? 0);
                $allowed = ['name', 'amount', 'currency', 'stage', 'probability', 'expected_close_date', 'status'];
                $update = ['updated_at' => date('Y-m-d H:i:s')];
                foreach ($allowed as $f) {
                    if (array_key_exists($f, $arguments)) $update[$f] = $arguments[$f];
                }
                (new Opportunity)->table()->where('id', $id)->where('workspace_id', $workspaceId)->update($update);
                ActivityLogger::log($workspaceId, $userId, 'updated', 'opportunities', $id, "Updated deal via MCP #{$id}");
                return ['success' => true, 'id' => $id];

            case 'delete_opportunity':
                $id = (int)($arguments['id'] ?? 0);
                (new Opportunity)->table()->where('id', $id)->where('workspace_id', $workspaceId)->delete();
                ActivityLogger::log($workspaceId, $userId, 'deleted', 'opportunities', $id, "Deleted deal via MCP #{$id}");
                return ['success' => true, 'id' => $id];

            case 'aggregate_opportunities':
                $deals = (new Opportunity)->table()->where('workspace_id', $workspaceId)->get();
                $groupBy = $arguments['group_by'] ?? 'stage';
                $aggregated = [];
                $totalRevenue = 0.0;

                foreach ($deals as $d) {
                    $key = (string)($d[$groupBy] ?? 'unknown');
                    $amt = (float)($d['amount'] ?? 0);
                    $totalRevenue += $amt;
                    if (!isset($aggregated[$key])) {
                        $aggregated[$key] = ['count' => 0, 'amount' => 0.0];
                    }
                    $aggregated[$key]['count']++;
                    $aggregated[$key]['amount'] += $amt;
                }

                return [
                    'group_by'      => $groupBy,
                    'total_deals'   => count($deals),
                    'total_revenue' => $totalRevenue,
                    'breakdown'     => $aggregated,
                ];

            // --- Tasks ---
            case 'list_tasks':
                $query = (new Task)->table()->where('workspace_id', $workspaceId);
                if (!empty($arguments['status'])) $query->where('status', $arguments['status']);
                if (!empty($arguments['priority'])) $query->where('priority', $arguments['priority']);
                if (!empty($arguments['entity_type'])) $query->where('entity_type', $arguments['entity_type']);
                if (!empty($arguments['entity_id'])) $query->where('entity_id', (int)$arguments['entity_id']);
                return $query->get();

            case 'get_task':
                $id = (int)($arguments['id'] ?? 0);
                $task = (new Task)->table()->where('id', $id)->where('workspace_id', $workspaceId)->first();
                return $task ? ['task' => $task] : ['error' => 'Task not found'];

            case 'create_task':
                $insertData = [
                    'workspace_id'    => $workspaceId,
                    'title'           => $arguments['title'],
                    'description'     => $arguments['description'] ?? null,
                    'due_date'        => $arguments['due_date'] ?? null,
                    'priority'        => $arguments['priority'] ?? 'medium',
                    'status'          => 'pending',
                    'entity_type'     => $arguments['entity_type'] ?? null,
                    'entity_id'       => !empty($arguments['entity_id']) ? (int)$arguments['entity_id'] : null,
                    'assigned_user_id'=> $userId,
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ];
                $id = (new Task)->table()->insert($insertData);
                ActivityLogger::log($workspaceId, $userId, 'created', 'tasks', (int)$id, "Created task via MCP: {$arguments['title']}");
                return ['success' => true, 'id' => (int)$id];

            case 'update_task':
                $id = (int)($arguments['id'] ?? 0);
                $allowed = ['title', 'description', 'due_date', 'priority', 'status'];
                $update = ['updated_at' => date('Y-m-d H:i:s')];
                foreach ($allowed as $f) {
                    if (array_key_exists($f, $arguments)) $update[$f] = $arguments[$f];
                }
                (new Task)->table()->where('id', $id)->where('workspace_id', $workspaceId)->update($update);
                return ['success' => true, 'id' => $id];

            case 'delete_task':
                $id = (int)($arguments['id'] ?? 0);
                (new Task)->table()->where('id', $id)->where('workspace_id', $workspaceId)->delete();
                return ['success' => true, 'id' => $id];

            // --- Notes ---
            case 'list_notes':
                return (new Note)->table()
                    ->where('workspace_id', $workspaceId)
                    ->where('entity_type', $arguments['entity_type'])
                    ->where('entity_id', (int)$arguments['entity_id'])
                    ->get();

            case 'get_note':
                $id = (int)($arguments['id'] ?? 0);
                $note = (new Note)->table()->where('id', $id)->where('workspace_id', $workspaceId)->first();
                return $note ? ['note' => $note] : ['error' => 'Note not found'];

            case 'create_note':
                $insertData = [
                    'workspace_id' => $workspaceId,
                    'entity_type'  => $arguments['entity_type'],
                    'entity_id'    => (int)$arguments['entity_id'],
                    'user_id'      => $userId,
                    'title'        => $arguments['title'] ?? null,
                    'body'         => $arguments['body'],
                    'created_at'   => date('Y-m-d H:i:s'),
                    'updated_at'   => date('Y-m-d H:i:s'),
                ];
                $id = (new Note)->table()->insert($insertData);
                ActivityLogger::log($workspaceId, $userId, 'created', 'notes', (int)$id, "Created note via MCP");
                return ['success' => true, 'id' => (int)$id];

            case 'update_note':
                $id = (int)($arguments['id'] ?? 0);
                $update = ['updated_at' => date('Y-m-d H:i:s')];
                if (isset($arguments['title'])) $update['title'] = $arguments['title'];
                if (isset($arguments['body'])) $update['body'] = $arguments['body'];
                (new Note)->table()->where('id', $id)->where('workspace_id', $workspaceId)->update($update);
                return ['success' => true, 'id' => $id];

            case 'delete_note':
                $id = (int)($arguments['id'] ?? 0);
                (new Note)->table()->where('id', $id)->where('workspace_id', $workspaceId)->delete();
                return ['success' => true, 'id' => $id];

            // --- Relationships Graph ---
            case 'attach_record':
                $pType = (string)$arguments['parent_type'];
                $pId   = (int)$arguments['parent_id'];
                $cType = (string)$arguments['child_type'];
                $cId   = (int)$arguments['child_id'];

                if ($pType === 'companies' && $cType === 'people') {
                    (new Person)->table()->where('id', $cId)->where('workspace_id', $workspaceId)->update(['company_id' => $pId]);
                } elseif ($pType === 'companies' && $cType === 'opportunities') {
                    (new Opportunity)->table()->where('id', $cId)->where('workspace_id', $workspaceId)->update(['company_id' => $pId]);
                } elseif ($pType === 'people' && $cType === 'opportunities') {
                    (new Opportunity)->table()->where('id', $cId)->where('workspace_id', $workspaceId)->update(['person_id' => $pId]);
                }
                return ['success' => true, 'attached' => true];

            case 'detach_record':
                $cType = (string)$arguments['child_type'];
                $cId   = (int)$arguments['child_id'];

                if ($cType === 'people') {
                    (new Person)->table()->where('id', $cId)->where('workspace_id', $workspaceId)->update(['company_id' => null]);
                } elseif ($cType === 'opportunities') {
                    (new Opportunity)->table()->where('id', $cId)->where('workspace_id', $workspaceId)->update(['company_id' => null, 'person_id' => null]);
                }
                return ['success' => true, 'detached' => true];

            case 'list_relationships':
                $eType = (string)$arguments['entity_type'];
                $eId   = (int)$arguments['entity_id'];
                $result = [];

                if ($eType === 'companies') {
                    $result['people']        = (new Person)->table()->where('company_id', $eId)->where('workspace_id', $workspaceId)->get();
                    $result['opportunities'] = (new Opportunity)->table()->where('company_id', $eId)->where('workspace_id', $workspaceId)->get();
                    $result['tasks']         = (new Task)->table()->where('entity_type', 'companies')->where('entity_id', $eId)->get();
                    $result['notes']         = (new Note)->table()->where('entity_type', 'companies')->where('entity_id', $eId)->get();
                } elseif ($eType === 'people') {
                    $person = (new Person)->table()->where('id', $eId)->where('workspace_id', $workspaceId)->first();
                    $result['company']       = $person && $person['company_id'] ? (new Company)->table()->find($person['company_id']) : null;
                    $result['opportunities'] = (new Opportunity)->table()->where('person_id', $eId)->where('workspace_id', $workspaceId)->get();
                    $result['tasks']         = (new Task)->table()->where('entity_type', 'people')->where('entity_id', $eId)->get();
                    $result['notes']         = (new Note)->table()->where('entity_type', 'people')->where('entity_id', $eId)->get();
                } elseif ($eType === 'opportunities') {
                    $deal = (new Opportunity)->table()->where('id', $eId)->where('workspace_id', $workspaceId)->first();
                    $result['company']       = $deal && $deal['company_id'] ? (new Company)->table()->find($deal['company_id']) : null;
                    $result['person']        = $deal && $deal['person_id'] ? (new Person)->table()->find($deal['person_id']) : null;
                    $result['tasks']         = (new Task)->table()->where('entity_type', 'opportunities')->where('entity_id', $eId)->get();
                    $result['notes']         = (new Note)->table()->where('entity_type', 'opportunities')->where('entity_id', $eId)->get();
                }
                return $result;

            case 'get_relationship_schema':
                return [
                    'relationships' => [
                        'companies -> people'        => 'One-to-many (people.company_id)',
                        'companies -> opportunities' => 'One-to-many (opportunities.company_id)',
                        'people -> opportunities'    => 'One-to-many (opportunities.person_id)',
                        '* -> tasks'                 => 'Polymorphic (tasks.entity_type, tasks.entity_id)',
                        '* -> notes'                 => 'Polymorphic (notes.entity_type, notes.entity_id)',
                    ]
                ];

            // --- Audit & Custom Fields ---
            case 'list_activity':
                $limit = min((int)($arguments['limit'] ?? 25), 100);
                $query = (new ActivityLog)->table()->where('workspace_id', $workspaceId);
                if (!empty($arguments['entity_type'])) $query->where('entity_type', $arguments['entity_type']);
                if (!empty($arguments['entity_id'])) $query->where('entity_id', (int)$arguments['entity_id']);
                return $query->orderBy('id', 'DESC')->limit($limit)->get();

            case 'list_custom_fields':
                $query = (new CustomField)->table()->where('workspace_id', $workspaceId);
                if (!empty($arguments['entity_type'])) $query->where('entity_type', $arguments['entity_type']);
                return $query->get();

            // --- Pro CRM Suite Tools ---
            case 'log_interaction':
                $eType = (string)($arguments['entity_type'] ?? '');
                $eId   = (int)($arguments['entity_id'] ?? 0);
                $type  = (string)($arguments['type'] ?? 'note');
                $desc  = (string)($arguments['description'] ?? '');
                if (!$eType || !$eId || !$desc) {
                    return ['error' => 'entity_type, entity_id, and description are required.'];
                }
                $actId = (new Interaction)->table()->insert([
                    'workspace_id'     => $workspaceId,
                    'user_id'          => $userId,
                    'entity_type'      => $eType,
                    'entity_id'        => $eId,
                    'type'             => $type,
                    'title'            => $arguments['title'] ?? null,
                    'description'      => $desc,
                    'outcome'          => $arguments['outcome'] ?? null,
                    'duration_minutes' => !empty($arguments['duration_minutes']) ? (int)$arguments['duration_minutes'] : null,
                    'scheduled_at'     => $arguments['scheduled_at'] ?? null,
                    'created_at'       => date('Y-m-d H:i:s'),
                ]);
                return ['success' => true, 'interaction_id' => $actId, 'message' => "Logged {$type} interaction."];

            case 'get_activity_timeline':
                $eType = (string)($arguments['entity_type'] ?? '');
                $eId   = (int)($arguments['entity_id'] ?? 0);
                $limit = min((int)($arguments['limit'] ?? 25), 100);
                $acts = (new Interaction)->table()
                    ->where('workspace_id', $workspaceId)
                    ->where('entity_type', $eType)
                    ->where('entity_id', $eId)
                    ->orderBy('id', 'DESC')
                    ->limit($limit)
                    ->get();
                return ['timeline' => $acts, 'count' => count($acts)];

            case 'get_deal_rotting_alerts':
                $pipeId = !empty($arguments['pipeline_id']) ? (int)$arguments['pipeline_id'] : null;
                $pQuery = (new Pipeline)->table()->where('workspace_id', $workspaceId);
                if ($pipeId) $pQuery->where('id', $pipeId);
                $pipeline = $pQuery->first();
                if (!$pipeline) {
                    $pipeline = (new Pipeline)->table()->where('workspace_id', $workspaceId)->first();
                }
                $stages = $pipeline ? (new PipelineStage)->table()->where('pipeline_id', $pipeline['id'])->get() : [];
                $stageRotMap = [];
                foreach ($stages as $st) {
                    $stageRotMap[$st['stage_key']] = (int)($st['rotting_days'] ?? 14);
                }
                $deals = (new Opportunity)->table()
                    ->where('workspace_id', $workspaceId)
                    ->where('deleted_at', null)
                    ->where('stage', '!=', 'closed_won')
                    ->where('stage', '!=', 'closed_lost')
                    ->get();
                $now = new \DateTime();
                $rottingDeals = [];
                foreach ($deals as $d) {
                    $threshold = $stageRotMap[$d['stage']] ?? 14;
                    $lastActivity = !empty($d['updated_at']) ? new \DateTime($d['updated_at']) : new \DateTime($d['created_at']);
                    $diffDays = $now->diff($lastActivity)->days;
                    if ($diffDays >= $threshold) {
                        $d['stagnant_days'] = $diffDays;
                        $d['rotting_threshold'] = $threshold;
                        $rottingDeals[] = $d;
                    }
                }
                return ['rotting_deals' => $rottingDeals, 'count' => count($rottingDeals)];

            case 'get_sales_funnel_analytics':
                $pipeId = !empty($arguments['pipeline_id']) ? (int)$arguments['pipeline_id'] : null;
                $dealsQuery = (new Opportunity)->table()->where('workspace_id', $workspaceId)->where('deleted_at', null);
                if ($pipeId) $dealsQuery->where('pipeline_id', $pipeId);
                $deals = $dealsQuery->get();

                $stages = (new PipelineStage)->table()->get();
                $stageProbMap = [];
                foreach ($stages as $st) {
                    $stageProbMap[$st['stage_key']] = (int)$st['probability'];
                }

                $nominal = 0.0;
                $weighted = 0.0;
                $wonCount = 0;
                $lostCount = 0;
                $openCount = 0;
                $byStage = [];

                foreach ($deals as $d) {
                    $st = (string)($d['stage'] ?? 'lead');
                    $amt = (float)($d['amount'] ?? 0);
                    $prob = $stageProbMap[$st] ?? 20;

                    $byStage[$st] = ($byStage[$st] ?? 0) + 1;
                    $nominal += $amt;

                    if ($st === 'closed_won') {
                        $wonCount++;
                        $weighted += $amt;
                    } elseif ($st === 'closed_lost') {
                        $lostCount++;
                    } else {
                        $openCount++;
                        $weighted += ($amt * ($prob / 100));
                    }
                }
                $closedTotal = $wonCount + $lostCount;
                $winRate = $closedTotal > 0 ? round(($wonCount / $closedTotal) * 100, 1) : 0.0;

                return [
                    'total_deals'     => count($deals),
                    'open_deals'      => $openCount,
                    'won_deals'       => $wonCount,
                    'lost_deals'      => $lostCount,
                    'win_rate_pct'    => $winRate,
                    'nominal_revenue' => $nominal,
                    'weighted_revenue'=> round($weighted, 2),
                    'stage_funnel'    => $byStage,
                ];

            case 'list_recycle_bin':
                $filterType = (string)($arguments['entity_type'] ?? '');
                $results = [];
                if (!$filterType || $filterType === 'companies') {
                    $results['companies'] = (new Company)->table()->where('workspace_id', $workspaceId)->whereNotNull('deleted_at')->get();
                }
                if (!$filterType || $filterType === 'people') {
                    $results['people'] = (new Person)->table()->where('workspace_id', $workspaceId)->whereNotNull('deleted_at')->get();
                }
                if (!$filterType || $filterType === 'opportunities') {
                    $results['opportunities'] = (new Opportunity)->table()->where('workspace_id', $workspaceId)->whereNotNull('deleted_at')->get();
                }
                if (!$filterType || $filterType === 'tasks') {
                    $results['tasks'] = (new Task)->table()->where('workspace_id', $workspaceId)->whereNotNull('deleted_at')->get();
                }
                return $results;

            case 'restore_from_recycle_bin':
                $eType = (string)($arguments['entity_type'] ?? '');
                $eId   = (int)($arguments['id'] ?? 0);
                $model = match($eType) {
                    'companies'     => new Company(),
                    'people'        => new Person(),
                    'opportunities' => new Opportunity(),
                    'tasks'         => new Task(),
                    default         => null
                };
                if (!$model) {
                    return ['error' => "Invalid entity_type '{$eType}'"];
                }
                $updated = $model->table()
                    ->where('id', $eId)
                    ->where('workspace_id', $workspaceId)
                    ->update(['deleted_at' => null]);
                return ['success' => (bool)$updated, 'message' => "Restored {$eType} #{$eId} from Recycle Bin."];

            // --- AI Manager & Executive Co-Pilot Executions ---
            case 'ai_manager_briefing':
                return (new AiManagerService())->getExecutiveBriefing($workspaceId, $userId);

            case 'ai_lead_score':
                $personId = (int)($arguments['person_id'] ?? 0);
                return (new AiManagerService())->calculateLeadScore($workspaceId, $personId);

            case 'ai_recommend_next_action':
                $eType = (string)($arguments['entity_type'] ?? 'opportunities');
                $eId   = (int)($arguments['id'] ?? 0);
                return (new AiManagerService())->recommendNextAction($workspaceId, $eType, $eId);

            case 'ai_create_quote':
                return (new AiManagerService())->createAiQuote($workspaceId, $userId, $arguments);

            case 'ai_send_tracked_email':
                return (new AiManagerService())->sendTrackedEmail($workspaceId, $userId, $arguments);

            case 'ai_get_booking_link':
                $targetUid = !empty($arguments['user_id']) ? (int)$arguments['user_id'] : $userId;
                return (new AiManagerService())->getBookingLink($workspaceId, $targetUid);

            case 'ai_detect_duplicates':
                $eType = (string)($arguments['entity_type'] ?? 'people');
                return (new AiManagerService())->detectDuplicates($workspaceId, $eType);

            case 'ai_merge_records':
                $eType = (string)($arguments['entity_type'] ?? 'people');
                $pId   = (int)($arguments['primary_id'] ?? 0);
                $dId   = (int)($arguments['duplicate_id'] ?? 0);
                return (new AiManagerService())->mergeRecords($workspaceId, $userId, $eType, $pId, $dId);

            case 'ai_trigger_automation':
                $event = (string)($arguments['event'] ?? '');
                $payload = (array)($arguments['payload'] ?? []);
                (new AutomationService())->dispatch($workspaceId, $event, $payload);
                return [
                    'success' => true,
                    'event'   => $event,
                    'message' => "Workflow automation event '{$event}' dispatched successfully.",
                ];

            case 'ai_segment_customers':
                return (new AiManagerService())->segmentCustomers($workspaceId, $arguments);

            case 'ai_bulk_email_group':
                return (new AiManagerService())->bulkEmailSegment($workspaceId, $userId, $arguments);

            default:
                return ['error' => "Unknown tool '{$toolName}'"];
        }
    }
}
