<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PaginationService
{
    /**
     * The main method to paginate a query.
     */
    public static function paginate(Builder $query, Request $request, array $options): array
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
        return [
            'data' => $results,
            'meta' => [
                'totalItems' => $totalItems,
                'itemCount' => $results->count(),
                'itemsPerPage' => $limit,
                'totalPages' => ceil($totalItems / $limit),
                'currentPage' => $page,
            ],
        ];
    }

    private static function applySorting(Builder $query, Request $request, array $sortableFields): void
    {
        $sort = $request->input('sort');

        if (!$sort) {
            $query->orderBy('created_at', 'desc'); // Default sort
            return;
        }

        [$field, $direction] = array_pad(explode(':', $sort), 2, 'asc');
        $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';

        if (in_array($field, $sortableFields)) {
            $query->orderBy($field, $direction);
        }
    }

    private static function applyFiltering(Builder $query, Request $request, array $filterableFields): void
    {
        $filters = $request->input('filter', []);
        if (is_string($filters)) {
            $filters = [$filters];
        }

        foreach ($filters as $filter) {
            [$field, $op, $value] = array_pad(explode(':', $filter, 3), 3, null);

            if (!$field || !$op || $value === null) continue;
            if (!in_array($field, $filterableFields)) continue;

            switch (strtolower($op)) {
                case 'eq':
                    $query->where($field, '=', $value);
                    break;
                case 'neq':
                    $query->where($field, '!=', $value);
                    break;
                case 'like':
                    $query->where($field, 'LIKE', "%{$value}%");
                    break;
                case 'in':
                    $query->whereIn($field, explode(',', $value));
                    break;
            }
        }
    }
}
