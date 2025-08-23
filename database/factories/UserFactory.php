<?php

namespace Database\Factories;

use App\Constants\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::CUSTOMER, // Default to the most common role
            'is_active' => true,
            'manager_id' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * State for creating an ADMIN user.
     */
    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => UserRole::ADMIN,
        ]);
    }

    /**
     * State for creating a MANAGER user.
     */
    public function manager(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => UserRole::MANAGER,
        ]);
    }

    /**
     * State for creating a CUSTOMER user.
     */
    public function customer(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => UserRole::CUSTOMER,
        ]);
    }
}
