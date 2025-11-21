<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportStatusHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'comment' => $this->comment,
            'status' => $this->whenLoaded('status', fn () => StatusResource::make($this->status)),
            'sub_status' => $this->whenLoaded('subStatus', fn () => SubStatusResource::make($this->subStatus)),
            'user' => $this->whenLoaded('user', fn () => UserResource::make($this->user)),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
