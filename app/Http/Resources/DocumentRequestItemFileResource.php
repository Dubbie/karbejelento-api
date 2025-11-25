<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\DocumentRequestItemFile */
class DocumentRequestItemFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'file_name' => $this->file_name_original,
            'mime_type' => $this->file_mime_type,
            'size_bytes' => $this->file_size_bytes,
            'uploaded_at' => $this->uploaded_at?->toIso8601String(),
            'download_url' => $this->downloadUrl(),
        ];
    }

    private function downloadUrl(): ?string
    {
        $path = $this->file_path;

        if (!$path) {
            return null;
        }

        $disk = config('filesystems.public.visibility') === 'public' ? 'public' : config('filesystems.default');

        return Storage::disk($disk)->url($path);
    }
}
