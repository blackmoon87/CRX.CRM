<?php

declare(strict_types=1);

namespace App\Services;

use Spartan\QueryBuilder;

class FilterService
{
    /**
     * Extract all active column filters from request parameters.
     * Supports:
     *  1. Direct params: ?industry=SaaS&city=Boston&amount_min=50000
     *  2. Array syntax: ?filter[industry]=SaaS&filter[job_title]=Architect
     *  3. Dynamic single filter: ?filter_col=job_title&filter_op=contains&filter_val=Director
     *
     * @param array<string, mixed> $queryParams Usually $_GET
     * @param array<string, array{type: string, label: string}> $allowedColumns
     * @return array<string, array{column: string, operator: string, value: mixed, label: string, type: string}>
     */
    public static function extractFilters(array $queryParams, array $allowedColumns): array
    {
        $filters = [];

        // 1. Check array syntax: filter[col] = val or filter[col_op] = val
        if (!empty($queryParams['filter']) && is_array($queryParams['filter'])) {
            foreach ($queryParams['filter'] as $key => $val) {
                if ($val === null || trim((string)$val) === '') continue;
                $parsed = self::parseKeyAndOp((string)$key, $val, $allowedColumns);
                if ($parsed) {
                    $filters[$parsed['filter_key']] = $parsed;
                }
            }
        }

        // 2. Check dynamic filter: filter_col, filter_val, filter_op OR fallback if filter_val is provided without filter_col
        $filterVal = isset($queryParams['filter_val']) ? trim((string)$queryParams['filter_val']) : '';
        if ($filterVal === '' && !empty($queryParams['search'])) {
            $filterVal = trim((string)$queryParams['search']);
        }

        if ($filterVal !== '') {
            $col = trim((string)($queryParams['filter_col'] ?? 'all'));
            if ($col === '' || $col === 'all') {
                $col = 'all';
            }
            $op  = trim((string)($queryParams['filter_op'] ?? 'contains'));
            $normOp = self::normalizeOp($op);

            if ($col === 'all') {
                $filters['all'] = [
                    'filter_key' => 'all',
                    'column'     => 'all',
                    'operator'   => $normOp,
                    'value'      => $filterVal,
                    'label'      => 'Any Column',
                    'type'       => 'string',
                ];
            } elseif (isset($allowedColumns[$col])) {
                $keySuffix = match($normOp) {
                    '>=' => '_gte',
                    '<=' => '_lte',
                    '>' => '_gt',
                    '<' => '_lt',
                    '=' => '_exact',
                    'starts_with' => '_starts_with',
                    'ends_with' => '_ends_with',
                    default => '',
                };
                $filterKey = $col . $keySuffix;
                $filters[$filterKey] = [
                    'filter_key' => $filterKey,
                    'column'     => $col,
                    'operator'   => $normOp,
                    'value'      => $filterVal,
                    'label'      => $allowedColumns[$col]['label'] ?? ucfirst(str_replace('_', ' ', $col)),
                    'type'       => $allowedColumns[$col]['type'] ?? 'string',
                ];
            }
        }

        // 3. Check direct query params (e.g. ?job_title=Director, ?domain=apple.com, ?city=Austin)
        // Skip system reserved params: page, per_page, sort, dir, search, view, pipeline_id
        $reserved = ['page', 'per_page', 'sort', 'dir', 'search', 'view', 'pipeline_id', 'filter', 'filter_col', 'filter_val', 'filter_op'];
        foreach ($queryParams as $key => $val) {
            if (in_array($key, $reserved, true)) continue;
            if ($val === null || trim((string)$val) === '' || $val === 'all') continue;
            $parsed = self::parseKeyAndOp((string)$key, $val, $allowedColumns);
            if ($parsed && !isset($filters[$parsed['filter_key']])) {
                $filters[$parsed['filter_key']] = $parsed;
            }
        }

        return $filters;
    }

    /**
     * Apply active filters to QueryBuilder.
     *
     * @param QueryBuilder $query
     * @param array<string, array{column: string, operator: string, value: mixed, label: string, type: string}> $activeFilters
     * @param string $tablePrefix e.g. 'companies.' or 'people.'
     */
    /**
     * Apply active filters to QueryBuilder.
     *
     * @param QueryBuilder $query
     * @param array<string, array{column: string, operator: string, value: mixed, label: string, type: string}> $activeFilters
     * @param string $tablePrefix e.g. 'companies.' or 'people.'
     * @param array<string, array{label: string, type: string}> $allowedColumns
     */
    public static function apply(QueryBuilder $query, array $activeFilters, string $tablePrefix = '', array $allowedColumns = []): QueryBuilder
    {
        foreach ($activeFilters as $f) {
            $col = $f['column'];
            $val = $f['value'];
            $op  = $f['operator'];
            $type = $f['type'] ?? 'string';

            if ($col === 'all' || $col === '') {
                $colsToSearch = !empty($allowedColumns) ? $allowedColumns : [
                    'name'        => ['type' => 'string'],
                    'first_name'  => ['type' => 'string'],
                    'last_name'   => ['type' => 'string'],
                    'email'       => ['type' => 'string'],
                    'job_title'   => ['type' => 'string'],
                    'title'       => ['type' => 'string'],
                    'description' => ['type' => 'string'],
                    'domain'      => ['type' => 'string'],
                    'industry'    => ['type' => 'string'],
                    'city'        => ['type' => 'string'],
                ];

                $query->where(function(QueryBuilder $q) use ($val, $op, $colsToSearch, $tablePrefix) {
                    $first = true;
                    foreach ($colsToSearch as $cKey => $cMeta) {
                        if (($cMeta['type'] ?? 'string') === 'string') {
                            $targetCol = ($tablePrefix !== '' && !str_contains($cKey, '.')) ? "{$tablePrefix}{$cKey}" : $cKey;
                            if ($op === 'exact' || $op === '=') {
                                $first ? $q->where($targetCol, '=', $val) : $q->orWhere($targetCol, '=', $val);
                            } elseif ($op === 'starts_with') {
                                $first ? $q->where($targetCol, 'LIKE', "{$val}%") : $q->orWhere($targetCol, 'LIKE', "{$val}%");
                            } elseif ($op === 'ends_with') {
                                $first ? $q->where($targetCol, 'LIKE', "%{$val}") : $q->orWhere($targetCol, 'LIKE', "%{$val}");
                            } else {
                                $first ? $q->where($targetCol, 'LIKE', "%{$val}%") : $q->orWhere($targetCol, 'LIKE', "%{$val}%");
                            }
                            $first = false;
                        }
                    }
                });
                continue;
            }

            $colName = ($tablePrefix !== '' && !str_contains($col, '.')) ? "{$tablePrefix}{$col}" : $col;

            if ($type === 'numeric') {
                $numericVal = is_numeric($val) ? (float)$val : $val;
                $query->where($colName, $op, $numericVal);
            } elseif (in_array($op, ['>', '<', '>=', '<='], true)) {
                $query->where($colName, $op, $val);
            } elseif ($op === 'exact' || $op === '=') {
                $query->where($colName, '=', $val);
            } elseif ($op === 'starts_with') {
                $query->where($colName, 'LIKE', "{$val}%");
            } elseif ($op === 'ends_with') {
                $query->where($colName, 'LIKE', "%{$val}");
            } else {
                // Default: contains
                $query->where($colName, 'LIKE', "%{$val}%");
            }
        }

        return $query;
    }

    /**
     * Parse raw param key into column name and comparison operator.
     */
    private static function parseKeyAndOp(string $rawKey, mixed $val, array $allowedColumns): ?array
    {
        $op = 'contains';
        $col = $rawKey;

        if (str_ends_with($rawKey, '_min') || str_ends_with($rawKey, '_gte')) {
            $col = preg_replace('/_(min|gte)$/', '', $rawKey);
            $op = '>=';
        } elseif (str_ends_with($rawKey, '_max') || str_ends_with($rawKey, '_lte')) {
            $col = preg_replace('/_(max|lte)$/', '', $rawKey);
            $op = '<=';
        } elseif (str_ends_with($rawKey, '_gt')) {
            $col = preg_replace('/_gt$/', '', $rawKey);
            $op = '>';
        } elseif (str_ends_with($rawKey, '_lt')) {
            $col = preg_replace('/_lt$/', '', $rawKey);
            $op = '<';
        } elseif (str_ends_with($rawKey, '_exact') || str_ends_with($rawKey, '_eq')) {
            $col = preg_replace('/_(exact|eq)$/', '', $rawKey);
            $op = '=';
        } elseif (str_ends_with($rawKey, '_starts_with')) {
            $col = preg_replace('/_starts_with$/', '', $rawKey);
            $op = 'starts_with';
        } elseif (str_ends_with($rawKey, '_ends_with')) {
            $col = preg_replace('/_ends_with$/', '', $rawKey);
            $op = 'ends_with';
        }

        if (!isset($allowedColumns[$col])) {
            return null;
        }

        $colType = $allowedColumns[$col]['type'] ?? 'string';
        if ($colType === 'exact' && $op === 'contains') {
            $op = '=';
        }

        return [
            'filter_key' => $rawKey,
            'column'     => $col,
            'operator'   => $op,
            'value'      => $val,
            'label'      => $allowedColumns[$col]['label'] ?? ucfirst(str_replace('_', ' ', $col)),
            'type'       => $colType,
        ];
    }

    private static function normalizeOp(string $op): string
    {
        return match(strtolower(trim($op))) {
            'equals', 'eq', '=' => '=',
            'gt', '>'           => '>',
            'lt', '<'           => '<',
            'gte', '>=', 'min'  => '>=',
            'lte', '<=', 'max'  => '<=',
            'starts_with'       => 'starts_with',
            'ends_with'         => 'ends_with',
            default             => 'contains',
        };
    }
}
