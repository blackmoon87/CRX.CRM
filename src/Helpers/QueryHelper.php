<?php

declare(strict_types=1);

namespace App\Helpers;

class QueryHelper
{
    /**
     * Build a query string from current GET parameters with overrides.
     *
     * @param array<string, mixed> $overrides
     * @param array<string> $remove
     */
    public static function buildQuery(array $overrides = [], array $remove = []): string
    {
        $params = $_GET;
        foreach ($remove as $key) {
            unset($params[$key]);
        }
        foreach ($overrides as $k => $v) {
            if ($v === null || $v === '') {
                unset($params[$k]);
            } else {
                $params[$k] = $v;
            }
        }
        return empty($params) ? '' : '?' . http_build_query($params);
    }

    /**
     * Generate URL for sorting by a specific column.
     */
    public static function sortUrl(string $column, string $currentSort, string $currentDir): string
    {
        $dir = ($currentSort === $column && strtolower($currentDir) === 'asc') ? 'desc' : 'asc';
        return self::buildQuery(['sort' => $column, 'dir' => $dir, 'page' => 1]);
    }

    /**
     * Get sorting icon indicator.
     */
    public static function sortIndicator(string $column, string $currentSort, string $currentDir): string
    {
        if ($currentSort !== $column) {
            return '<span style="opacity:0.35;font-size:0.75rem;margin-left:4px;">⇅</span>';
        }
        return strtolower($currentDir) === 'asc'
            ? '<span style="color:var(--primary);font-size:0.8rem;margin-left:4px;font-weight:900;">▲</span>'
            : '<span style="color:var(--primary);font-size:0.8rem;margin-left:4px;font-weight:900;">▼</span>';
    }

    /**
     * Generate URL for a specific page.
     */
    public static function pageUrl(int $page): string
    {
        return self::buildQuery(['page' => $page]);
    }

    /**
     * Generate URL that removes a specific filter key while keeping everything else.
     */
    public static function removeFilterUrl(string $filterKey): string
    {
        $params = $_GET;
        unset($params[$filterKey]);
        if (isset($params['filter'][$filterKey])) {
            unset($params['filter'][$filterKey]);
            if (empty($params['filter'])) {
                unset($params['filter']);
            }
        }
        if (($params['filter_col'] ?? '') === $filterKey) {
            unset($params['filter_col'], $params['filter_val'], $params['filter_op']);
        }
        $params['page'] = 1;
        return empty($params) ? '' : '?' . http_build_query($params);
    }
}
