<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Paginatable
{
    /**
     * The new public static method that the application will call.
     *
     * @param Request $request The current HTTP request.
     * @param array $options Contains 'sortableFields' and 'filterableFields'.
     * @return array The formatted pagination result.
     */
    public static function advancedPaginate(Request $request, array $options): array
    {
        // Start a new query for the model that uses this trait.
        // Then, call our internal pagination logic.
        return self::query()->performAdvancedPagination($request, $options);
    }

    /**
     * The actual pagination logic.
     *
     * @param Builder $query The Eloquent query builder instance.
     * @param Request $request
     * @param array $options
     * @return array
     */
    public function scopePerformAdvancedPagination(Builder $query, Request $request, array $options): array
    {
        // 1. Apply Filtering
        $this->applyFiltering($query, $request, $options['filterableFields'] ?? []);

        // 2. Apply Sorting
        $this->applySorting($query, $request, $options['sortableFields'] ?? []);

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

    private function applySorting(Builder $query, Request $request, array $sortableFields): void
    {
        $sort = $request->input('sort');

        if (!$sort) {
            $query->orderBy('created_at', 'desc'); // Default sort
            return;
        }

        [$field, $direction] = array_pad(explode(':', $sort), 2, 'asc');
        $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';

        // Security: Only allow sorting on whitelisted fields
        if (in_array($field, $sortableFields)) {
            $query->orderBy($field, $direction);
        }
    }

    private function applyFiltering(Builder $query, Request $request, array $filterableFields): void
    {
        $filters = $request->input('filter', []);
        if (is_string($filters)) {
            $filters = [$filters];
        }

        foreach ($filters as $filter) {
            [$field, $op, $value] = array_pad(explode(':', $filter, 3), 3, null);

            if (!$field || !$op || $value === null) continue;

            // Security: Only allow filtering on whitelisted fields
            if (!in_array($field, $filterableFields)) {
                continue;
            }

            // Whitelist operators for security
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
                    // Add more operators like 'gt', 'lt', etc. as needed
            }
        }
    }
}
