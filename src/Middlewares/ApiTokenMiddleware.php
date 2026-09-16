<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Models\PersonalAccessToken;
use Spartan\Middleware;
use Spartan\Request;
use Spartan\Response;

class ApiTokenMiddleware extends Middleware
{
    public function handle(Request $request, Response $response, callable $next): mixed
    {
        $authHeader = $request->header('Authorization') ?? '';
        $token = '';

        if (str_starts_with($authHeader, 'Bearer ')) {
            $token = trim(substr($authHeader, 7));
        }

        if ($token === '') {
            $token = (string)($request->get('api_token') ?? '');
        }

        // If session is already authenticated (e.g. web user calling API), allow through
        if ($this->auth && $this->auth->check()) {
            return $next($request, $response);
        }

        if ($token === '') {
            return $response->json([
                'error' => 'Unauthorized: Missing API or Bearer token',
                'code'  => 401,
            ], 401);
        }

        $tokenRecord = (new PersonalAccessToken)->table()
            ->where('token', $token)
            ->first();

        if (!$tokenRecord) {
            return $response->json([
                'error' => 'Unauthorized: Invalid token',
                'code'  => 401,
            ], 401);
        }

        // Update last_used_at
        (new PersonalAccessToken)->table()
            ->where('id', (int)$tokenRecord['id'])
            ->update(['last_used_at' => date('Y-m-d H:i:s')]);

        // Attach workspace and user to session/request context
        $this->session->set('api_user_id', (int)$tokenRecord['user_id']);
        $this->session->set('api_workspace_id', (int)$tokenRecord['workspace_id']);

        return $next($request, $response);
    }
}
