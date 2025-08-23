<?php

namespace Database\Factories;

use App\Constants\StreetType;
use App\Models\Building;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Building>
 */
class BuildingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Building::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $streetTypes = (new \ReflectionClass(StreetType::class))->getConstants();

        return [
            'uuid' => $this->faker->uuid(),
            'name' => $this->faker->company() . ' Building',
            'postcode' => $this->faker->postcode(),
            'city' => $this->faker->city(),
            'street_name' => $this->faker->streetName(),
            'street_type' => $this->faker->randomElement($streetTypes),
            'street_number' => $this->faker->buildingNumber(),
            'bond_number' => $this->faker->unique()->numerify('BOND-########'),
            'account_number' => $this->faker->iban(),
            'insurer' => $this->faker->company(),
            'is_archived' => false,
        ];
    }
}
