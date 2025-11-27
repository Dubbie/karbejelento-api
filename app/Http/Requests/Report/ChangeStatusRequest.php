<?php

namespace App\Http\Requests\Report;

use App\Constants\ReportStatus;
use App\Constants\ReportSubStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class ChangeStatusRequest extends FormRequest
{
    /**
     * @var array<int, UploadedFile>|null
     */
    private ?array $attachmentFiles = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isDocumentRequest = $this->isDocumentRequestTransition();
        $isClosingWithPayment = $this->isClosingWithPaymentTransition();
        $isClosingTransition = $this->isClosingTransition();
        $isClosingDuplicate = $this->isClosingDuplicateTransition();

        return [
            'status' => ['required', 'string', Rule::exists('statuses', 'name')],
            'sub_status' => ['nullable', 'string', Rule::exists('sub_statuses', 'name')],
            'comment' => [Rule::requiredIf($isClosingTransition), 'string', 'max:2000'],
            'payload' => ['nullable', 'array'],
            'payload.email_title' => [Rule::requiredIf($isDocumentRequest), 'string', 'max:255'],
            'payload.email_body' => [Rule::requiredIf($isDocumentRequest), 'string', 'max:5000'],
            'payload.requested_documents' => [
                Rule::requiredIf($isDocumentRequest),
                'array',
                'min:1',
            ],
            'payload.requested_documents.*' => ['string', 'max:255'],
            'payload.other_document_note' => ['nullable', 'string', 'max:2000'],
            'payload.attachments' => ['nullable', 'array'],
            'payload.attachments.*.file' => ['file', 'max:10240'], // 10MB per file
            'payload.attachments.*.files' => ['file', 'max:10240'],
            'payload.closing_payments' => [Rule::requiredIf($isClosingWithPayment), 'array', 'min:1'],
            'payload.closing_payments.*.recipient' => ['required_with:payload.closing_payments', 'string', 'max:255'],
            'payload.closing_payments.*.amount' => ['required_with:payload.closing_payments', 'numeric', 'min:0'],
            'payload.closing_payments.*.currency' => ['required_with:payload.closing_payments', 'string', 'size:3'],
            'payload.closing_payments.*.payment_date' => ['required_with:payload.closing_payments', 'date'],
            'payload.closing_payments.*.payment_time' => ['nullable', 'date_format:H:i'],
            'payload.duplicate_report_uuid' => [
                Rule::requiredIf($isClosingDuplicate),
                'string',
                'uuid',
                Rule::exists('reports', 'uuid'),
            ],
        ];
    }

    /**
     * Merge the optional payload bag with top-level fields like comment.
     *
     * @return array<string, mixed>
     */
    public function transitionPayload(): array
    {
        $payload = $this->input('payload', []);

        if (!is_array($payload)) {
            $payload = [];
        }

        if ($this->filled('comment')) {
            $payload['comment'] = $this->input('comment');
        }

        $attachments = $this->getAttachmentFiles();
        if (!empty($attachments)) {
            $payload['attachments'] = $attachments;
        }

        return $payload;
    }

    private function isDocumentRequestTransition(): bool
    {
        return $this->input('status') === ReportStatus::DATA_OR_DOCUMENT_DEFICIENCY
            && $this->input('sub_status') === ReportSubStatus::DEFICIENCY_WAITING_FOR_DOCUMENT_FROM_CLIENT;
    }

    private function isClosingWithPaymentTransition(): bool
    {
        return $this->input('status') === ReportStatus::CLOSED
            && $this->input('sub_status') === ReportSubStatus::CLOSED_WITH_PAYMENT;
    }

    private function isClosingTransition(): bool
    {
        return $this->input('status') === ReportStatus::CLOSED;
    }

    private function isClosingDuplicateTransition(): bool
    {
        return $this->input('status') === ReportStatus::CLOSED
            && $this->input('sub_status') === ReportSubStatus::CLOSED_DUPLICATE_REPORT;
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function getAttachmentFiles(): array
    {
        if ($this->attachmentFiles !== null) {
            return $this->attachmentFiles;
        }

        $raw = Arr::get($this->allFiles(), 'payload.attachments', []);

        return $this->attachmentFiles = $this->flattenAttachmentFiles($raw);
    }

    /**
     * @param mixed $value
     * @return array<int, UploadedFile>
     */
    private function flattenAttachmentFiles($value): array
    {
        if ($value instanceof UploadedFile) {
            return [$value];
        }

        if (!is_array($value)) {
            return [];
        }

        $collected = [];

        foreach ($value as $nestedValue) {
            $collected = array_merge($collected, $this->flattenAttachmentFiles($nestedValue));
        }

        return $collected;
    }
}
