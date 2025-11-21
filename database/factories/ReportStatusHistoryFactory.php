<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\ReportStatusHistory;
use App\Models\User;
use Database\Factories\Concerns\ResolvesStatuses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReportStatusHistory>
 */
class ReportStatusHistoryFactory extends Factory
{
    use ResolvesStatuses;

    protected $model = ReportStatusHistory::class;

    public function definition(): array
    {
        [$status, $subStatus] = $this->randomStatusWithOptionalSubStatus();

        return [
            'uuid' => (string) Str::uuid(),
            'report_id' => Report::factory(),
            'user_id' => User::factory(),
            'status_id' => $status->id,
            'sub_status_id' => $subStatus?->id,
            'comment' => $this->faker->sentence(),
        ];
    }
}
