<?php

namespace App\Services\ReportStatusTransitions\Rules;

use App\Constants\ReportStatus;
use App\Constants\ReportSubStatus;
use App\Mail\DocumentRequestMail;
use App\Models\DocumentRequest;
use App\Models\Report;
use App\Models\Status;
use App\Models\SubStatus;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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

        $documentRequest = $this->createDocumentRequest(
            $report,
            $actor,
            $emailTitle,
            $emailBody,
            $requestedDocuments,
            $otherDocumentNote
        );

        $documentRequestUrl = $this->buildDocumentRequestUrl($documentRequest);

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->send(new DocumentRequestMail(
                $report,
                $emailTitle,
                $emailBody,
                $requestedDocuments,
                $otherDocumentNote,
                $documentRequestUrl,
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

    /**
     * @param array<int, string> $requestedDocuments
     */
    private function createDocumentRequest(
        Report $report,
        User $actor,
        string $emailTitle,
        string $emailBody,
        array $requestedDocuments,
        ?string $otherDocumentNote
    ): DocumentRequest {
        $documents = array_values(array_filter($requestedDocuments, fn ($doc) => is_string($doc) && $doc !== ''));

        return DB::transaction(function () use (
            $report,
            $actor,
            $emailTitle,
            $emailBody,
            $documents,
            $otherDocumentNote
        ) {
            $documentRequest = DocumentRequest::create([
                'uuid' => (string) Str::uuid(),
                'report_id' => $report->id,
                'requested_by_user_id' => $actor->id,
                'email_title' => $emailTitle,
                'email_body' => $emailBody,
                'requested_documents' => $documents,
                'other_document_note' => $otherDocumentNote,
                'public_token' => Str::random(48),
                'sent_at' => now(),
            ]);

            foreach ($documents as $index => $label) {
                $documentRequest->items()->create([
                    'uuid' => (string) Str::uuid(),
                    'label' => $label,
                    'position' => $index + 1,
                ]);
            }

            return $documentRequest;
        });
    }

    private function buildDocumentRequestUrl(DocumentRequest $documentRequest): ?string
    {
        $base = config('client.document_request_base_url');

        if (!$base) {
            return null;
        }

        return rtrim($base, '/') . '/' . $documentRequest->uuid . '?token=' . $documentRequest->public_token;
    }
}
