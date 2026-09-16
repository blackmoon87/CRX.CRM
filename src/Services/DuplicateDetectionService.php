<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Interaction;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\Person;
use App\Models\Task;

class DuplicateDetectionService
{
    /**
     * Find potential duplicate contacts by email, phone, or exact full name.
     */
    public static function findDuplicateContacts(int $workspaceId): array
    {
        $people = (new Person)->table()
            ->where('workspace_id', $workspaceId)
            ->where('deleted_at', null)
            ->orderBy('id', 'ASC')
            ->get();

        $duplicates = [];
        $emailMap = [];
        $nameMap = [];

        foreach ($people as $p) {
            $email = strtolower(trim((string)($p['email'] ?? '')));
            $fullName = strtolower(trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')));

            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                if (isset($emailMap[$email])) {
                    $duplicates[] = [
                        'primary'      => $emailMap[$email],
                        'duplicate'    => $p,
                        'match_reason' => "Identical Email: {$email}",
                        'field'        => 'email',
                    ];
                } else {
                    $emailMap[$email] = $p;
                }
            }

            if ($fullName !== '' && strlen($fullName) > 3) {
                if (isset($nameMap[$fullName])) {
                    // Avoid duplicating if already matched by email
                    $alreadyMatched = false;
                    foreach ($duplicates as $d) {
                        if ($d['duplicate']['id'] === $p['id']) {
                            $alreadyMatched = true;
                            break;
                        }
                    }
                    if (!$alreadyMatched) {
                        $duplicates[] = [
                            'primary'      => $nameMap[$fullName],
                            'duplicate'    => $p,
                            'match_reason' => "Matching Name: {$p['first_name']} {$p['last_name']}",
                            'field'        => 'name',
                        ];
                    }
                } else {
                    $nameMap[$fullName] = $p;
                }
            }
        }

        return $duplicates;
    }

    /**
     * Find potential duplicate companies by domain or normalized name.
     */
    public static function findDuplicateCompanies(int $workspaceId): array
    {
        $companies = (new Company)->table()
            ->where('workspace_id', $workspaceId)
            ->where('deleted_at', null)
            ->orderBy('id', 'ASC')
            ->get();

        $duplicates = [];
        $domainMap = [];
        $nameMap = [];

        foreach ($companies as $c) {
            $domain = strtolower(trim((string)($c['domain'] ?? '')));
            $name = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', (string)($c['name'] ?? ''))));

            if ($domain !== '') {
                // Normalize domain (strip www., http://, https://)
                $cleanDomain = preg_replace('/^(https?:\/\/)?(www\.)?/', '', $domain);
                $cleanDomain = explode('/', $cleanDomain)[0];

                if (isset($domainMap[$cleanDomain])) {
                    $duplicates[] = [
                        'primary'      => $domainMap[$cleanDomain],
                        'duplicate'    => $c,
                        'match_reason' => "Matching Domain: {$cleanDomain}",
                        'field'        => 'domain',
                    ];
                } else {
                    $domainMap[$cleanDomain] = $c;
                }
            }

            if ($name !== '' && strlen($name) > 3) {
                if (isset($nameMap[$name])) {
                    $alreadyMatched = false;
                    foreach ($duplicates as $d) {
                        if ($d['duplicate']['id'] === $c['id']) {
                            $alreadyMatched = true;
                            break;
                        }
                    }
                    if (!$alreadyMatched) {
                        $duplicates[] = [
                            'primary'      => $nameMap[$name],
                            'duplicate'    => $c,
                            'match_reason' => "Similar Company Name: {$c['name']}",
                            'field'        => 'name',
                        ];
                    }
                } else {
                    $nameMap[$name] = $c;
                }
            }
        }

        return $duplicates;
    }

    /**
     * Safely merge duplicate contact into primary contact.
     */
    public static function mergeContacts(int $primaryId, int $duplicateId, int $workspaceId, int $userId): bool
    {
        if ($primaryId === $duplicateId) return false;

        $primary = (new Person)->table()
            ->where('id', $primaryId)
            ->where('workspace_id', $workspaceId)
            ->where('deleted_at', null)
            ->first();

        $duplicate = (new Person)->table()
            ->where('id', $duplicateId)
            ->where('workspace_id', $workspaceId)
            ->where('deleted_at', null)
            ->first();

        if (!$primary || !$duplicate) return false;

        // 1. Fill missing fields on primary from duplicate
        $updates = [];
        $fillable = ['phone', 'job_title', 'company_id'];
        foreach ($fillable as $f) {
            if (empty($primary[$f]) && !empty($duplicate[$f])) {
                $updates[$f] = $duplicate[$f];
            }
        }
        if (!empty($updates)) {
            $updates['updated_at'] = date('Y-m-d H:i:s');
            (new Person)->table()->where('id', $primaryId)->update($updates);
        }

        // 2. Re-parent Opportunities
        (new Opportunity)->table()
            ->where('person_id', $duplicateId)
            ->where('workspace_id', $workspaceId)
            ->update(['person_id' => $primaryId]);

        // 3. Re-parent Tasks
        (new Task)->table()
            ->where('entity_type', 'people')
            ->where('entity_id', $duplicateId)
            ->where('workspace_id', $workspaceId)
            ->update(['entity_id' => $primaryId]);

        // 4. Re-parent Notes
        (new Note)->table()
            ->where('entity_type', 'people')
            ->where('entity_id', $duplicateId)
            ->where('workspace_id', $workspaceId)
            ->update(['entity_id' => $primaryId]);

        // 5. Re-parent Interactions
        (new Interaction)->table()
            ->where('entity_type', 'people')
            ->where('entity_id', $duplicateId)
            ->where('workspace_id', $workspaceId)
            ->update(['entity_id' => $primaryId]);

        // 6. Soft-delete duplicate contact
        (new Person)->table()->where('id', $duplicateId)->update([
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // 7. Audit Trail
        ActivityLogger::log(
            $workspaceId,
            $userId,
            'merged',
            'people',
            $primaryId,
            "Merged duplicate contact '{$duplicate['first_name']} {$duplicate['last_name']}' (#{$duplicateId}) into primary contact (#{$primaryId})."
        );

        return true;
    }

    /**
     * Safely merge duplicate company into primary company.
     */
    public static function mergeCompanies(int $primaryId, int $duplicateId, int $workspaceId, int $userId): bool
    {
        if ($primaryId === $duplicateId) return false;

        $primary = (new Company)->table()
            ->where('id', $primaryId)
            ->where('workspace_id', $workspaceId)
            ->where('deleted_at', null)
            ->first();

        $duplicate = (new Company)->table()
            ->where('id', $duplicateId)
            ->where('workspace_id', $workspaceId)
            ->where('deleted_at', null)
            ->first();

        if (!$primary || !$duplicate) return false;

        // 1. Fill missing fields
        $updates = [];
        $fillable = ['domain', 'industry', 'annual_revenue', 'phone', 'email', 'website', 'city', 'country'];
        foreach ($fillable as $f) {
            if (empty($primary[$f]) && !empty($duplicate[$f])) {
                $updates[$f] = $duplicate[$f];
            }
        }
        if (!empty($updates)) {
            $updates['updated_at'] = date('Y-m-d H:i:s');
            (new Company)->table()->where('id', $primaryId)->update($updates);
        }

        // 2. Re-parent People
        (new Person)->table()
            ->where('company_id', $duplicateId)
            ->where('workspace_id', $workspaceId)
            ->update(['company_id' => $primaryId]);

        // 3. Re-parent Opportunities
        (new Opportunity)->table()
            ->where('company_id', $duplicateId)
            ->where('workspace_id', $workspaceId)
            ->update(['company_id' => $primaryId]);

        // 4. Re-parent Tasks
        (new Task)->table()
            ->where('entity_type', 'companies')
            ->where('entity_id', $duplicateId)
            ->where('workspace_id', $workspaceId)
            ->update(['entity_id' => $primaryId]);

        // 5. Re-parent Notes
        (new Note)->table()
            ->where('entity_type', 'companies')
            ->where('entity_id', $duplicateId)
            ->where('workspace_id', $workspaceId)
            ->update(['entity_id' => $primaryId]);

        // 6. Re-parent Interactions
        (new Interaction)->table()
            ->where('entity_type', 'companies')
            ->where('entity_id', $duplicateId)
            ->where('workspace_id', $workspaceId)
            ->update(['entity_id' => $primaryId]);

        // 7. Soft-delete duplicate company
        (new Company)->table()->where('id', $duplicateId)->update([
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // 8. Audit Trail
        ActivityLogger::log(
            $workspaceId,
            $userId,
            'merged',
            'companies',
            $primaryId,
            "Merged duplicate company '{$duplicate['name']}' (#{$duplicateId}) into primary company (#{$primaryId})."
        );

        return true;
    }
}
