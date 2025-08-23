<?php

namespace Database\Factories;

use App\Constants\ReportStatus;
use App\Models\Report;
use App\Models\ReportStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReportStatusHistory>
 */
class ReportStatusHistoryFactory extends Factory
{
    protected $model = ReportStatusHistory::class;

    public function definition(): array
    {
        $statuses = (new \ReflectionClass(ReportStatus::class))->getConstants();

        return [
            'report_id' => Report::factory(),
            'user_id' => User::factory(),
            'status' => $this->faker->randomElement($statuses),
            'notes' => $this->faker->sentence(),
        ];
    }
}
