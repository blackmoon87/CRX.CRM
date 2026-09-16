<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Person;
use App\Models\Task;
use App\Services\CustomFieldService;
use App\Services\WorkspaceService;
use Spartan\Controller;

class ImportExportController extends Controller
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

    public function export(string $entity): void
    {
        $ctx = $this->getContext();
        $wsId = $ctx['workspaceId'];
        $cfService = new CustomFieldService();

        $allowed = ['companies', 'people', 'opportunities', 'tasks', 'quotes'];
        if (!in_array($entity, $allowed, true)) {
            $this->response->setStatusCode(400);
            $this->response->setContent("Invalid export entity '{$entity}'. Allowed: " . implode(', ', $allowed));
            return;
        }

        $fields = $cfService->getFieldsForEntity($wsId, $entity);
        $customFieldCodes = array_column($fields, 'code');

        $filename = "{$entity}-export-" . date('Y-m-d-His') . ".csv";

        $this->response->setHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        $out = fopen('php://temp', 'w+');
        // UTF-8 BOM for Excel
        fputs($out, "\xEF\xBB\xBF");

        if ($entity === 'companies') {
            $headers = ['id', 'name', 'domain', 'industry', 'size', 'phone', 'email', 'website', 'address', 'city', 'country', 'annual_revenue', 'description', 'created_at'];
            foreach ($customFieldCodes as $c) {
                $headers[] = "cf_{$c}";
            }
            fputcsv($out, $headers);

            $rows = (new Company)->table()->where('workspace_id', $wsId)->orderBy('id', 'ASC')->get();
            foreach ($rows as $r) {
                $cfs = $cfService->getFormattedCustomFields($wsId, 'companies', (int)$r['id']);
                $line = [
                    $r['id'], $r['name'], $r['domain'], $r['industry'], $r['size'],
                    $r['phone'], $r['email'], $r['website'], $r['address'],
                    $r['city'], $r['state'] ?? '', $r['postal_code'] ?? '',
                    $r['country'], $r['annual_revenue'], $r['description'], $r['created_at'],
                ];
                foreach ($customFieldCodes as $c) {
                    $val = $cfs[$c] ?? '';
                    $line[] = is_array($val) ? implode(', ', $val) : (string)$val;
                }
                fputcsv($out, $line);
            }
        } elseif ($entity === 'people') {
            $headers = ['id', 'company_id', 'first_name', 'last_name', 'email', 'phone', 'job_title', 'status', 'address', 'city', 'state', 'postal_code', 'country', 'created_at'];
            foreach ($customFieldCodes as $c) {
                $headers[] = "cf_{$c}";
            }
            fputcsv($out, $headers);

            $rows = (new Person)->table()->where('workspace_id', $wsId)->orderBy('id', 'ASC')->get();
            foreach ($rows as $r) {
                $cfs = $cfService->getFormattedCustomFields($wsId, 'people', (int)$r['id']);
                $line = [
                    $r['id'], $r['company_id'], $r['first_name'], $r['last_name'],
                    $r['email'], $r['phone'], $r['job_title'], $r['status'],
                    $r['address'] ?? '', $r['city'] ?? '', $r['state'] ?? '',
                    $r['postal_code'] ?? '', $r['country'] ?? '', $r['created_at'],
                ];
                foreach ($customFieldCodes as $c) {
                    $val = $cfs[$c] ?? '';
                    $line[] = is_array($val) ? implode(', ', $val) : (string)$val;
                }
                fputcsv($out, $line);
            }
        } elseif ($entity === 'opportunities') {
            $headers = ['id', 'company_id', 'person_id', 'name', 'amount', 'currency', 'stage', 'probability', 'expected_close_date', 'status', 'created_at'];
            foreach ($customFieldCodes as $c) {
                $headers[] = "cf_{$c}";
            }
            fputcsv($out, $headers);

            $rows = (new Opportunity)->table()->where('workspace_id', $wsId)->orderBy('id', 'ASC')->get();
            foreach ($rows as $r) {
                $cfs = $cfService->getFormattedCustomFields($wsId, 'opportunities', (int)$r['id']);
                $line = [
                    $r['id'], $r['company_id'], $r['person_id'], $r['name'],
                    $r['amount'], $r['currency'], $r['stage'], $r['probability'],
                    $r['expected_close_date'], $r['status'], $r['created_at'],
                ];
                foreach ($customFieldCodes as $c) {
                    $val = $cfs[$c] ?? '';
                    $line[] = is_array($val) ? implode(', ', $val) : (string)$val;
                }
                fputcsv($out, $line);
            }
        } elseif ($entity === 'tasks') {
            $headers = ['id', 'title', 'description', 'due_date', 'priority', 'status', 'entity_type', 'entity_id', 'created_at'];
            foreach ($customFieldCodes as $c) {
                $headers[] = "cf_{$c}";
            }
            fputcsv($out, $headers);

            $rows = (new Task)->table()->where('workspace_id', $wsId)->orderBy('id', 'ASC')->get();
            foreach ($rows as $r) {
                $cfs = $cfService->getFormattedCustomFields($wsId, 'tasks', (int)$r['id']);
                $line = [
                    $r['id'], $r['title'], $r['description'], $r['due_date'],
                    $r['priority'], $r['status'], $r['entity_type'], $r['entity_id'], $r['created_at'],
                ];
                foreach ($customFieldCodes as $c) {
                    $val = $cfs[$c] ?? '';
                    $line[] = is_array($val) ? implode(', ', $val) : (string)$val;
                }
                fputcsv($out, $line);
            }
        } elseif ($entity === 'quotes') {
            $headers = ['id', 'quote_number', 'title', 'company_id', 'person_id', 'opportunity_id', 'status', 'subtotal', 'tax_percent', 'tax_amount', 'discount_amount', 'total_amount', 'currency', 'valid_until', 'notes', 'created_at'];
            foreach ($customFieldCodes as $c) {
                $headers[] = "cf_{$c}";
            }
            fputcsv($out, $headers);

            $rows = (new \App\Models\Quote)->table()->where('workspace_id', $wsId)->orderBy('id', 'ASC')->get();
            foreach ($rows as $r) {
                $cfs = $cfService->getFormattedCustomFields($wsId, 'quotes', (int)$r['id']);
                $line = [
                    $r['id'], $r['quote_number'], $r['title'], $r['company_id'],
                    $r['person_id'], $r['opportunity_id'], $r['status'],
                    $r['subtotal'], $r['tax_percent'] ?? 0, $r['tax_amount'],
                    $r['discount_amount'] ?? 0, $r['total_amount'], $r['currency'],
                    $r['valid_until'], $r['notes'], $r['created_at'],
                ];
                foreach ($customFieldCodes as $c) {
                    $val = $cfs[$c] ?? '';
                    $line[] = is_array($val) ? implode(', ', $val) : (string)$val;
                }
                fputcsv($out, $line);
            }
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $this->response->setContent($csv);
    }

    public function importView(): string
    {
        $ctx = $this->getContext();
        $target = $this->request->get('entity') ?? 'companies';

        return $this->render('import/index', [
            'title'      => 'Import & Export Hub',
            'target'     => $target,
            'workspace'  => $ctx['workspace'],
            'workspaces' => $ctx['workspaces'],
        ]);
    }

    public function importPreview(): string
    {
        $ctx = $this->getContext();
        $wsId = $ctx['workspaceId'];
        $entity = (string)($this->request->get('entity') ?? 'companies');

        $csvFile = $_FILES['csv_file'] ?? null;
        if (!$csvFile || empty($csvFile['tmp_name']) || !is_uploaded_file($csvFile['tmp_name'])) {
            $this->session->setFlash('error', 'Please upload a valid CSV file.');
            $this->redirect('/import?entity=' . urlencode($entity));
            return '';
        }

        $handle = fopen($csvFile['tmp_name'], 'r');
        if (!$handle) {
            $this->session->setFlash('error', 'Unable to read uploaded CSV file.');
            $this->redirect('/import?entity=' . urlencode($entity));
            return '';
        }

        // Check BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            $this->session->setFlash('error', 'CSV file is empty or missing headers.');
            $this->redirect('/import?entity=' . urlencode($entity));
            return '';
        }

        $headers = array_map('trim', $headers);

        $sampleRows = [];
        $allRows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === 1 && $row[0] === null) {
                continue;
            }
            $allRows[] = $row;
            if (count($sampleRows) < 5) {
                $sampleRows[] = $row;
            }
        }
        fclose($handle);

        if (empty($allRows)) {
            $this->session->setFlash('error', 'CSV contains headers but no data rows.');
            $this->redirect('/import?entity=' . urlencode($entity));
            return '';
        }

        // Cache rows in temporary file for execution step
        $tmpToken = bin2hex(random_bytes(16));
        $tmpPath = sys_get_temp_dir() . "/crx_import_{$tmpToken}.json";
        file_put_contents($tmpPath, json_encode([
            'entity'  => $entity,
            'headers' => $headers,
            'rows'    => $allRows,
        ], JSON_UNESCAPED_UNICODE));

        // Available CRM target fields
        $cfService = new CustomFieldService();
        $cfs = $cfService->getFieldsForEntity($wsId, $entity);

        $standardFields = [];
        if ($entity === 'companies') {
            $standardFields = [
                'name'           => 'Company Name *',
                'domain'         => 'Domain',
                'industry'       => 'Industry',
                'size'           => 'Size / Headcount',
                'phone'          => 'Phone',
                'email'          => 'Email',
                'website'        => 'Website',
                'address'        => 'Address',
                'city'           => 'City',
                'state'          => 'State / Province',
                'postal_code'    => 'Postal / Zip Code',
                'country'        => 'Country',
                'annual_revenue' => 'Annual Revenue ($)',
                'description'    => 'Description',
            ];
        } elseif ($entity === 'people') {
            $standardFields = [
                'first_name'  => 'First Name *',
                'last_name'   => 'Last Name',
                'email'       => 'Email Address',
                'phone'       => 'Phone Number',
                'job_title'   => 'Job Title',
                'status'      => 'Status (lead/active/inactive)',
                'company_id'  => 'Company ID',
                'address'     => 'Address',
                'city'        => 'City',
                'state'       => 'State / Province',
                'postal_code' => 'Postal Code',
                'country'     => 'Country',
            ];
        } elseif ($entity === 'opportunities') {
            $standardFields = [
                'name'                => 'Deal Name *',
                'amount'              => 'Amount / Value ($)',
                'currency'            => 'Currency (USD, EUR...)',
                'stage'               => 'Stage (lead, meeting, proposal, negotiation, closed_won, closed_lost)',
                'probability'         => 'Probability (0-100)',
                'expected_close_date' => 'Expected Close Date (YYYY-MM-DD)',
                'company_id'          => 'Company ID',
                'person_id'           => 'Contact (Person) ID',
            ];
        } elseif ($entity === 'tasks') {
            $standardFields = [
                'title'       => 'Task Title *',
                'description' => 'Description',
                'due_date'    => 'Due Date (YYYY-MM-DD)',
                'priority'    => 'Priority (low/medium/high/urgent)',
                'status'      => 'Status (pending/in_progress/completed)',
                'entity_type' => 'Entity Type (companies/people/opportunities)',
                'entity_id'   => 'Entity ID',
            ];
        } elseif ($entity === 'quotes') {
            $standardFields = [
                'title'           => 'Quote Title *',
                'quote_number'    => 'Quote Number',
                'company_id'      => 'Company ID',
                'person_id'       => 'Contact (Person) ID',
                'opportunity_id'  => 'Opportunity ID',
                'status'          => 'Status (draft/sent/accepted/declined)',
                'subtotal'        => 'Subtotal ($)',
                'tax_percent'     => 'Tax Percent (%)',
                'tax_amount'      => 'Tax Amount ($)',
                'discount_amount' => 'Discount Amount ($)',
                'total_amount'    => 'Total Amount ($)',
                'currency'        => 'Currency (USD, EUR...)',
                'valid_until'     => 'Valid Until (YYYY-MM-DD)',
                'notes'           => 'Terms & Notes',
            ];
        }

        $targetFields = $standardFields;
        foreach ($cfs as $cf) {
            $targetFields["cf_{$cf['code']}"] = "Custom Field: {$cf['name']} ({$cf['type']})";
        }

        return $this->render('import/preview', [
            'title'          => 'Map CSV Columns',
            'entity'         => $entity,
            'tmpToken'       => $tmpToken,
            'headers'        => $headers,
            'sampleRows'     => $sampleRows,
            'totalRows'      => count($allRows),
            'standardFields' => $standardFields,
            'customFields'   => $cfs,
            'targetFields'   => $targetFields,
            'workspace'      => $ctx['workspace'],
            'workspaces'     => $ctx['workspaces'],
        ]);
    }

    public function importExecute(): void
    {
        $ctx = $this->getContext();
        $wsId = $ctx['workspaceId'];
        $body = $this->request->getBody();

        $tmpToken = (string)($body['tmp_token'] ?? '');
        $tmpPath = sys_get_temp_dir() . "/crx_import_{$tmpToken}.json";

        if (!file_exists($tmpPath)) {
            $this->session->setFlash('error', 'Import session expired. Please upload the CSV file again.');
            $this->redirect('/import');
            return;
        }

        $importData = json_decode((string)file_get_contents($tmpPath), true);
        @unlink($tmpPath);

        if (!$importData || empty($importData['rows'])) {
            $this->session->setFlash('error', 'Invalid import data.');
            $this->redirect('/import');
            return;
        }

        $entity = $importData['entity'];
        $headers = $importData['headers'];
        $rows = $importData['rows'];
        $mappings = $body['map'] ?? []; // [header_index => target_field]

        // Auto-create custom fields requested during mapping
        foreach ($mappings as $colIndex => $field) {
            if (str_starts_with((string)$field, '__new_cf')) {
                $colHeader = $headers[$colIndex] ?? "Field " . ($colIndex + 1);
                $parts = explode(':', (string)$field);
                $cfType = $parts[1] ?? 'text';

                // Check for user-provided custom name override
                $overrideName = trim((string)($body['cf_custom_name'][$colIndex] ?? ''));
                $cfName = !empty($overrideName) ? $overrideName : ucwords(str_replace(['_', 'cf '], ' ', $colHeader));
                $cfCode = strtolower(trim(preg_replace('/[^A-Za-z0-9_]+/', '_', str_replace(['cf_', 'CF_'], '', $cfName)), '_'));
                if (empty($cfCode)) {
                    $cfCode = 'field_' . bin2hex(random_bytes(3));
                }

                // If type is dropdown or multi-select, auto-discover distinct options from CSV rows!
                $optionsJson = null;
                if ($cfType === 'select' || $cfType === 'multi_select') {
                    $uniqueOpts = [];
                    foreach ($rows as $r) {
                        $rawVal = trim((string)($r[$colIndex] ?? ''));
                        if ($rawVal !== '') {
                            if ($cfType === 'multi_select' && str_contains($rawVal, ',')) {
                                foreach (explode(',', $rawVal) as $item) {
                                    $item = trim($item);
                                    if ($item !== '' && !in_array($item, $uniqueOpts, true)) {
                                        $uniqueOpts[] = $item;
                                    }
                                }
                            } elseif (!in_array($rawVal, $uniqueOpts, true)) {
                                $uniqueOpts[] = $rawVal;
                            }
                        }
                        if (count($uniqueOpts) >= 40) {
                            break;
                        }
                    }
                    if (!empty($uniqueOpts)) {
                        sort($uniqueOpts);
                        $optionsJson = json_encode(array_values($uniqueOpts), JSON_UNESCAPED_UNICODE);
                    }
                }

                $existingCf = (new \App\Models\CustomField)->table()
                    ->where('workspace_id', $wsId)
                    ->where('entity_type', $entity)
                    ->where('code', $cfCode)
                    ->first();

                if (!$existingCf) {
                    (new \App\Models\CustomField)->table()->insert([
                        'workspace_id' => $wsId,
                        'entity_type'  => $entity,
                        'name'         => $cfName,
                        'code'         => $cfCode,
                        'type'         => $cfType,
                        'options'      => $optionsJson,
                        'is_required'  => 0,
                        'order_column' => 0,
                        'created_at'   => date('Y-m-d H:i:s'),
                        'updated_at'   => date('Y-m-d H:i:s'),
                    ]);
                }
                $mappings[$colIndex] = "cf_{$cfCode}";
            }
        }

        $cfService = new CustomFieldService();
        $importedCount = 0;
        $skippedCount = 0;

        foreach ($rows as $row) {
            $record = [];
            $customFieldsInput = [];

            foreach ($mappings as $colIndex => $field) {
                if (empty($field) || $field === '__skip__') {
                    continue;
                }
                $val = trim((string)($row[$colIndex] ?? ''));
                if (str_starts_with($field, 'cf_')) {
                    $customFieldsInput[$field] = $val;
                } else {
                    $record[$field] = $val;
                }
            }

            if ($entity === 'companies') {
                if (empty($record['name'])) {
                    $skippedCount++;
                    continue;
                }
                $data = [
                    'workspace_id'     => $wsId,
                    'name'             => $record['name'],
                    'domain'           => $record['domain'] ?? null,
                    'industry'         => $record['industry'] ?? null,
                    'size'             => $record['size'] ?? null,
                    'phone'            => $record['phone'] ?? null,
                    'email'            => $record['email'] ?? null,
                    'website'          => $record['website'] ?? null,
                    'address'          => $record['address'] ?? null,
                    'city'             => $record['city'] ?? null,
                    'state'            => $record['state'] ?? null,
                    'postal_code'      => $record['postal_code'] ?? null,
                    'country'          => $record['country'] ?? null,
                    'annual_revenue'   => !empty($record['annual_revenue']) ? (float)$record['annual_revenue'] : null,
                    'description'      => $record['description'] ?? null,
                    'assigned_user_id' => $ctx['userId'],
                    'created_at'       => date('Y-m-d H:i:s'),
                    'updated_at'       => date('Y-m-d H:i:s'),
                ];
                $id = (new Company)->table()->insertGetId($data);
                if (!empty($customFieldsInput)) {
                    $cfService->saveValues($wsId, 'companies', $id, $customFieldsInput);
                }
                $importedCount++;
            } elseif ($entity === 'people') {
                if (empty($record['first_name'])) {
                    $skippedCount++;
                    continue;
                }
                $data = [
                    'workspace_id'     => $wsId,
                    'company_id'       => !empty($record['company_id']) ? (int)$record['company_id'] : null,
                    'first_name'       => $record['first_name'],
                    'last_name'        => $record['last_name'] ?? null,
                    'email'            => $record['email'] ?? null,
                    'phone'            => $record['phone'] ?? null,
                    'job_title'        => $record['job_title'] ?? null,
                    'status'           => $record['status'] ?? 'lead',
                    'address'          => $record['address'] ?? null,
                    'city'             => $record['city'] ?? null,
                    'state'            => $record['state'] ?? null,
                    'postal_code'      => $record['postal_code'] ?? null,
                    'country'          => $record['country'] ?? null,
                    'assigned_user_id' => $ctx['userId'],
                    'created_at'       => date('Y-m-d H:i:s'),
                    'updated_at'       => date('Y-m-d H:i:s'),
                ];
                $id = (new Person)->table()->insertGetId($data);
                if (!empty($customFieldsInput)) {
                    $cfService->saveValues($wsId, 'people', $id, $customFieldsInput);
                }
                $importedCount++;
            } elseif ($entity === 'opportunities') {
                if (empty($record['name'])) {
                    $skippedCount++;
                    continue;
                }
                $data = [
                    'workspace_id'        => $wsId,
                    'company_id'          => !empty($record['company_id']) ? (int)$record['company_id'] : null,
                    'person_id'           => !empty($record['person_id']) ? (int)$record['person_id'] : null,
                    'name'                => $record['name'],
                    'amount'              => !empty($record['amount']) ? (float)$record['amount'] : 0.00,
                    'currency'            => $record['currency'] ?? 'USD',
                    'stage'               => $record['stage'] ?? 'lead',
                    'probability'         => isset($record['probability']) ? (int)$record['probability'] : 20,
                    'expected_close_date' => !empty($record['expected_close_date']) ? $record['expected_close_date'] : null,
                    'status'              => $record['status'] ?? 'open',
                    'assigned_user_id'    => $ctx['userId'],
                    'created_at'          => date('Y-m-d H:i:s'),
                    'updated_at'          => date('Y-m-d H:i:s'),
                ];
                $id = (new Opportunity)->table()->insertGetId($data);
                if (!empty($customFieldsInput)) {
                    $cfService->saveValues($wsId, 'opportunities', $id, $customFieldsInput);
                }
                $importedCount++;
            } elseif ($entity === 'tasks') {
                if (empty($record['title'])) {
                    $skippedCount++;
                    continue;
                }
                $data = [
                    'workspace_id'     => $wsId,
                    'title'            => $record['title'],
                    'description'      => $record['description'] ?? null,
                    'due_date'         => !empty($record['due_date']) ? $record['due_date'] : null,
                    'priority'         => $record['priority'] ?? 'medium',
                    'status'           => $record['status'] ?? 'pending',
                    'entity_type'      => $record['entity_type'] ?? null,
                    'entity_id'        => !empty($record['entity_id']) ? (int)$record['entity_id'] : null,
                    'assigned_user_id' => $ctx['userId'],
                    'created_at'       => date('Y-m-d H:i:s'),
                    'updated_at'       => date('Y-m-d H:i:s'),
                ];
                $id = (new Task)->table()->insertGetId($data);
                if (!empty($customFieldsInput)) {
                    $cfService->saveValues($wsId, 'tasks', $id, $customFieldsInput);
                }
                $importedCount++;
            } elseif ($entity === 'quotes') {
                if (empty($record['title'])) {
                    $skippedCount++;
                    continue;
                }
                $data = [
                    'workspace_id'    => $wsId,
                    'quote_number'    => !empty($record['quote_number']) ? $record['quote_number'] : 'Q-' . strtoupper(bin2hex(random_bytes(3))),
                    'title'           => $record['title'],
                    'opportunity_id'  => !empty($record['opportunity_id']) ? (int)$record['opportunity_id'] : null,
                    'person_id'       => !empty($record['person_id']) ? (int)$record['person_id'] : null,
                    'company_id'      => !empty($record['company_id']) ? (int)$record['company_id'] : null,
                    'status'          => $record['status'] ?? 'draft',
                    'subtotal'        => !empty($record['subtotal']) ? (float)$record['subtotal'] : 0.00,
                    'tax_percent'     => !empty($record['tax_percent']) ? (float)$record['tax_percent'] : 0.00,
                    'tax_amount'      => !empty($record['tax_amount']) ? (float)$record['tax_amount'] : 0.00,
                    'discount_amount' => !empty($record['discount_amount']) ? (float)$record['discount_amount'] : 0.00,
                    'total_amount'    => !empty($record['total_amount']) ? (float)$record['total_amount'] : 0.00,
                    'currency'        => $record['currency'] ?? 'USD',
                    'public_token'    => bin2hex(random_bytes(16)),
                    'valid_until'     => !empty($record['valid_until']) ? $record['valid_until'] : null,
                    'notes'           => $record['notes'] ?? null,
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ];
                $id = (new \App\Models\Quote)->table()->insertGetId($data);
                if (!empty($customFieldsInput)) {
                    $cfService->saveValues($wsId, 'quotes', $id, $customFieldsInput);
                }
                $importedCount++;
            }
        }

        // Record activity
        try {
            (new Activity)->table()->insert([
                'workspace_id' => $wsId,
                'user_id'      => $ctx['userId'],
                'action'       => 'imported',
                'entity_type'  => $entity,
                'entity_id'    => 0,
                'description'  => "Imported {$importedCount} {$entity} from CSV ({$skippedCount} skipped)",
                'metadata'     => json_encode(['imported' => $importedCount, 'skipped' => $skippedCount]),
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {}

        $this->session->setFlash('success', "Import complete: {$importedCount} records imported successfully" . ($skippedCount > 0 ? " ({$skippedCount} skipped due to missing required fields)." : "!"));
        $this->redirect("/{$entity}");
    }
}
