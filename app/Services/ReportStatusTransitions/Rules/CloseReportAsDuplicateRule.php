<?php

namespace App\Services\ReportStatusTransitions\Rules;

use App\Constants\ReportStatus;
use App\Constants\ReportSubStatus;
use App\Models\Report;
use App\Models\Status;
use App\Models\SubStatus;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CloseReportAsDuplicateRule implements ReportStatusTransitionRule
{
    public function __construct(protected ReportService $reportService) {}

    public function supports(Report $report, Status $targetStatus, ?SubStatus $subStatus): bool
    {
        return $targetStatus->name === ReportStatus::CLOSED
            && $subStatus?->name === ReportSubStatus::CLOSED_DUPLICATE_REPORT;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(
        Report $report,
        Status $targetStatus,
        ?SubStatus $subStatus,
        User $actor,
        array $payload
    ): Report {
        $duplicateUuid = (string) ($payload['duplicate_report_uuid'] ?? '');

        if ($duplicateUuid === '') {
            throw ValidationException::withMessages([
                'payload.duplicate_report_uuid' => ['The referenced duplicate report is required.'],
            ]);
        }

        $duplicateReport = Report::where('uuid', $duplicateUuid)->first();

        if (!$duplicateReport) {
            throw ValidationException::withMessages([
                'payload.duplicate_report_uuid' => ['The referenced duplicate report could not be found.'],
            ]);
        }

        if ($duplicateReport->id === $report->id) {
            throw ValidationException::withMessages([
                'payload.duplicate_report_uuid' => ['A report cannot be marked as a duplicate of itself.'],
            ]);
        }

        return DB::transaction(function () use ($report, $duplicateReport, $targetStatus, $subStatus, $actor, $payload) {
            $report->update([
                'duplicate_report_id' => $duplicateReport->id,
            ]);

            return $this->reportService->changeReportStatus(
                $report->fresh(),
                $targetStatus->id,
                $subStatus?->id,
                [
                    'user_id' => $actor->id,
                    'comment' => $payload['comment'] ?? null,
                ]
            );
        });
    }
}
