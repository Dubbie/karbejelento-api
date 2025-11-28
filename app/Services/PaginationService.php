<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PaginationService
{
    /**
     * The main method to paginate a query.
     */
    public static function paginate(Builder $query, Request $request, array $options): PaginatedResult
    {
        // 1. Apply Filtering
        self::applyFiltering($query, $request, $options['filterableFields'] ?? []);

        // 2. Apply Sorting
        self::applySorting($query, $request, $options['sortableFields'] ?? []);

        // 3. Get total count AFTER filtering
        $totalItems = $query->count();

        // 4. Apply Pagination
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);

        $results = $query->forPage($page, $limit)->get();

        // 5. Format the final result
        return PaginatedResult::fromMetrics($results, $totalItems, $limit, $page);
    }

    private static function applySorting(Builder $query, Request $request, array $sortableFields): void
    {
        $fieldMap = self::normalizeFieldMap($sortableFields);
        $sort = $request->input('sort');

        if (!$sort) {
            $defaultColumn = $query->getModel()->getTable() . '.created_at';
            $query->orderBy($defaultColumn, 'desc'); // Default sort
            return;
        }

        [$field, $direction] = array_pad(explode(':', $sort), 2, 'asc');
        $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';

        if (isset($fieldMap[$field])) {
            $query->orderBy($fieldMap[$field], $direction);
            return;
        }

        $defaultColumn = $query->getModel()->getTable() . '.created_at';
        $query->orderBy($defaultColumn, 'desc');
    }

    private static function applyFiltering(Builder $query, Request $request, array $filterableFields): void
    {
        $fieldMap = self::normalizeFieldMap($filterableFields);
        $filters = $request->input('filter', []);
        if (is_string($filters)) {
            $filters = [$filters];
        }

        foreach ($filters as $filter) {
            [$field, $op, $value] = array_pad(explode(':', $filter, 3), 3, null);

            if (!$field || !$op || $value === null) continue;
            if (!isset($fieldMap[$field])) continue;

            $column = $fieldMap[$field];

            switch (strtolower($op)) {
                case 'eq':
                    $query->where($column, '=', $value);
                    break;
                case 'neq':
                    $query->where($column, '!=', $value);
                    break;
                case 'like':
                    $query->where($column, 'LIKE', "%{$value}%");
                    break;
                case 'in':
                    $query->whereIn($column, explode(',', $value));
                    break;
            }
        }
    }

    /**
     * Normalize sortable/filterable definitions into an associative array.
     *
     * @param array<int|string, string> $fields
     */
    private static function normalizeFieldMap(array $fields): array
    {
        $map = [];

        foreach ($fields as $key => $value) {
            if (is_int($key)) {
                $map[$value] = $value;
            } else {
                $map[$key] = $value;
            }
        }

        return $map;
    }
}
