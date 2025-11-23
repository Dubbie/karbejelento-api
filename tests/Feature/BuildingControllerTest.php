<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Insurer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BuildingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_view_buildings(): void
    {
        $this->getJson('/api/v1/buildings')->assertStatus(401);
    }

    public function test_any_authenticated_user_can_view_buildings_list(): void
    {
        $customer = User::factory()->customer()->create();
        Sanctum::actingAs($customer);

        $this->getJson('/api/v1/buildings')->assertStatus(200);
    }

    public function test_admin_can_create_a_building(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();
        $insurer = Insurer::factory()->create();

        Sanctum::actingAs($admin);

        $buildingData = Building::factory()->make()->toArray();
        unset($buildingData['insurer_id']);
        $buildingData['customer_id'] = $customer->id;
        $buildingData['insurer_uuid'] = $insurer->uuid;

        $response = $this->postJson('/api/v1/buildings', $buildingData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => $buildingData['name']]);

        // Assert building was created
        $this->assertDatabaseHas('buildings', ['name' => $buildingData['name']]);
        // Assert the management record was also created, linking it to the customer
        $this->assertDatabaseHas('building_management', ['customer_id' => $customer->id]);
    }

    public function test_customer_cannot_create_a_building(): void
    {
        $customer = User::factory()->customer()->create();
        $insurer = Insurer::factory()->create();
        Sanctum::actingAs($customer);

        $buildingData = Building::factory()->make()->toArray();
        unset($buildingData['insurer_id']);
        $buildingData['customer_id'] = $customer->id;
        $buildingData['insurer_uuid'] = $insurer->uuid;

        $response = $this->postJson('/api/v1/buildings', $buildingData);

        // We expect a 403 Forbidden because customers don't have the 'admin' or 'damage_solver' role
        $response->assertStatus(403);
    }

    public function test_user_can_view_a_specific_building(): void
    {
        $user = User::factory()->create();
        $building = Building::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/buildings/' . $building->uuid);

        $response->assertStatus(200)
            ->assertJsonFragment(['uuid' => $building->uuid]);
    }

    public function test_manager_can_update_a_building(): void
    {
        $manager = User::factory()->manager()->create();
        $building = Building::factory()->create(['name' => 'Old Building Name']);
        Sanctum::actingAs($manager);

        $updateData = ['name' => 'New Building Name'];
        $response = $this->patchJson('/api/v1/buildings/' . $building->uuid, $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'New Building Name']);

        $this->assertDatabaseHas('buildings', ['id' => $building->id, 'name' => 'New Building Name']);
    }

    public function test_customer_cannot_update_a_building(): void
    {
        $customer = User::factory()->customer()->create();
        $building = Building::factory()->create();
        Sanctum::actingAs($customer);

        $response = $this->patchJson('/api/v1/buildings/' . $building->uuid, ['name' => 'Attempted Update']);

        // Customers are not in the allowed role list [admin, damage_solver, manager]
        $response->assertStatus(403);
    }

    public function test_admin_can_delete_a_building(): void
    {
        $admin = User::factory()->admin()->create();
        $building = Building::factory()->create();
        Sanctum::actingAs($admin);

        $response = $this->deleteJson('/api/v1/buildings/' . $building->uuid);

        $response->assertStatus(204); // No Content
        $this->assertDatabaseMissing('buildings', ['id' => $building->id]);
    }

    public function test_manager_cannot_delete_a_building(): void
    {
        $manager = User::factory()->manager()->create();
        $building = Building::factory()->create();
        Sanctum::actingAs($manager);

        $response = $this->deleteJson('/api/v1/buildings/' . $building->uuid);

        // Only admins can delete
        $response->assertStatus(403);
    }
}
