<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BuildingImportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'status' => $this->status,
            'original_filename' => $this->original_filename,
            'stored_path' => $this->stored_path,
            'totals' => [
                'total_rows' => $this->total_rows,
                'processed_rows' => $this->processed_rows,
                'successful_rows' => $this->successful_rows,
            ],
            'errors' => $this->errors,
            'user_uuid' => $this->uploader?->uuid,
            'customer_uuid' => $this->customer?->uuid,
            'uploader' => $this->whenLoaded('uploader', fn () => UserResource::make($this->uploader)),
            'customer' => $this->whenLoaded('customer', fn () => UserResource::make($this->customer)),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
