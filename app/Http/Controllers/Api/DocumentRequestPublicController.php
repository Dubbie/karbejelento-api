<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentRequest\StoreDocumentRequestItemFileRequest;
use App\Http\Resources\DocumentRequestItemFileResource;
use App\Http\Resources\DocumentRequestResource;
use App\Models\DocumentRequest;
use App\Models\DocumentRequestItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class DocumentRequestPublicController extends Controller
{
    public function show(DocumentRequest $documentRequest, Request $request)
    {
        $this->ensureValidToken($documentRequest, $request->query('token'));

        $documentRequest->load(['items.files']);

        return DocumentRequestResource::make($documentRequest);
    }

    public function storeItemFile(
        StoreDocumentRequestItemFileRequest $request,
        DocumentRequest $documentRequest,
        DocumentRequestItem $documentRequestItem
    ) {
        $this->ensureValidToken($documentRequest, $request->input('token'));

        if ($documentRequestItem->document_request_id !== $documentRequest->id) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $files = collect();

        if ($single = $request->file('file')) {
            $files->push($single);
        }

        $multi = $request->file('files', []);
        if (!empty($multi)) {
            $files = $files->merge($multi);
        }

        $storedFiles = $files->map(function ($file) use ($documentRequest, $documentRequestItem) {
            $path = $file->store(
                'document-requests/' . $documentRequest->uuid . '/' . $documentRequestItem->uuid,
                'public'
            );

            return $documentRequestItem->files()->create([
                'uuid' => (string) Str::uuid(),
                'file_path' => $path,
                'file_name_original' => $file->getClientOriginalName(),
                'file_mime_type' => $file->getClientMimeType() ?? $file->getMimeType(),
                'file_size_bytes' => $file->getSize(),
                'uploaded_by_user_id' => null,
                'uploaded_at' => now(),
            ]);
        });

        $this->updateFulfilledState($documentRequest);

        $resource = $storedFiles->count() === 1
            ? DocumentRequestItemFileResource::make($storedFiles->first())
            : DocumentRequestItemFileResource::collection($storedFiles);

        return response()->json($resource, Response::HTTP_CREATED);
    }

    private function ensureValidToken(DocumentRequest $documentRequest, ?string $token): void
    {
        if (!$token || !hash_equals($documentRequest->public_token, $token)) {
            abort(Response::HTTP_FORBIDDEN, 'Invalid document request token.');
        }
    }

    private function updateFulfilledState(DocumentRequest $documentRequest): void
    {
        if ($documentRequest->is_fulfilled) {
            return;
        }

        $documentRequest->loadMissing('items.files');

        $allFulfilled = $documentRequest->items->every(fn ($item) => $item->files->isNotEmpty());

        if ($allFulfilled) {
            $documentRequest->forceFill(['is_fulfilled' => true])->save();
        }
    }
}
