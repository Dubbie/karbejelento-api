<?php

namespace Database\Factories;

use App\Constants\NotificationEvent;
use App\Models\NotificationRule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\NotificationRule>
 */
class NotificationRuleFactory extends Factory
{
    protected $model = NotificationRule::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => $this->faker->sentence(3),
            'event' => Arr::random(NotificationEvent::all()),
            'status_id' => null,
            'sub_status_id' => null,
            'is_active' => true,
            'options' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
