<?php

namespace App\Services\ReportStatusTransitions\Rules;

use App\Constants\ReportStatus;
use App\Models\Report;
use App\Models\Status;
use App\Models\SubStatus;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Validation\ValidationException;

class RequireDamageIdForUnderAdministrationRule implements ReportStatusTransitionRule
{
    public function __construct(protected ReportService $reportService) {}

    public function supports(Report $report, Status $targetStatus, ?SubStatus $subStatus): bool
    {
        $currentStatusName = $report->status?->name;

        return $currentStatusName === ReportStatus::REPORTED_TO_DAMARISK
            && $targetStatus->name === ReportStatus::UNDER_INSURER_ADMINISTRATION;
    }

    public function handle(
        Report $report,
        Status $targetStatus,
        ?SubStatus $subStatus,
        User $actor,
        array $payload
    ): Report {
        $damageId = $payload['damage_id'] ?? null;

        if (empty($damageId)) {
            throw ValidationException::withMessages([
                'damage_id' => ['Damage ID is required when moving this report under insurer administration.'],
            ]);
        }

        $report->update(['damage_id' => $damageId]);

        return $this->reportService->changeReportStatus($report, $targetStatus->id, $subStatus?->id, [
            'user_id' => $actor->id,
            'comment' => $payload['comment'] ?? null,
        ]);
    }
}
