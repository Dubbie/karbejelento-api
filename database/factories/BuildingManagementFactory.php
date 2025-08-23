<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\BuildingManagement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BuildingManagement>
 */
class BuildingManagementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BuildingManagement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'customer_id' => User::factory()->customer(), // Ensure the user is a customer
            'start_date' => $this->faker->dateTimeThisYear(),
            'end_date' => null,
        ];
    }
}
