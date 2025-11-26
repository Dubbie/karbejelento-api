<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\DocumentRequestItem */
class DocumentRequestItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'label' => $this->label,
            'note' => $this->note,
            'position' => $this->position,
            'files' => DocumentRequestItemFileResource::collection($this->whenLoaded('files')),
        ];
    }
}
