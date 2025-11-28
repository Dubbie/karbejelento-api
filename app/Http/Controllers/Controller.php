<?php

namespace App\Http\Controllers;

use App\Services\PaginatedResult;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class Controller
{
    /**
     * Format a paginated result set using the provided resource.
     */
    protected function paginatedResponse(PaginatedResult $paginated, string $resourceClass): array
    {
        /** @var JsonResource $resourceClass */
        $resourceCollection = $resourceClass::collection($paginated->data());

        return [
            'data' => $resourceCollection->toArray(request()),
            'meta' => $paginated->meta()->toArray(),
        ];
    }
}
