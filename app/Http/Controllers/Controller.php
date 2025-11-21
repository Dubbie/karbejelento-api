<?php

namespace App\Http\Controllers;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class Controller
{
    /**
     * Format a paginated result set using the provided resource.
     */
    protected function paginatedResponse(array $paginated, string $resourceClass): array
    {
        /** @var JsonResource $resourceClass */
        $resourceCollection = $resourceClass::collection($paginated['data']);

        return [
            'data' => $resourceCollection->toArray(request()),
            'meta' => $paginated['meta'],
        ];
    }
}
