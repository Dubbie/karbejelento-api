<?php

namespace Tests\Feature;

use App\Models\Insurer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InsurerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_insurers(): void
    {
        $user = User::factory()->manager()->create();
        Insurer::factory()->count(2)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/insurers');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_admin_can_create_insurer(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $payload = ['name' => 'New Insurer'];

        $response = $this->postJson('/api/v1/insurers', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'New Insurer']);

        $this->assertDatabaseHas('insurers', ['name' => 'New Insurer']);
    }

    public function test_manager_cannot_create_insurer(): void
    {
        $manager = User::factory()->manager()->create();
        Sanctum::actingAs($manager);

        $response = $this->postJson('/api/v1/insurers', ['name' => 'Should Fail']);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_insurer(): void
    {
        $admin = User::factory()->admin()->create();
        $insurer = Insurer::factory()->create(['name' => 'Old Name']);
        Sanctum::actingAs($admin);

        $response = $this->patchJson('/api/v1/insurers/' . $insurer->uuid, ['name' => 'Updated Name']);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Name']);

        $this->assertDatabaseHas('insurers', ['id' => $insurer->id, 'name' => 'Updated Name']);
    }

    public function test_admin_can_delete_insurer(): void
    {
        $admin = User::factory()->admin()->create();
        $insurer = Insurer::factory()->create();
        Sanctum::actingAs($admin);

        $response = $this->deleteJson('/api/v1/insurers/' . $insurer->uuid);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('insurers', ['id' => $insurer->id]);
    }
}
