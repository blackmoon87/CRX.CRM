<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Models\CustomField;

class CustomFieldsApiController extends BaseApiController
{
    public function index(): void
    {
        $wsId = $this->getWorkspaceId();
        $query = (new CustomField)->table()->where('workspace_id', $wsId);

        $entityType = trim((string)($this->request->get('entity_type') ?? ''));
        if ($entityType !== '') {
            $query->where('entity_type', $entityType);
        }

        $fields = $query->orderBy('entity_type', 'ASC')->orderBy('order_column', 'ASC')->get();

        $data = [];
        foreach ($fields as $f) {
            $f['id'] = (int)$f['id'];
            $f['is_required'] = (bool)$f['is_required'];
            $f['options'] = !empty($f['options']) ? json_decode((string)$f['options'], true) : [];
            $data[] = $f;
        }

        $this->respondJson($data);
    }
}
