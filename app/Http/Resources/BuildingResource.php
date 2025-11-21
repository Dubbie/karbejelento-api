<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BuildingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'bond_number' => $this->bond_number,
            'insurer' => $this->insurer,
            'city' => $this->city,
            'postcode' => $this->postcode,
            'street' => [
                'name' => $this->street_name,
                'type' => $this->street_type,
                'number' => $this->street_number,
            ],
            'formatted_address' => $this->formatted_address,
            'is_archived' => (bool) $this->is_archived,
            'current_customer' => $this->when(
                $this->current_customer,
                fn () => UserResource::make($this->current_customer)
            ),
            'management_history' => $this->whenLoaded('managementHistory', function () {
                return BuildingManagementResource::collection($this->managementHistory);
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
