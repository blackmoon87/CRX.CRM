<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Models\Activity;
use App\Models\User;
use App\Services\CustomFieldService;
use Spartan\Controller;

abstract class BaseApiController extends Controller
{
    protected ?CustomFieldService $cfService = null;

    protected function customFields(): CustomFieldService
    {
        if ($this->cfService === null) {
            $this->cfService = new CustomFieldService();
        }
        return $this->cfService;
    }

    protected function getWorkspaceId(): int
    {
        $wsId = $this->session->get('api_workspace_id')
            ?? $this->session->get('active_workspace_id');

        if ($wsId) {
            return (int)$wsId;
        }

        // Check user memberships
        $userId = $this->getUserId();
        if ($userId) {
            $member = (new \App\Models\Membership)->table()
                ->where('user_id', $userId)
                ->first();
            if ($member) {
                return (int)$member['workspace_id'];
            }
        }

        return 1;
    }

    protected function getUserId(): int
    {
        $id = $this->session->get('api_user_id')
            ?? ($this->auth ? $this->auth->id() : null);

        return (int)($id ?? 1);
    }

    protected function respondJson(mixed $data, int $status = 200, array $meta = []): void
    {
        $payload = ['data' => $data];
        if (!empty($meta)) {
            $payload['meta'] = $meta;
        }

        $this->response->json($payload, $status);
    }

    protected function respondError(string $message, int $status = 400, array $errors = []): void
    {
        $payload = [
            'error'   => $message,
            'code'    => $status,
        ];
        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }

        $this->response->json($payload, $status);
    }

    protected function recordActivity(string $action, string $entityType, int $entityId, string $description, array $metadata = []): void
    {
        try {
            (new Activity)->table()->insert([
                'workspace_id' => $this->getWorkspaceId(),
                'user_id'      => $this->getUserId(),
                'action'       => $action,
                'entity_type'  => $entityType,
                'entity_id'    => $entityId,
                'description'  => $description,
                'metadata'     => !empty($metadata) ? json_encode($metadata, JSON_UNESCAPED_SLASHES) : null,
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Ignore activity logging errors in API
        }
    }
}
