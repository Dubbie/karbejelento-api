<?php

namespace Tests\Feature;

use App\Constants\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    // This trait handles resetting the database for each test.
    use RefreshDatabase;

    // A test to set the baseline: can we even get a 401?
    public function test_unauthenticated_user_cannot_access_users_list(): void
    {
        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(401); // Unauthorized
    }

    public function test_authenticated_user_can_view_users_list(): void
    {
        // Arrange: Create a user to log in as, and a few other users to see in the list.
        $user = User::factory()->create();
        User::factory()->count(3)->create();

        // Act: Make the request "acting as" our user.
        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/users');

        // Assert: Check the response and the JSON structure.
        $response->assertStatus(200);
        $response->assertJsonCount(4, 'data'); // Should see all 4 users
        $response->assertJsonStructure([
            'data' => [
                '*' => ['uuid', 'name', 'email', 'role']
            ],
            'meta' => ['totalItems']
        ]);
    }

    public function test_admin_can_create_a_new_user(): void
    {
        // Arrange
        $admin = User::factory()->admin()->create();
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => UserRole::MANAGER,
        ];

        // Act
        Sanctum::actingAs($admin);
        $response = $this->postJson('/api/v1/users', $userData);

        // Assert
        $response->assertStatus(201); // Created
        $response->assertJsonFragment(['email' => 'test@example.com']);
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => UserRole::MANAGER,
        ]);
    }

    public function test_customer_cannot_create_a_new_user(): void
    {
        // Arrange: We need an authorization rule for this first. Let's assume only admins can create.
        // For now, let's just assert our current behavior. If you add authorization, this test will fail
        // until you update it to assert a 403 Forbidden status.
        $customer = User::factory()->customer()->create();
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => UserRole::MANAGER,
        ];

        // Act
        Sanctum::actingAs($customer);
        $response = $this->postJson('/api/v1/users', $userData);

        // Assert: A customer should be forbidden from creating users.
        // We will need to add an authorization rule in StoreUserRequest for this to pass.
        $response->assertStatus(403); // Let's assume we add this rule.
    }

    public function test_creating_user_fails_with_invalid_data(): void
    {
        // Arrange
        $admin = User::factory()->admin()->create();
        $badUserData = [
            'name' => 'Test User',
            'email' => 'not-an-email', // Invalid email
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'invalid-role', // Invalid role
        ];

        // Act
        Sanctum::actingAs($admin);
        $response = $this->postJson('/api/v1/users', $badUserData);

        // Assert
        $response->assertStatus(422); // Unprocessable Entity (Validation Failed)
        $response->assertJsonValidationErrors(['email', 'role']);
        $this->assertDatabaseMissing('users', ['name' => 'Test User']);
    }

    public function test_user_can_be_updated(): void
    {
        // Arrange
        $admin = User::factory()->admin()->create();
        $userToUpdate = User::factory()->create(['name' => 'Original Name']);

        // Act
        Sanctum::actingAs($admin);
        $response = $this->patchJson('/api/v1/users/' . $userToUpdate->uuid, [
            'name' => 'Updated Name',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Updated Name']);
        $this->assertDatabaseHas('users', [
            'uuid' => $userToUpdate->uuid,
            'name' => 'Updated Name',
        ]);
    }

    public function test_user_can_be_deleted(): void
    {
        // Arrange
        $admin = User::factory()->admin()->create();
        $userToDelete = User::factory()->create();

        // Act
        Sanctum::actingAs($admin);
        $response = $this->deleteJson('/api/v1/users/' . $userToDelete->uuid);

        // Assert
        $response->assertStatus(204); // No Content
        $this->assertDatabaseMissing('users', ['uuid' => $userToDelete->uuid]);
    }
}
