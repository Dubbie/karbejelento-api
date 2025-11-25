<?php

namespace Tests\Feature;

use App\Models\DocumentRequest;
use App\Models\DocumentRequestItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentRequestPublicControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_document_request_when_token_is_valid(): void
    {
        $documentRequest = DocumentRequest::factory()
            ->has(DocumentRequestItem::factory()->count(2), 'items')
            ->create();

        $response = $this->getJson(
            '/api/v1/public/document-requests/' . $documentRequest->uuid . '?token=' . $documentRequest->public_token
        );

        $response->assertStatus(200)
            ->assertJsonPath('uuid', $documentRequest->uuid)
            ->assertJsonCount(2, 'items');
    }

    public function test_show_rejects_invalid_token(): void
    {
        $documentRequest = DocumentRequest::factory()->create();

        $response = $this->getJson('/api/v1/public/document-requests/' . $documentRequest->uuid . '?token=invalid');

        $response->assertStatus(403);
    }

    public function test_store_item_file_saves_upload_and_marks_request_fulfilled(): void
    {
        Storage::fake('public');

        $documentRequest = DocumentRequest::factory()
            ->has(DocumentRequestItem::factory()->count(1), 'items')
            ->create();

        $item = $documentRequest->items()->first();

        $file = UploadedFile::fake()->create('id-card.jpg', 500, 'image/jpeg');

        $response = $this->postJson(
            '/api/v1/public/document-requests/' . $documentRequest->uuid . '/items/' . $item->uuid . '/files',
            [
                'token' => $documentRequest->public_token,
                'file' => $file,
            ]
        );

        $response->assertStatus(201)
            ->assertJsonPath('file_name', 'id-card.jpg');

        $documentRequest->refresh();
        $this->assertTrue($documentRequest->is_fulfilled);

        Storage::disk('public')->assertExists($documentRequest->items()->first()->files()->first()->file_path);
    }

    public function test_store_item_file_accepts_multiple_files(): void
    {
        Storage::fake('public');

        $documentRequest = DocumentRequest::factory()
            ->has(DocumentRequestItem::factory()->count(1), 'items')
            ->create();

        $item = $documentRequest->items()->first();

        $files = [
            UploadedFile::fake()->create('id-front.jpg', 500, 'image/jpeg'),
            UploadedFile::fake()->create('id-back.jpg', 480, 'image/jpeg'),
        ];

        $response = $this->postJson(
            '/api/v1/public/document-requests/' . $documentRequest->uuid . '/items/' . $item->uuid . '/files',
            [
                'token' => $documentRequest->public_token,
                'files' => $files,
            ]
        );

        $response->assertStatus(201)
            ->assertJsonCount(2)
            ->assertJsonPath('0.file_name', 'id-front.jpg')
            ->assertJsonPath('1.file_name', 'id-back.jpg');

        $documentRequest->refresh();
        $this->assertTrue($documentRequest->is_fulfilled);

        $this->assertCount(2, $documentRequest->items()->first()->files);
    }
}
