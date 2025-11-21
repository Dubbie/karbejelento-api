<?php

namespace Tests\Feature;

use App\Constants\UserRole;
use App\Models\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BuildingImportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create one of each user role for use in the tests
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $this->customer = User::factory()->create(['role' => UserRole::CUSTOMER]);
    }

    /*
    |--------------------------------------------------------------------------
    | Template Download Tests
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_download_import_template(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('api/v1/buildings/import/template');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('Content-Disposition', 'attachment; filename=building-import-template.xlsx');
    }

    public function test_manager_can_download_import_template(): void
    {
        Sanctum::actingAs($this->manager);

        $response = $this->getJson('api/v1/buildings/import/template');

        $response->assertStatus(200);
    }

    public function test_customer_is_forbidden_from_downloading_template(): void
    {
        Sanctum::actingAs($this->customer);

        $this->getJson('api/v1/buildings/import/template')
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_download_template(): void
    {
        $this->getJson('api/v1/buildings/import/template')
            ->assertStatus(401);
    }


    /*
    |--------------------------------------------------------------------------
    | Building Import Tests
    |--------------------------------------------------------------------------
    */

    public function test_unauthenticated_user_cannot_import_buildings(): void
    {
        $this->postJson('api/v1/buildings/import')
            ->assertStatus(401);
    }

    public function test_customer_is_forbidden_from_importing_buildings(): void
    {
        Sanctum::actingAs($this->customer);

        $this->postJson('api/v1/buildings/import')
            ->assertStatus(403);
    }

    public function test_import_request_fails_with_validation_errors(): void
    {
        Sanctum::actingAs($this->admin);

        // Test with empty payload
        $this->postJson('api/v1/buildings/import', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id', 'file']);

        // Test with invalid customer ID (not a customer role)
        $this->postJson('api/v1/buildings/import', ['customer_id' => $this->manager->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id']);

        // Test with invalid file type
        $this->postJson('api/v1/buildings/import', [
            'customer_id' => $this->customer->id,
            'file' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])->assertStatus(422)->assertJsonValidationErrors(['file']);
    }

    public function test_admin_can_successfully_import_buildings_for_a_customer(): void
    {
        Sanctum::actingAs($this->admin);
        Storage::fake('local'); // Use a fake disk to prevent actual file storage

        // Create a real CSV file content as a string
        $header = 'Társasház neve,Irányítószám,Város,Közterület neve,Közterület tipusa,Házszám,Bankszámlaszám,Kötvényszám,Biztosító';
        $row1 = '"TH Teszt 1","1225","Budapest","Hamis","utca","1","00000000-10000000-00000000","BOND-01","Allianz"';
        $row2 = '"TH Teszt 2","1225","Budapest","Hamis","utca","2","00000000-20000000-00000000","BOND-02","Allianz"';
        $row_invalid = '"","1225","Budapest","Helytelen","utca","2","00000000-30000000-00000000","BOND-03","Allianz"';

        $content = implode("\n", [$header, $row1, $row2, $row_invalid]);

        $file = UploadedFile::fake()->createWithContent('buildings.csv', $content);

        // Action: Post the file to the import endpoint
        $response = $this->postJson('api/v1/buildings/import', [
            'customer_id' => $this->customer->id,
            'file' => $file,
        ]);

        // Assertions
        $response->assertStatus(201)
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('user_id', $this->admin->id)
            ->assertJsonPath('customer_id', $this->customer->id);

        // Assert file was stored correctly
        $importRecordData = $response->json();
        Storage::disk('local')->assertExists($importRecordData['stored_path']);

        // Assert valid buildings were created in the database
        $this->assertDatabaseHas('buildings', ['name' => 'TH Teszt 1']);
        $this->assertDatabaseHas('buildings', ['name' => 'TH Teszt 2']);

        // Assert pivot records were created linking the buildings to the correct customer
        $building1 = Building::where('name', 'TH Teszt 1')->first();
        $this->assertDatabaseHas('building_management', [
            'building_id' => $building1->id,
            'customer_id' => $this->customer->id,
        ]);

        // Assert the invalid building was NOT created
        $this->assertDatabaseMissing('buildings', ['address_line_1' => 'Helytelen']);

        // Assert the import record logged the error for the invalid row
        $this->assertDatabaseHas('building_imports', [
            'id' => $importRecordData['id'],
            // Note: row index is 4 because of header and 1-based indexing
            'errors' => json_encode(['4' => ['The name field is required.']]),
        ]);
    }
}
