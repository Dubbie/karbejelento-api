<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BuildingManagementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'customer' => $this->whenLoaded('customer', fn () => UserResource::make($this->customer)),
            'insurer' => $this->whenLoaded('insurer', fn () => InsurerResource::make($this->insurer)),
        ];
    }
}
