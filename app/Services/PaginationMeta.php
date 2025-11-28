<?php

namespace App\Services;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Value object describing pagination metadata.
 */
class PaginationMeta implements Arrayable, JsonSerializable
{
    public function __construct(
        private readonly int $totalItems,
        private readonly int $itemCount,
        private readonly int $itemsPerPage,
        private readonly int $totalPages,
        private readonly int $currentPage,
    ) {
    }

    public function totalItems(): int
    {
        return $this->totalItems;
    }

    public function itemCount(): int
    {
        return $this->itemCount;
    }

    public function itemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function totalPages(): int
    {
        return $this->totalPages;
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    public function toArray(): array
    {
        return [
            'totalItems' => $this->totalItems,
            'itemCount' => $this->itemCount,
            'itemsPerPage' => $this->itemsPerPage,
            'totalPages' => $this->totalPages,
            'currentPage' => $this->currentPage,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
