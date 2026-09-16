<?php

declare(strict_types=1);

use App\Controllers\CompanyController;
use App\Controllers\CustomFieldController;
use App\Controllers\DashboardController;
use App\Controllers\InteractionController;
use App\Controllers\NoteController;
use App\Controllers\OpportunityController;
use App\Controllers\PeopleController;
use App\Controllers\RecycleBinController;
use App\Controllers\ReportController;
use App\Controllers\SettingController;
use App\Controllers\TagController;
use App\Controllers\TaskController;
use App\Controllers\TemplateController;
use App\Controllers\WorkflowController;
use App\Controllers\WorkspaceController;
use App\Middlewares\WorkspaceMiddleware;
use Spartan\Middlewares\AuthMiddleware;

/**
 * Protected CRM Routes
 */

$crmGuards = [
    AuthMiddleware::class,
    WorkspaceMiddleware::class,
];

// Dashboard & Workspace Switcher
$app->router->get('/dashboard', [DashboardController::class, 'index'], $crmGuards);
$app->router->post('/workspaces/switch', [WorkspaceController::class, 'switchWorkspace'], $crmGuards);

// Pro Analytics & Sales Velocity Funnel
$app->router->get('/reports', [ReportController::class, 'index'], $crmGuards);
$app->router->get('/reports/export', [ReportController::class, 'export'], $crmGuards);

// Companies
$app->router->get('/companies', [CompanyController::class, 'index'], $crmGuards);
$app->router->get('/companies/create', [CompanyController::class, 'create'], $crmGuards);
$app->router->post('/companies', [CompanyController::class, 'store'], $crmGuards);
$app->router->post('/companies/bulk', [CompanyController::class, 'bulk'], $crmGuards);
$app->router->post('/companies/merge', [CompanyController::class, 'merge'], $crmGuards);
$app->router->get('/companies/{id}', [CompanyController::class, 'show'], $crmGuards);
$app->router->post('/companies/{id}/delete', [CompanyController::class, 'destroy'], $crmGuards);

// Contacts / People
$app->router->get('/people', [PeopleController::class, 'index'], $crmGuards);
$app->router->get('/people/create', [PeopleController::class, 'create'], $crmGuards);
$app->router->post('/people', [PeopleController::class, 'store'], $crmGuards);
$app->router->post('/people/bulk', [PeopleController::class, 'bulk'], $crmGuards);
$app->router->post('/people/merge', [PeopleController::class, 'merge'], $crmGuards);
$app->router->get('/people/{id}', [PeopleController::class, 'show'], $crmGuards);
$app->router->post('/people/{id}/delete', [PeopleController::class, 'destroy'], $crmGuards);

// Opportunities / Pipeline
$app->router->get('/opportunities', [OpportunityController::class, 'index'], $crmGuards);
$app->router->get('/opportunities/create', [OpportunityController::class, 'create'], $crmGuards);
$app->router->post('/opportunities', [OpportunityController::class, 'store'], $crmGuards);
$app->router->post('/opportunities/bulk', [OpportunityController::class, 'bulk'], $crmGuards);
$app->router->get('/opportunities/{id}', [OpportunityController::class, 'show'], $crmGuards);
$app->router->post('/opportunities/update-stage', [OpportunityController::class, 'updateStage'], $crmGuards);
$app->router->post('/opportunities/{id}/delete', [OpportunityController::class, 'destroy'], $crmGuards);

// Tasks
$app->router->get('/tasks', [TaskController::class, 'index'], $crmGuards);
$app->router->get('/tasks/create', [TaskController::class, 'create'], $crmGuards);
$app->router->post('/tasks', [TaskController::class, 'store'], $crmGuards);
$app->router->post('/tasks/bulk', [TaskController::class, 'bulk'], $crmGuards);
$app->router->post('/tasks/{id}/toggle', [TaskController::class, 'toggleStatus'], $crmGuards);
$app->router->post('/tasks/{id}/delete', [TaskController::class, 'destroy'], $crmGuards);

// Notes & Pro Omnichannel Interactions Timeline
$app->router->post('/notes', [NoteController::class, 'store'], $crmGuards);
$app->router->post('/notes/{id}/delete', [NoteController::class, 'destroy'], $crmGuards);
$app->router->post('/interactions', [InteractionController::class, 'store'], $crmGuards);
$app->router->get('/interactions/{id}/ics', [InteractionController::class, 'ics'], $crmGuards);
$app->router->get('/api/upcoming-alarms', [InteractionController::class, 'upcomingAlarms'], $crmGuards);
$app->router->post('/interactions/{id}/delete', [InteractionController::class, 'destroy'], $crmGuards);

// Tags & Segmentation
$app->router->get('/settings/tags', [TagController::class, 'index'], $crmGuards);
$app->router->post('/settings/tags', [TagController::class, 'store'], $crmGuards);
$app->router->post('/settings/tags/{id}/delete', [TagController::class, 'destroy'], $crmGuards);
$app->router->post('/settings/tags/attach', [TagController::class, 'attach'], $crmGuards);
$app->router->post('/settings/tags/detach', [TagController::class, 'detach'], $crmGuards);

// Communication Templates / Snippets
$app->router->get('/settings/templates', [TemplateController::class, 'index'], $crmGuards);
$app->router->post('/settings/templates', [TemplateController::class, 'store'], $crmGuards);
$app->router->post('/settings/templates/{id}/delete', [TemplateController::class, 'destroy'], $crmGuards);

// Event-Driven Workflow Automations
$app->router->get('/settings/workflows', [WorkflowController::class, 'index'], $crmGuards);
$app->router->post('/settings/workflows', [WorkflowController::class, 'store'], $crmGuards);
$app->router->post('/settings/workflows/{id}/toggle', [WorkflowController::class, 'toggle'], $crmGuards);
$app->router->post('/settings/workflows/{id}/test', [WorkflowController::class, 'test'], $crmGuards);
$app->router->post('/settings/workflows/{id}/delete', [WorkflowController::class, 'destroy'], $crmGuards);

// Recycle Bin & Soft Deletes
$app->router->get('/settings/recycle-bin', [RecycleBinController::class, 'index'], $crmGuards);
$app->router->post('/settings/recycle-bin/restore', [RecycleBinController::class, 'restore'], $crmGuards);
$app->router->post('/settings/recycle-bin/purge', [RecycleBinController::class, 'purge'], $crmGuards);

// Workspace Settings & Team
$app->router->get('/settings/workspace', [WorkspaceController::class, 'settings'], $crmGuards);
$app->router->post('/settings/workspace/update', [WorkspaceController::class, 'updateSettings'], $crmGuards);
$app->router->post('/settings/workspace/invite', [WorkspaceController::class, 'inviteMember'], $crmGuards);
$app->router->post('/settings/workspace/members/{id}/remove', [WorkspaceController::class, 'removeMember'], $crmGuards);
$app->router->post('/settings/workspace/members/{id}/role', [WorkspaceController::class, 'updateMemberRole'], $crmGuards);

// Roles & Granular Permissions (RBAC)
$app->router->get('/settings/roles', [\App\Controllers\RoleController::class, 'index'], $crmGuards);
$app->router->post('/settings/roles', [\App\Controllers\RoleController::class, 'store'], $crmGuards);
$app->router->post('/settings/roles/{id}/update', [\App\Controllers\RoleController::class, 'update'], $crmGuards);
$app->router->post('/settings/roles/{id}/delete', [\App\Controllers\RoleController::class, 'delete'], $crmGuards);


// Data Import & Export Subsystem
$app->router->get('/import', [\App\Controllers\ImportExportController::class, 'importView'], $crmGuards);
$app->router->post('/import/preview', [\App\Controllers\ImportExportController::class, 'importPreview'], $crmGuards);
$app->router->post('/import/execute', [\App\Controllers\ImportExportController::class, 'importExecute'], $crmGuards);
$app->router->get('/export/{entity}', [\App\Controllers\ImportExportController::class, 'export'], $crmGuards);

// Custom Fields Settings
$app->router->get('/settings/custom-fields', [CustomFieldController::class, 'index'], $crmGuards);
$app->router->post('/settings/custom-fields', [CustomFieldController::class, 'store'], $crmGuards);
$app->router->post('/settings/custom-fields/{id}/delete', [CustomFieldController::class, 'destroy'], $crmGuards);

// API Tokens & MCP Agent Access
$app->router->get('/settings/api-tokens', [SettingController::class, 'apiTokens'], $crmGuards);
$app->router->post('/settings/api-tokens/generate', [SettingController::class, 'generateToken'], $crmGuards);
$app->router->post('/settings/api-tokens/{id}/revoke', [SettingController::class, 'revokeToken'], $crmGuards);

// Saved Views & Custom Filter Tabs
$app->router->post('/saved-views', [\App\Controllers\SavedViewController::class, 'store'], $crmGuards);
$app->router->post('/saved-views/{id}/update', [\App\Controllers\SavedViewController::class, 'update'], $crmGuards);
$app->router->post('/saved-views/{id}/delete', [\App\Controllers\SavedViewController::class, 'destroy'], $crmGuards);
$app->router->post('/saved-views/{id}/default', [\App\Controllers\SavedViewController::class, 'setDefault'], $crmGuards);

// Spreadsheet-Style Inline Cell Editing API
$app->router->post('/api/inline-update', [\App\Controllers\Api\InlineEditController::class, 'update'], $crmGuards);

// Booking Settings (Public Scheduler Configuration)
$app->router->get('/settings/booking', [\App\Controllers\BookingController::class, 'settings'], $crmGuards);
$app->router->post('/settings/booking', [\App\Controllers\BookingController::class, 'updateSettings'], $crmGuards);

// Web-to-Lead Forms Management
$app->router->get('/settings/forms', [\App\Controllers\FormController::class, 'index'], $crmGuards);
$app->router->post('/settings/forms', [\App\Controllers\FormController::class, 'store'], $crmGuards);

// Quotes, Invoicing & CPQ
$app->router->get('/quotes', [\App\Controllers\QuoteController::class, 'index'], $crmGuards);
$app->router->get('/quotes/create', [\App\Controllers\QuoteController::class, 'create'], $crmGuards);
$app->router->post('/quotes', [\App\Controllers\QuoteController::class, 'store'], $crmGuards);
$app->router->get('/quotes/{id}', [\App\Controllers\QuoteController::class, 'show'], $crmGuards);



