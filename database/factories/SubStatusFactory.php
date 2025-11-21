<?php

namespace Database\Factories;

use App\Models\Status;
use App\Models\SubStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubStatus>
 */
class SubStatusFactory extends Factory
{
    protected $model = SubStatus::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'status_id' => Status::factory(),
            'name' => $this->faker->unique()->words(3, true),
        ];
    }
}
