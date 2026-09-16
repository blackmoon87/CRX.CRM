<?php

declare(strict_types=1);

use App\Controllers\Api\CrmApiController;
use App\Controllers\Api\McpController;
use App\Controllers\Api\V1\CompanyApiController;
use App\Controllers\Api\V1\CustomFieldsApiController;
use App\Controllers\Api\V1\NoteApiController;
use App\Controllers\Api\V1\OpportunityApiController;
use App\Controllers\Api\V1\PeopleApiController;
use App\Controllers\Api\V1\TaskApiController;
use App\Controllers\Api\V1\UserApiController;
use App\Middlewares\ApiTokenMiddleware;

/**
 * API Routes (Stateless JSON & Model Context Protocol)
 */

// Exclude all API routes from CSRF verification
$app->router->excludeCsrf('/api/*', '/api/mcp');

// MCP Server Endpoint (JSON-RPC 2.0)
$app->router->post('/api/mcp', [McpController::class, 'handle'], [ApiTokenMiddleware::class]);
$app->router->get('/api/mcp', [McpController::class, 'handle'], [ApiTokenMiddleware::class]);

// Legacy / Quick Summary REST API Endpoints
$app->router->get('/api/summary', [CrmApiController::class, 'summary'], [ApiTokenMiddleware::class]);
$app->router->get('/api/companies', [CrmApiController::class, 'companies'], [ApiTokenMiddleware::class]);
$app->router->get('/api/people', [CrmApiController::class, 'people'], [ApiTokenMiddleware::class]);
$app->router->get('/api/opportunities', [CrmApiController::class, 'opportunities'], [ApiTokenMiddleware::class]);
$app->router->get('/api/tasks', [CrmApiController::class, 'tasks'], [ApiTokenMiddleware::class]);

// ==========================================
// REST API V1 Suite (Matching Relaticle 1:1)
// ==========================================

// Current User
$app->router->get('/api/v1/user', [UserApiController::class, 'show'], [ApiTokenMiddleware::class]);

// Custom Fields
$app->router->get('/api/v1/custom-fields', [CustomFieldsApiController::class, 'index'], [ApiTokenMiddleware::class]);

// Companies
$app->router->get('/api/v1/companies', [CompanyApiController::class, 'index'], [ApiTokenMiddleware::class]);
$app->router->post('/api/v1/companies', [CompanyApiController::class, 'store'], [ApiTokenMiddleware::class]);
$app->router->get('/api/v1/companies/{id}', [CompanyApiController::class, 'show'], [ApiTokenMiddleware::class]);
$app->router->put('/api/v1/companies/{id}', [CompanyApiController::class, 'update'], [ApiTokenMiddleware::class]);
$app->router->patch('/api/v1/companies/{id}', [CompanyApiController::class, 'update'], [ApiTokenMiddleware::class]);
$app->router->delete('/api/v1/companies/{id}', [CompanyApiController::class, 'destroy'], [ApiTokenMiddleware::class]);

// People (Contacts)
$app->router->get('/api/v1/people', [PeopleApiController::class, 'index'], [ApiTokenMiddleware::class]);
$app->router->post('/api/v1/people', [PeopleApiController::class, 'store'], [ApiTokenMiddleware::class]);
$app->router->get('/api/v1/people/{id}', [PeopleApiController::class, 'show'], [ApiTokenMiddleware::class]);
$app->router->put('/api/v1/people/{id}', [PeopleApiController::class, 'update'], [ApiTokenMiddleware::class]);
$app->router->patch('/api/v1/people/{id}', [PeopleApiController::class, 'update'], [ApiTokenMiddleware::class]);
$app->router->delete('/api/v1/people/{id}', [PeopleApiController::class, 'destroy'], [ApiTokenMiddleware::class]);

// Opportunities (Deals)
$app->router->get('/api/v1/opportunities', [OpportunityApiController::class, 'index'], [ApiTokenMiddleware::class]);
$app->router->post('/api/v1/opportunities', [OpportunityApiController::class, 'store'], [ApiTokenMiddleware::class]);
$app->router->get('/api/v1/opportunities/{id}', [OpportunityApiController::class, 'show'], [ApiTokenMiddleware::class]);
$app->router->put('/api/v1/opportunities/{id}', [OpportunityApiController::class, 'update'], [ApiTokenMiddleware::class]);
$app->router->patch('/api/v1/opportunities/{id}', [OpportunityApiController::class, 'update'], [ApiTokenMiddleware::class]);
$app->router->delete('/api/v1/opportunities/{id}', [OpportunityApiController::class, 'destroy'], [ApiTokenMiddleware::class]);

// Tasks
$app->router->get('/api/v1/tasks', [TaskApiController::class, 'index'], [ApiTokenMiddleware::class]);
$app->router->post('/api/v1/tasks', [TaskApiController::class, 'store'], [ApiTokenMiddleware::class]);
$app->router->get('/api/v1/tasks/{id}', [TaskApiController::class, 'show'], [ApiTokenMiddleware::class]);
$app->router->put('/api/v1/tasks/{id}', [TaskApiController::class, 'update'], [ApiTokenMiddleware::class]);
$app->router->patch('/api/v1/tasks/{id}', [TaskApiController::class, 'update'], [ApiTokenMiddleware::class]);
$app->router->delete('/api/v1/tasks/{id}', [TaskApiController::class, 'destroy'], [ApiTokenMiddleware::class]);

// Notes
$app->router->get('/api/v1/notes', [NoteApiController::class, 'index'], [ApiTokenMiddleware::class]);
$app->router->post('/api/v1/notes', [NoteApiController::class, 'store'], [ApiTokenMiddleware::class]);
$app->router->get('/api/v1/notes/{id}', [NoteApiController::class, 'show'], [ApiTokenMiddleware::class]);
$app->router->put('/api/v1/notes/{id}', [NoteApiController::class, 'update'], [ApiTokenMiddleware::class]);
$app->router->patch('/api/v1/notes/{id}', [NoteApiController::class, 'update'], [ApiTokenMiddleware::class]);
$app->router->delete('/api/v1/notes/{id}', [NoteApiController::class, 'destroy'], [ApiTokenMiddleware::class]);
