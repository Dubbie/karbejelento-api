<?php

namespace App\Services\ReportStatusTransitions\Rules;

use App\Models\Report;
use App\Models\Status;
use App\Models\SubStatus;
use App\Models\User;

interface ReportStatusTransitionRule
{
    public function supports(Report $report, Status $targetStatus, ?SubStatus $subStatus): bool;

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(
        Report $report,
        Status $targetStatus,
        ?SubStatus $subStatus,
        User $actor,
        array $payload
    ): Report;
}
