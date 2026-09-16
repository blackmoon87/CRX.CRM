<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\BookingController;
use App\Controllers\TrackingController;
use App\Controllers\FormController;
use App\Controllers\QuoteController;
use App\Controllers\LocaleController;
use App\Controllers\Api\CopilotController;

/**
 * Web Routes (Public / Auth / Localization)
 */

$app->router->get('/locale/{lang}', [LocaleController::class, 'switch']);

$app->router->get('/', function() use ($app) {
    if ($app->auth->check()) {
        $app->response->redirect('/dashboard');
    } else {
        $app->response->redirect('/login');
    }
});

$app->router->get('/landing', function() use ($app) {
    $filePath = dirname(__DIR__) . '/docs/index.html';
    if (file_exists($filePath)) {
        return file_get_contents($filePath);
    }
    $app->response->redirect('/login');
});

$app->router->get('/login', [AuthController::class, 'showLogin']);
$app->router->post('/login', [AuthController::class, 'login']);

$app->router->get('/register', [AuthController::class, 'showRegister']);
$app->router->post('/register', [AuthController::class, 'register']);

$app->router->get('/logout', [AuthController::class, 'logout']);
$app->router->post('/logout', [AuthController::class, 'logout']);

// Public Booking Scheduler Routes
$app->router->get('/book/{slug}', [BookingController::class, 'show']);
$app->router->post('/book/{slug}', [BookingController::class, 'book']);
$app->router->get('/book/{slug}/success', [BookingController::class, 'success']);

// Email Open & Click Tracking Routes
$app->router->get('/track/open/{token}', [TrackingController::class, 'pixel']);
$app->router->get('/track/click/{token}', [TrackingController::class, 'click']);

// Web-to-Lead Ingestion Route (CORS Enabled)
$app->router->post('/api/v1/forms/{uuid}/submit', [FormController::class, 'submit']);

// Client Proposal & Digital Acceptance Routes
$app->router->get('/quote/{token}', [QuoteController::class, 'publicView']);
$app->router->post('/quote/{token}/accept', [QuoteController::class, 'accept']);

// AI Copilot Live LLM Chat & MCP Execution Endpoints
$app->router->post('/api/copilot/chat', [CopilotController::class, 'chat']);
$app->router->post('/api/copilot/execute-mcp', [CopilotController::class, 'executeMcp']);

