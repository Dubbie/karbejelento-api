<?php

namespace Database\Factories;

use App\Constants\BuildingImportStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BuildingImport>
 */
class BuildingImportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // The uploader
            'customer_id' => User::factory(), // The customer
            'status' => BuildingImportStatus::PENDING,
            'original_filename' => $this->faker->word() . '.xlsx',
            'stored_path' => 'imports/' . $this->faker->uuid() . '.xlsx',
            'total_rows' => 0,
            'processed_rows' => 0,
            'successful_rows' => 0,
            'errors' => null,
        ];
    }

    public function completed(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'status' => BuildingImportStatus::COMPLETED,
            'processed_rows' => $attributes['total_rows'] ?? 10,
            'successful_rows' => $attributes['total_rows'] ?? 10,
        ]);
    }
}
