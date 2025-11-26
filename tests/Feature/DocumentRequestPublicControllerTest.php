<?php

namespace Tests\Feature;

use App\Constants\ReportStatus;
use App\Constants\ReportSubStatus;
use App\Models\DocumentRequest;
use App\Models\DocumentRequestItem;
use App\Models\Report;
use App\Models\Status;
use App\Models\SubStatus;
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

    public function test_fulfilling_document_request_updates_report_status(): void
    {
        Storage::fake('public');

        $status = Status::factory()->create(['name' => ReportStatus::DATA_OR_DOCUMENT_DEFICIENCY]);
        $waitingSubStatus = SubStatus::factory()->create([
            'status_id' => $status->id,
            'name' => ReportSubStatus::DEFICIENCY_WAITING_FOR_DOCUMENT_FROM_CLIENT,
        ]);
        $sentSubStatus = SubStatus::factory()->create([
            'status_id' => $status->id,
            'name' => ReportSubStatus::DEFICIENCY_DOCUMENT_SENT_TO_DAMARISK,
        ]);

        $report = Report::factory()->create([
            'status_id' => $status->id,
            'sub_status_id' => $waitingSubStatus->id,
        ]);

        $documentRequest = DocumentRequest::factory()
            ->for($report)
            ->has(DocumentRequestItem::factory()->count(1), 'items')
            ->create(['is_fulfilled' => false]);

        $item = $documentRequest->items()->first();

        $file = UploadedFile::fake()->create('proof.pdf', 200, 'application/pdf');

        $this->postJson(
            '/api/v1/public/document-requests/' . $documentRequest->uuid . '/items/' . $item->uuid . '/files',
            [
                'token' => $documentRequest->public_token,
                'file' => $file,
            ]
        )->assertCreated();

        $report->refresh();

        $this->assertEquals($status->id, $report->status_id);
        $this->assertEquals($sentSubStatus->id, $report->sub_status_id);
    }
}
