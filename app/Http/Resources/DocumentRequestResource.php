<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\DocumentRequest */
class DocumentRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'email_title' => $this->email_title,
            'email_body' => $this->email_body,
            'requested_documents' => $this->requested_documents,
            'other_document_note' => $this->other_document_note,
            'is_fulfilled' => $this->is_fulfilled,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'items' => DocumentRequestItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
