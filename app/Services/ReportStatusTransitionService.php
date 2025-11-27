<?php

namespace App\Services;

use App\Constants\ReportStatus;
use App\Models\Report;
use App\Models\Status;
use App\Models\SubStatus;
use App\Models\User;
use App\Services\ReportStatusTransitions\Rules\ReportStatusTransitionRule;
use Illuminate\Validation\ValidationException;

class ReportStatusTransitionService
{
    /**
     * Status/sub-status combinations that may be repeated without raising a validation error.
     *
     * @var array<string, array<int, string|null>>
     */
    private const REPEATABLE_STATE_COMBINATIONS = [
        ReportStatus::UNDER_INSURER_ADMINISTRATION => [null],
    ];

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
        $report->loadMissing(['status', 'subStatus']);

        $currentStatus = $report->status;
        $isSameStatus = $currentStatus && $currentStatus->id === $targetStatus->id;
        $currentSubStatus = $report->subStatus;
        $isSameSubStatus = ($currentSubStatus?->id ?? null) === ($subStatus?->id ?? null);

        if ($isSameStatus && $isSameSubStatus) {
            if (!$this->currentStateAllowsRepeat($currentStatus, $currentSubStatus)) {
                throw ValidationException::withMessages([
                    'status' => ['The report is already in the selected status.'],
                ]);
            }
        }

        $this->assertClosingHasComment($targetStatus, $payload);

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

    private function assertClosingHasComment(Status $targetStatus, array $payload): void
    {
        if ($targetStatus->name !== ReportStatus::CLOSED) {
            return;
        }

        $comment = trim((string) ($payload['comment'] ?? ''));

        if ($comment === '') {
            throw ValidationException::withMessages([
                'comment' => ['A comment is required when closing a report.'],
            ]);
        }
    }

    private function currentStateAllowsRepeat(?Status $currentStatus, ?SubStatus $currentSubStatus): bool
    {
        if (!$currentStatus) {
            return false;
        }

        $allowedSubStatuses = self::REPEATABLE_STATE_COMBINATIONS[$currentStatus->name] ?? [];

        foreach ($allowedSubStatuses as $allowedSubStatus) {
            $isNullCombination = $allowedSubStatus === null && $currentSubStatus === null;
            $isMatchingSubStatus = $allowedSubStatus !== null
                && $currentSubStatus
                && $currentSubStatus->name === $allowedSubStatus;

            if ($isNullCombination || $isMatchingSubStatus) {
                return true;
            }
        }

        return false;
    }
}
