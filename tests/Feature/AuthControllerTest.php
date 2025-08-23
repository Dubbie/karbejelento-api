<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_correct_credentials(): void
    {
        // Arrange: Create a user in the database.
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        // Act: Make a POST request to the login endpoint.
        $response = $this->postJson('/api/v1/auth/login', $credentials);

        // Assert: Check the response.
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'access_token', // Ensure the token is in the response
        ]);
        $this->assertNotEmpty($response->json('access_token'));
    }

    public function test_user_cannot_login_with_incorrect_password(): void
    {
        // Arrange: Create a user.
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ];

        // Act
        $response = $this->postJson('/api/v1/auth/login', $credentials);

        // Assert: The AuthController throws a ValidationException, resulting in a 422 status.
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email'); // The error is attached to the 'email' field
        $response->assertJsonMissingPath('access_token'); // Ensure no token was issued
    }

    public function test_user_cannot_login_with_non_existent_email(): void
    {
        // Arrange
        $credentials = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ];

        // Act
        $response = $this->postJson('/api/v1/auth/login', $credentials);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
        $response->assertJsonMissingPath('access_token');
    }

    public function test_login_requires_email_and_password(): void
    {
        // Act: Send an empty request body.
        $response = $this->postJson('/api/v1/auth/login', []);

        // Assert: We should get validation errors for both fields.
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password']);
    }
}
