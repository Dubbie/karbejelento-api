<?php

namespace Tests\Feature;

use App\Constants\DamageType;
use App\Constants\ReportStatus;
use App\Models\Building;
use App\Models\BuildingManagement;
use App\Models\Notifier;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\User;
use Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    /**
     * Set up the common actors for all tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a hierarchy: Admin, Manager, and a Customer managed by the Manager
        $this->admin = User::factory()->admin()->create();
        $this->manager = User::factory()->manager()->create();
        $this->customer = User::factory()->customer()->create(['manager_id' => $this->manager->id]);

        // Create a building and assign the customer to it
        $this->building = Building::factory()->create();
        BuildingManagement::factory()->create([
            'building_id' => $this->building->id,
            'customer_id' => $this->customer->id,
        ]);

        // Create a notifier for the customer
        $this->notifier = Notifier::factory()->create(['customer_id' => $this->customer->id]);
    }

    public function test_customer_can_create_a_report(): void
    {
        Sanctum::actingAs($this->customer);

        $reportData = [
            'building_id' => $this->building->id,
            'notifier_id' => $this->notifier->id,
            'damage_type' => DamageType::ROOF_LEAK,
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
        $this->assertDatabaseHas('reports', ['building_id' => $this->building->id]);
        $this->assertDatabaseHas('report_status_history', ['status' => ReportStatus::NEW]);
    }

    public function test_user_can_view_a_specific_report(): void
    {
        $report = Report::factory()->create(['building_id' => $this->building->id]);
        Sanctum::actingAs($this->customer);

        $response = $this->getJson('/api/v1/reports/' . $report->uuid);

        $response->assertStatus(200)
            ->assertJsonFragment(['uuid' => $report->uuid]);
    }

    public function test_manager_can_update_a_report(): void
    {
        $report = Report::factory()->create(['building_id' => $this->building->id]);
        Sanctum::actingAs($this->manager);

        $updateData = ['current_status' => ReportStatus::IN_PROGRESS];
        $response = $this->patchJson('/api/v1/reports/' . $report->uuid, $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment(['current_status' => ReportStatus::IN_PROGRESS]);

        $this->assertDatabaseHas('reports', ['id' => $report->id, 'current_status' => ReportStatus::IN_PROGRESS]);
    }

    public function test_customer_cannot_update_a_report(): void
    {
        $report = Report::factory()->create(['building_id' => $this->building->id]);
        Sanctum::actingAs($this->customer);

        $response = $this->patchJson('/api/v1/reports/' . $report->uuid, ['current_status' => ReportStatus::IN_PROGRESS]);

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
}
