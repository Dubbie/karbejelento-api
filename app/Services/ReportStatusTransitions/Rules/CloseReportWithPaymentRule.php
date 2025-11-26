<?php

namespace App\Services\ReportStatusTransitions\Rules;

use App\Constants\ReportStatus;
use App\Constants\ReportSubStatus;
use App\Models\Report;
use App\Models\Status;
use App\Models\SubStatus;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CloseReportWithPaymentRule implements ReportStatusTransitionRule
{
    public function __construct(protected ReportService $reportService) {}

    public function supports(Report $report, Status $targetStatus, ?SubStatus $subStatus): bool
    {
        return $targetStatus->name === ReportStatus::CLOSED
            && $subStatus?->name === ReportSubStatus::CLOSED_WITH_PAYMENT;
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
        $payments = $this->normalizePayments($payload['closing_payments'] ?? null);

        if (empty($payments)) {
            throw ValidationException::withMessages([
                'closing_payments' => ['At least one payment record is required when closing with payment.'],
            ]);
        }

        return DB::transaction(function () use ($report, $targetStatus, $subStatus, $actor, $payload, $payments) {
            foreach ($payments as $payment) {
                $report->closingPayments()->create(array_merge($payment, [
                    'uuid' => (string) Str::uuid(),
                    'created_by_user_id' => $actor->id,
                ]));
            }

            return $this->reportService->changeReportStatus(
                $report,
                $targetStatus->id,
                $subStatus?->id,
                [
                    'user_id' => $actor->id,
                    'comment' => $payload['comment'] ?? null,
                ]
            );
        });
    }

    /**
     * @param mixed $rawPayments
     * @return array<int, array<string, mixed>>
     */
    private function normalizePayments($rawPayments): array
    {
        if (!is_array($rawPayments)) {
            return [];
        }

        $normalized = [];

        foreach ($rawPayments as $payment) {
            if (!is_array($payment)) {
                continue;
            }

            $normalized[] = [
                'recipient' => Arr::get($payment, 'recipient'),
                'amount' => Arr::get($payment, 'amount'),
                'currency' => strtoupper((string) Arr::get($payment, 'currency')),
                'payment_date' => $this->formatDate(Arr::get($payment, 'payment_date')),
                'payment_time' => Arr::get($payment, 'payment_time'),
            ];
        }

        return array_values(array_filter($normalized, fn ($payment) => $this->isCompletePayment($payment)));
    }

    /**
     * @param array<string, mixed> $payment
     */
    private function isCompletePayment(array $payment): bool
    {
        return isset($payment['recipient'], $payment['amount'], $payment['currency'], $payment['payment_date']);
    }

    private function formatDate(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
