<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CustomField;
use App\Models\CustomFieldValue;

class CustomFieldService
{
    /**
     * Supported custom field types matching Relaticle CRM.
     */
    public const TYPES = [
        'text'         => 'Text (Single line)',
        'textarea'     => 'Text Area (Multi-line)',
        'number'       => 'Number',
        'currency'     => 'Currency ($)',
        'date'         => 'Date',
        'boolean'      => 'Boolean (True / False)',
        'select'       => 'Dropdown Select',
        'multi_select' => 'Multi-Select Options',
        'email'        => 'Email Address',
        'phone'        => 'Phone Number',
        'url'          => 'Website / URL',
        'rating'       => 'Rating (1-5 Stars)',
    ];

    public function getFieldsForEntity(int $workspaceId, string $entityType): array
    {
        return (new CustomField)->table()
            ->where('workspace_id', $workspaceId)
            ->where('entity_type', $entityType)
            ->orderBy('order_column', 'ASC')
            ->get();
    }

    public function getValuesForEntity(int $workspaceId, string $entityType, int $entityId): array
    {
        $rawValues = (new CustomFieldValue)->table()
            ->where('workspace_id', $workspaceId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->get();

        $valuesByFieldId = [];
        foreach ($rawValues as $v) {
            $val = $v['text_value'] 
                ?? $v['number_value'] 
                ?? $v['date_value'] 
                ?? ($v['json_value'] ? json_decode((string)$v['json_value'], true) : null);
            $valuesByFieldId[(int)$v['custom_field_id']] = $val;
        }

        return $valuesByFieldId;
    }

    /**
     * Return associative array of [code => value, ...] for JSON APIs and views.
     */
    public function getFormattedCustomFields(int $workspaceId, string $entityType, int $entityId): array
    {
        $fields = $this->getFieldsForEntity($workspaceId, $entityType);
        $values = $this->getValuesForEntity($workspaceId, $entityType, $entityId);

        $result = [];
        foreach ($fields as $field) {
            $fieldId = (int)$field['id'];
            $code = $field['code'];
            $type = $field['type'];
            $raw = $values[$fieldId] ?? null;

            if ($type === 'boolean' && $raw !== null) {
                $raw = (bool)$raw;
            } elseif (($type === 'number' || $type === 'rating') && $raw !== null) {
                $raw = is_numeric($raw) ? ($raw == (int)$raw ? (int)$raw : (float)$raw) : $raw;
            }

            $result[$code] = $raw;
        }

        return $result;
    }

    public function saveValues(int $workspaceId, string $entityType, int $entityId, array $inputs): void
    {
        $fields = $this->getFieldsForEntity($workspaceId, $entityType);
        $cfvModel = new CustomFieldValue();

        // Support nested 'custom_fields' array
        $flatInputs = $inputs;
        if (isset($inputs['custom_fields']) && is_array($inputs['custom_fields'])) {
            $flatInputs = array_merge($flatInputs, $inputs['custom_fields']);
        }

        foreach ($fields as $field) {
            $fieldId = (int)$field['id'];
            $code = $field['code'];

            if (!array_key_exists("cf_{$code}", $flatInputs) && !array_key_exists($code, $flatInputs)) {
                continue;
            }

            $raw = $flatInputs["cf_{$code}"] ?? $flatInputs[$code];
            $type = $field['type'];

            $textVal = null;
            $numberVal = null;
            $dateVal = null;
            $jsonVal = null;

            if ($raw === null || $raw === '') {
                // Clearing the value
            } elseif ($type === 'multi_select') {
                if (is_array($raw)) {
                    $jsonVal = json_encode(array_values($raw), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                } elseif (is_string($raw)) {
                    $arr = array_values(array_filter(array_map('trim', explode(',', $raw))));
                    $jsonVal = json_encode($arr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
            } elseif ($type === 'boolean') {
                $numberVal = (!empty($raw) && $raw !== '0' && $raw !== 'false') ? 1 : 0;
            } elseif ($type === 'number' || $type === 'currency' || $type === 'rating') {
                $numberVal = is_numeric($raw) ? (float)$raw : null;
            } elseif ($type === 'date') {
                $dateVal = !empty($raw) ? (string)$raw : null;
            } elseif (is_array($raw)) {
                $jsonVal = json_encode($raw, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } else {
                $textVal = (string)$raw;
            }

            // Check if existing record
            $existing = $cfvModel->table()
                ->where('workspace_id', $workspaceId)
                ->where('custom_field_id', $fieldId)
                ->where('entity_type', $entityType)
                ->where('entity_id', $entityId)
                ->first();

            $data = [
                'text_value'   => $textVal,
                'number_value' => $numberVal,
                'date_value'   => $dateVal,
                'json_value'   => $jsonVal,
                'updated_at'   => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                $cfvModel->table()->where('id', (int)$existing['id'])->update($data);
            } else {
                $data['workspace_id']    = $workspaceId;
                $data['custom_field_id'] = $fieldId;
                $data['entity_type']     = $entityType;
                $data['entity_id']       = $entityId;
                $data['created_at']      = date('Y-m-d H:i:s');
                $cfvModel->table()->insert($data);
            }
        }
    }
}
