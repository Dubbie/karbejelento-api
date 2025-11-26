<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\ReportClosingPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ReportClosingPayment>
 */
class ReportClosingPaymentFactory extends Factory
{
    protected $model = ReportClosingPayment::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'report_id' => Report::factory(),
            'created_by_user_id' => User::factory(),
            'recipient' => $this->faker->name(),
            'amount' => $this->faker->randomFloat(2, 1000, 20000),
            'currency' => 'HUF',
            'payment_date' => $this->faker->date(),
            'payment_time' => $this->faker->optional()->time('H:i'),
        ];
    }
}
