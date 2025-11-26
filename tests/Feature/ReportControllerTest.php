<?php

namespace Tests\Feature;

use App\Constants\DamageType;
use App\Constants\ReportStatus;
use App\Constants\ReportSubStatus;
use App\Mail\DocumentRequestMail;
use App\Models\Building;
use App\Models\BuildingManagement;
use App\Models\DocumentRequest;
use App\Models\Notifier;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\Status;
use App\Models\SubStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $customer;
    private Building $building;
    private Notifier $notifier;
    private Status $defaultStatus;
    private Status $waitingStatus;
    private SubStatus $waitingSubStatus;
    private Status $underAdministrationStatus;
    private Status $deficiencyStatus;
    private SubStatus $deficiencyWaitingSubStatus;
    private Status $closedStatus;
    private SubStatus $closedWithPaymentSubStatus;

    /**
     * Set up the common actors for all tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seedStatusHierarchy();

        // Create a hierarchy: Admin, Manager, and a Customer managed by the Manager
        $this->admin = User::factory()->admin()->create();
        $this->manager = User::factory()->manager()->create();
        $this->customer = User::factory()->customer()->create(['manager_id' => $this->manager->id]);

        // Create a building and assign the customer to it
        $this->building = Building::factory()->create();
        BuildingManagement::factory()->create([
            'building_id' => $this->building->id,
            'customer_id' => $this->customer->id,
            'insurer_id' => $this->building->insurer_id,
        ]);

        // Create a notifier for the customer
        $this->notifier = Notifier::factory()->create(['customer_id' => $this->customer->id]);
    }

    public function test_customer_can_create_a_report(): void
    {
        Sanctum::actingAs($this->customer);

        $reportData = [
            'building_uuid' => $this->building->uuid,
            'notifier_uuid' => $this->notifier->uuid,
            'damage_type' => DamageType::ROOF_STEEP,
            'estimated_cost' => '501-2000',
            'damage_description' => 'Water is leaking from the ceiling.',
            'damage_date' => '2025-08-20',
            'claimant_type' => 'resident',
            'contact_name' => 'John Doe',
            'contact_phone_number' => '555-1234',
            'damaged_building_number' => 'A1',
            'damaged_floor' => '3',
        ];

        $response = $this->postJson('/api/v1/reports', $reportData);

        $response->assertStatus(201)
            ->assertJsonFragment(['damage_description' => 'Water is leaking from the ceiling.']);

        // Assert that the report AND its initial status history were created
        $report = Report::first();
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status_id' => $this->defaultStatus->id,
        ]);
        $this->assertDatabaseHas('report_status_histories', [
            'report_id' => $report->id,
            'status_id' => $this->defaultStatus->id,
        ]);
    }

    public function test_user_can_view_a_specific_report(): void
    {
        $report = Report::factory()->create(['building_id' => $this->building->id]);
        Sanctum::actingAs($this->customer);

        $response = $this->getJson('/api/v1/reports/' . $report->uuid);

        $response->assertStatus(200)
            ->assertJsonFragment(['uuid' => $report->uuid]);
    }

    public function test_manager_can_change_report_status(): void
    {
        $report = Report::factory()->create([
            'building_id' => $this->building->id,
            'status_id' => $this->defaultStatus->id,
            'sub_status_id' => null,
        ]);
        Sanctum::actingAs($this->manager);

        $updateData = [
            'status' => $this->waitingStatus->name,
            'sub_status' => $this->waitingSubStatus->name,
        ];
        $response = $this->postJson('/api/v1/reports/' . $report->uuid . '/status', $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('status.name', $this->waitingStatus->name)
            ->assertJsonPath('sub_status.name', $this->waitingSubStatus->name);

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status_id' => $this->waitingStatus->id,
            'sub_status_id' => $this->waitingSubStatus->id,
        ]);
        $this->assertDatabaseHas('report_status_histories', [
            'report_id' => $report->id,
            'status_id' => $this->waitingStatus->id,
            'sub_status_id' => $this->waitingSubStatus->id,
        ]);
    }

    public function test_customer_cannot_update_a_report(): void
    {
        $report = Report::factory()->create(['building_id' => $this->building->id]);
        Sanctum::actingAs($this->customer);

        $response = $this->postJson('/api/v1/reports/' . $report->uuid . '/status', [
            'status' => $this->waitingStatus->name,
        ]);

        // Customers are not in the allowed role list for updating
        $response->assertStatus(403);
    }

    public function test_user_can_upload_attachments_to_a_report(): void
    {
        // Fake the storage disk to avoid actual file uploads
        Storage::fake('public');

        $report = Report::factory()->create();
        Sanctum::actingAs($this->customer);

        $files = [
            UploadedFile::fake()->image('photo1.jpg'),
            UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf'),
        ];

        $categories = ['photo', 'invoice'];

        $response = $this->postJson('/api/v1/reports/' . $report->uuid . '/attachments', [
            'attachments' => $files,
            'categories' => $categories,
        ]);

        $response->assertStatus(201)
            ->assertJsonCount(2); // Expecting two attachment records back

        // Assert the files were "stored" and database records were created
        $attachment = ReportAttachment::first();
        Storage::disk('public')->assertExists($attachment->file_path);
        $this->assertDatabaseHas('report_attachments', [
            'report_id' => $report->id,
            'category' => 'photo',
        ]);
        $this->assertDatabaseHas('report_attachments', [
            'report_id' => $report->id,
            'category' => 'invoice',
        ]);
    }

    public function test_customer_can_only_see_their_own_reports(): void
    {
        // Arrange: Create one report for our customer, and another for a different customer
        Report::factory()->create(['building_id' => $this->building->id]);

        $otherCustomer = User::factory()->customer()->create();
        $otherBuilding = Building::factory()->create();
        BuildingManagement::factory()->create([
            'building_id' => $otherBuilding->id,
            'customer_id' => $otherCustomer->id,
            'insurer_id' => $otherBuilding->insurer_id,
        ]);
        Report::factory()->create(['building_id' => $otherBuilding->id]);

        // Act
        Sanctum::actingAs($this->customer);
        $response = $this->getJson('/api/v1/reports');

        // Assert: The customer should only see the 1 report linked to their building
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_manager_can_see_reports_for_all_their_customers(): void
    {
        // Arrange: Our manager already has one customer with a report
        Report::factory()->create(['building_id' => $this->building->id]);

        // Create another customer for the same manager with a report
        $customer2 = User::factory()->customer()->create(['manager_id' => $this->manager->id]);
        $building2 = Building::factory()->create();
        BuildingManagement::factory()->create([
            'building_id' => $building2->id,
            'customer_id' => $customer2->id,
            'insurer_id' => $building2->insurer_id,
        ]);
        Report::factory()->create(['building_id' => $building2->id]);

        // And create a totally unrelated report
        Report::factory()->create();

        // Act
        Sanctum::actingAs($this->manager);
        $response = $this->getJson('/api/v1/reports');

        // Assert: The manager should see the 2 reports from their customers, but not the 3rd.
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
    private function seedStatusHierarchy(): void
    {
        $order = 1;
        foreach (ReportStatus::all() as $name) {
            $status = Status::factory()->create([
                'name' => $name,
                'order_column' => $order++,
            ]);

            if ($name === ReportStatus::REPORTED_TO_DAMARISK) {
                $this->defaultStatus = $status;
            }

            if ($name === ReportStatus::WAITING_FOR_INSURER_DAMAGE_ID) {
                $this->waitingStatus = $status;
                $this->waitingSubStatus = SubStatus::factory()->create([
                    'status_id' => $status->id,
                    'name' => 'Awaiting insurer follow-up',
                ]);
            }

            if ($name === ReportStatus::UNDER_INSURER_ADMINISTRATION) {
                $this->underAdministrationStatus = $status;
            }

            if ($name === ReportStatus::DATA_OR_DOCUMENT_DEFICIENCY) {
                $this->deficiencyStatus = $status;
                $this->deficiencyWaitingSubStatus = SubStatus::factory()->create([
                    'status_id' => $status->id,
                    'name' => ReportSubStatus::DEFICIENCY_WAITING_FOR_DOCUMENT_FROM_CLIENT,
                ]);
            }

            if ($name === ReportStatus::CLOSED) {
                $this->closedStatus = $status;
                $this->closedWithPaymentSubStatus = SubStatus::factory()->create([
                    'status_id' => $status->id,
                    'name' => ReportSubStatus::CLOSED_WITH_PAYMENT,
                ]);
            }
        }
    }

    public function test_transition_to_under_insurer_requires_damage_id_when_coming_from_reported(): void
    {
        $report = Report::factory()->create([
            'building_id' => $this->building->id,
            'status_id' => $this->defaultStatus->id,
            'sub_status_id' => null,
            'damage_id' => null,
        ]);

        Sanctum::actingAs($this->manager);

        $response = $this->postJson('/api/v1/reports/' . $report->uuid . '/status', [
            'status' => $this->underAdministrationStatus->name,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['damage_id']);
    }

    public function test_transition_to_under_insurer_updates_damage_id_when_provided(): void
    {
        $report = Report::factory()->create([
            'building_id' => $this->building->id,
            'status_id' => $this->defaultStatus->id,
            'sub_status_id' => null,
            'damage_id' => null,
        ]);

        Sanctum::actingAs($this->manager);

        $response = $this->postJson('/api/v1/reports/' . $report->uuid . '/status', [
            'status' => $this->underAdministrationStatus->name,
            'payload' => ['damage_id' => 'DMG-123'],
            'comment' => 'Insurer confirmed receipt',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status.name', $this->underAdministrationStatus->name);

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status_id' => $this->underAdministrationStatus->id,
            'damage_id' => 'DMG-123',
        ]);
        $this->assertDatabaseHas('report_status_histories', [
            'report_id' => $report->id,
            'status_id' => $this->underAdministrationStatus->id,
            'comment' => 'Insurer confirmed receipt',
        ]);
    }

    public function test_closing_with_payment_requires_payments(): void
    {
        $report = Report::factory()->create([
            'building_id' => $this->building->id,
            'status_id' => $this->defaultStatus->id,
        ]);

        Sanctum::actingAs($this->manager);

        $response = $this->postJson('/api/v1/reports/' . $report->uuid . '/status', [
            'status' => $this->closedStatus->name,
            'sub_status' => $this->closedWithPaymentSubStatus->name,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payload.closing_payments']);
    }

    public function test_closing_with_payment_creates_payment_records(): void
    {
        $report = Report::factory()->create([
            'building_id' => $this->building->id,
            'status_id' => $this->defaultStatus->id,
        ]);

        Sanctum::actingAs($this->manager);

        $payload = [
            [
                'recipient' => 'Claimant One',
                'amount' => 1234.56,
                'currency' => 'huf',
                'payment_date' => '2025-12-01',
                'payment_time' => '08:30',
            ],
            [
                'recipient' => 'Claimant Two',
                'amount' => 789.10,
                'currency' => 'EUR',
                'payment_date' => '2025-12-02',
                'payment_time' => null,
            ],
        ];

        $response = $this->postJson('/api/v1/reports/' . $report->uuid . '/status', [
            'status' => $this->closedStatus->name,
            'sub_status' => $this->closedWithPaymentSubStatus->name,
            'payload' => [
                'closing_payments' => $payload,
            ],
            'comment' => 'Paid out',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status.name', $this->closedStatus->name)
            ->assertJsonPath('sub_status.name', $this->closedWithPaymentSubStatus->name)
            ->assertJsonPath('closing_payments.0.recipient', 'Claimant One')
            ->assertJsonPath('closing_payments.0.currency', 'HUF')
            ->assertJsonCount(2, 'closing_payments');

        $this->assertDatabaseHas('report_closing_payments', [
            'report_id' => $report->id,
            'recipient' => 'Claimant One',
            'amount' => 1234.56,
            'currency' => 'HUF',
            'payment_time' => '08:30',
        ]);
        $this->assertDatabaseHas('report_closing_payments', [
            'report_id' => $report->id,
            'recipient' => 'Claimant Two',
            'amount' => 789.10,
            'currency' => 'EUR',
            'payment_time' => null,
        ]);
    }

    public function test_document_request_transition_requires_payload(): void
    {
        $report = Report::factory()->create([
            'building_id' => $this->building->id,
            'created_by_user_id' => $this->manager->id,
            'notifier_id' => $this->notifier->id,
            'insurer_id' => $this->building->insurer_id,
            'status_id' => $this->defaultStatus->id,
            'sub_status_id' => null,
        ]);

        Sanctum::actingAs($this->manager);

        $response = $this->postJson('/api/v1/reports/' . $report->uuid . '/status', [
            'status' => $this->deficiencyStatus->name,
            'sub_status' => ReportSubStatus::DEFICIENCY_WAITING_FOR_DOCUMENT_FROM_CLIENT,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'payload.email_title',
                'payload.email_body',
                'payload.requested_documents',
            ]);
    }

    public function test_document_request_transition_sends_mail_and_changes_status(): void
    {
        $report = Report::factory()->create([
            'building_id' => $this->building->id,
            'created_by_user_id' => $this->manager->id,
            'notifier_id' => $this->notifier->id,
            'insurer_id' => $this->building->insurer_id,
            'status_id' => $this->defaultStatus->id,
            'sub_status_id' => null,
            'claimant_email' => 'claimant@example.com',
        ]);

        Mail::fake();
        Sanctum::actingAs($this->manager);

        $payload = [
            'email_title' => 'Missing documents',
            'email_body' => "Hello,\nWe need more paperwork.",
            'requested_documents' => ['Invoice copy', 'Proof of ownership'],
            'other_document_note' => 'Please send within 5 days.',
            'attachments' => [
                ['file' => UploadedFile::fake()->create('checklist.pdf', 120, 'application/pdf')],
            ],
        ];

        $response = $this->post(
            '/api/v1/reports/' . $report->uuid . '/status',
            [
                'status' => $this->deficiencyStatus->name,
                'sub_status' => ReportSubStatus::DEFICIENCY_WAITING_FOR_DOCUMENT_FROM_CLIENT,
                'payload' => $payload,
                'comment' => 'Request sent to customer',
            ],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(200)
            ->assertJsonPath('status.name', $this->deficiencyStatus->name)
            ->assertJsonPath('sub_status.name', ReportSubStatus::DEFICIENCY_WAITING_FOR_DOCUMENT_FROM_CLIENT);

        $documentRequest = DocumentRequest::where('report_id', $report->id)->first();
        $this->assertNotNull($documentRequest);
        $this->assertSame($payload['email_title'], $documentRequest->email_title);
        $this->assertSame($payload['requested_documents'], $documentRequest->requested_documents);
        $this->assertFalse($documentRequest->is_fulfilled);
        $this->assertDatabaseCount('document_request_items', count($payload['requested_documents']));

        $this->assertDatabaseHas('report_status_histories', [
            'report_id' => $report->id,
            'status_id' => $this->deficiencyStatus->id,
            'sub_status_id' => $this->deficiencyWaitingSubStatus->id,
            'comment' => 'Request sent to customer',
        ]);

        Mail::assertSent(DocumentRequestMail::class, 2);
        Mail::assertSent(DocumentRequestMail::class, function (DocumentRequestMail $mail) use ($report, $payload) {
            $this->assertSame($payload['email_title'], $mail->emailTitle);
            $this->assertSame($payload['email_body'], $mail->emailBody);
            $this->assertEquals($payload['requested_documents'], $mail->requestedDocuments);
            $this->assertSame($payload['other_document_note'], $mail->otherDocumentNote);
            $this->assertCount(1, $mail->mailAttachments);
            $this->assertInstanceOf(UploadedFile::class, $mail->mailAttachments[0]);
            $this->assertNotEmpty($mail->documentRequestUrl);

            return $mail->report->is($report);
        });
    }

    public function test_manager_can_update_damage_id_without_status_change(): void
    {
        $report = Report::factory()->create([
            'building_id' => $this->building->id,
            'status_id' => $this->defaultStatus->id,
            'sub_status_id' => null,
            'damage_id' => 'OLD-1',
        ]);

        Sanctum::actingAs($this->manager);

        $response = $this->patchJson('/api/v1/reports/' . $report->uuid . '/damage-id', [
            'damage_id' => 'NEW-123',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['damage_id' => 'NEW-123']);

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'damage_id' => 'NEW-123',
        ]);
    }

    public function test_update_damage_id_requires_unique_value(): void
    {
        $existing = Report::factory()->create(['damage_id' => 'DUPLICATE']);
        $report = Report::factory()->create([
            'building_id' => $this->building->id,
            'damage_id' => 'OLD',
        ]);

        Sanctum::actingAs($this->manager);

        $response = $this->patchJson('/api/v1/reports/' . $report->uuid . '/damage-id', [
            'damage_id' => $existing->damage_id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['damage_id']);
    }
}
