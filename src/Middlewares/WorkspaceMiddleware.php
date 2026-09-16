<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Services\WorkspaceService;
use Spartan\Middleware;
use Spartan\Request;
use Spartan\Response;

class WorkspaceMiddleware extends Middleware
{
    public function handle(Request $request, Response $response, callable $next): mixed
    {
        $session = $this->session;
        $auth = $this->auth;

        if (!$auth || !$auth->check()) {
            return $response->redirect('/login');
        }

        $userId = (int)$auth->id();
        $wsService = new WorkspaceService($session);
        $activeWorkspaceId = $wsService->getActiveWorkspaceId($userId);

        if (!$activeWorkspaceId) {
            $session->setFlash('error', 'No active workspace found. Please contact support or create one.');
            return $response->redirect('/logout');
        }

        return $next($request, $response);
    }
}
