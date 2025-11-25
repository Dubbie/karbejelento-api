<?php

namespace App\Services\ReportStatusTransitions\Rules;

use App\Constants\ReportStatus;
use App\Constants\ReportSubStatus;
use App\Mail\DocumentRequestMail;
use App\Models\Report;
use App\Models\Status;
use App\Models\SubStatus;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SendDocumentRequestEmailRule implements ReportStatusTransitionRule
{
    public function __construct(protected ReportService $reportService) {}

    public function supports(Report $report, Status $targetStatus, ?SubStatus $subStatus): bool
    {
        return $targetStatus->name === ReportStatus::DATA_OR_DOCUMENT_DEFICIENCY
            && $subStatus?->name === ReportSubStatus::DEFICIENCY_WAITING_FOR_DOCUMENT_FROM_CLIENT;
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
        $report->loadMissing(['notifier', 'building']);

        $recipients = $this->resolveRecipients($report);

        if (empty($recipients)) {
            throw ValidationException::withMessages([
                'recipient' => ['No recipient email address is available for this report.'],
            ]);
        }

        $emailTitle = (string) ($payload['email_title'] ?? '');
        $emailBody = (string) ($payload['email_body'] ?? '');
        $requestedDocuments = $payload['requested_documents'] ?? [];
        $otherDocumentNote = $payload['other_document_note'] ?? null;
        $attachments = $payload['attachments'] ?? [];
        $attachments = is_array($attachments) ? $attachments : [];

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->send(new DocumentRequestMail(
                $report,
                $emailTitle,
                $emailBody,
                $requestedDocuments,
                $otherDocumentNote,
                $attachments
            ));
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
    }

    /**
     * @return array<int, string>
     */
    private function resolveRecipients(Report $report): array
    {
        $emails = [
            $report->claimant_email,
            $report->notifier?->email,
        ];

        return array_values(array_unique(array_filter($emails)));
    }
}
