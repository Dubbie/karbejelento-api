<?php

namespace Database\Factories;

use App\Constants\NotificationRecipientType;
use App\Models\NotificationRule;
use App\Models\NotificationRuleRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<\App\Models\NotificationRuleRecipient>
 */
class NotificationRuleRecipientFactory extends Factory
{
    protected $model = NotificationRuleRecipient::class;

    public function definition(): array
    {
        $type = Arr::random(NotificationRecipientType::all());

        return [
            'notification_rule_id' => NotificationRule::factory(),
            'recipient_type' => $type,
            'recipient_value' => $this->fakeValueForType($type),
        ];
    }

    private function fakeValueForType(string $type): ?string
    {
        return match ($type) {
            NotificationRecipientType::CUSTOM_EMAIL => $this->faker->safeEmail(),
            NotificationRecipientType::ROLE => $this->faker->randomElement(['admin', 'manager', 'damage_solver']),
            default => null,
        };
    }

    public function type(string $type, ?string $value = null): static
    {
        return $this->state(fn () => [
            'recipient_type' => $type,
            'recipient_value' => $value,
        ]);
    }
}
