<?php

namespace Database\Factories;

use App\Models\Status;
use App\Models\SubStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Status>
 */
class StatusFactory extends Factory
{
    protected $model = Status::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => $this->faker->unique()->words(3, true),
            'order_column' => $this->faker->numberBetween(1, 100),
        ];
    }

    /**
     * Quickly attach a set of sub-statuses to the status.
     */
    public function withSubStatuses(int $count = 2): static
    {
        return $this->has(SubStatus::factory()->count($count), 'subStatuses');
    }
}
