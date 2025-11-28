<?php

namespace App\Services;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use JsonSerializable;

/**
 * @template TModel of array|object
 * @implements Arrayable<array{data: array<int, TModel>, meta: PaginationMeta}>
 */
class PaginatedResult implements Arrayable, JsonSerializable
{
    /**
     * @param array<int, TModel> $data
     */
    public function __construct(
        private readonly array $data,
        private readonly PaginationMeta $meta,
    ) {
    }

    /**
     * Helper factory to build a paginated result using common pagination metrics.
     *
     * @template TCollectionItem of array|object
     * @param Collection<int, TCollectionItem> $data
     * @return self<TCollectionItem>
     */
    public static function fromMetrics(Collection $data, int $totalItems, int $itemsPerPage, int $currentPage): self
    {
        $safeItemsPerPage = $itemsPerPage > 0 ? $itemsPerPage : 1;

        return new self(
            $data->values()->all(),
            new PaginationMeta(
                totalItems: $totalItems,
                itemCount: $data->count(),
                itemsPerPage: $itemsPerPage,
                totalPages: (int) ceil($totalItems / $safeItemsPerPage),
                currentPage: $currentPage
            )
        );
    }

    /**
     * @return array<int, TModel>
     */
    public function data(): array
    {
        return $this->data;
    }

    public function meta(): PaginationMeta
    {
        return $this->meta;
    }

    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'meta' => $this->meta->toArray(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
