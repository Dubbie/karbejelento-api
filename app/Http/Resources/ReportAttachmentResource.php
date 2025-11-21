<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportAttachmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'file_name' => $this->file_name_original,
            'file_path' => $this->file_path,
            'mime_type' => $this->file_mime_type,
            'size_bytes' => $this->file_size_bytes,
            'category' => $this->category,
            'uploaded_by' => $this->whenLoaded('uploadedBy', fn () => UserResource::make($this->uploadedBy)),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
