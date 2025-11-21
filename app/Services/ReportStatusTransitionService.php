<?php

namespace App\Services;

use App\Models\Report;
use App\Models\Status;
use App\Models\SubStatus;
use App\Models\User;
use App\Services\ReportStatusTransitions\Rules\ReportStatusTransitionRule;

class ReportStatusTransitionService
{
    /**
     * @param ReportStatusTransitionRule[] $rules
     */
    public function __construct(
        protected ReportService $reportService,
        protected array $rules = []
    ) {}

    /**
     * @param array<string, mixed> $payload
     */
    public function transition(
        Report $report,
        Status $targetStatus,
        ?SubStatus $subStatus,
        User $actor,
        array $payload = []
    ): Report {
        $report->loadMissing('status');

        foreach ($this->rules as $rule) {
            if ($rule->supports($report, $targetStatus, $subStatus)) {
                return $rule->handle($report, $targetStatus, $subStatus, $actor, $payload);
            }
        }

        return $this->reportService->changeReportStatus($report, $targetStatus->id, $subStatus?->id, [
            'user_id' => $actor->id,
            'comment' => $payload['comment'] ?? null,
        ]);
    }
}
