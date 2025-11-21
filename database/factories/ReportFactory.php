<?php

namespace Database\Factories;

use App\Constants\ClaimantType;
use App\Constants\DamageType;
use App\Constants\EstimatedCost;
use App\Models\Building;
use App\Models\Notifier;
use App\Models\Report;
use App\Models\User;
use Database\Factories\Concerns\ResolvesStatuses;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    use ResolvesStatuses;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Report::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Dynamically get constants to use in faker->randomElement
        $estimatedCosts = EstimatedCost::all();
        $claimantTypes = ClaimantType::all();
        $damageTypes = DamageType::all();
        [$status, $subStatus] = $this->randomStatusWithOptionalSubStatus();

        return [
            'uuid' => $this->faker->uuid(),
            'building_id' => Building::factory(),
            'created_by_user_id' => User::factory(),
            'notifier_id' => Notifier::factory(),
            'bond_number' => $this->faker->numerify('BOND-########'),
            'insurer' => $this->faker->company(),
            'damage_id' => null,
            'damage_type' => $this->faker->randomElement($damageTypes),
            'damage_description' => $this->faker->paragraph(3),
            'damaged_building_name' => null,
            'damaged_building_number' => $this->faker->buildingNumber(),
            'damaged_floor' => $this->faker->randomElement(['1st', '2nd', '3rd', 'Ground']),
            'damaged_unit_or_door' => $this->faker->bothify('Unit ##??'),
            'damage_date' => $this->faker->dateTimeThisMonth(),
            'estimated_cost' => $this->faker->randomElement($estimatedCosts),
            'claimant_type' => $this->faker->randomElement($claimantTypes),
            'claimant_name' => $this->faker->name(),
            'claimant_email' => $this->faker->safeEmail(),
            'claimant_phone_number' => $this->faker->phoneNumber(),
            'contact_name' => $this->faker->name(),
            'contact_phone_number' => $this->faker->phoneNumber(),
            'claimant_account_number' => $this->faker->bankAccountNumber(),
            'status_id' => $status->id,
            'sub_status_id' => $subStatus?->id,
        ];
    }
}
