<?php

namespace Database\Factories\Concerns;

use App\Models\Status;

trait ResolvesStatuses
{
    /**
     * Resolve an existing status (creating one if necessary) and optionally a sub-status.
     *
     * @return array{0:\App\Models\Status,1:\App\Models\SubStatus|null}
     */
    protected function randomStatusWithOptionalSubStatus(): array
    {
        /** @var Status|null $status */
        $status = Status::with('subStatuses')->inRandomOrder()->first();

        if (!$status) {
            $status = Status::factory()
                ->withSubStatuses($this->faker->numberBetween(0, 3))
                ->create();
            $status->load('subStatuses');
        }

        $subStatus = $status->subStatuses->isEmpty()
            ? null
            : $status->subStatuses->random();

        return [$status, $subStatus];
    }
}
