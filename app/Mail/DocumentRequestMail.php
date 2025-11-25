<?php

namespace App\Mail;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var UploadedFile[]
     */
    public array $mailAttachments;

    /**
     * @param UploadedFile[] $attachments
     */
    public function __construct(
        public Report $report,
        public string $emailTitle,
        public string $emailBody,
        public array $requestedDocuments,
        public ?string $otherDocumentNote = null,
        array $attachments = []
    ) {
        $this->mailAttachments = $attachments;
    }

    public function build(): self
    {
        $mail = $this->subject($this->emailTitle)
            ->view('emails.report-document-request')
            ->with([
                'report' => $this->report,
                'emailTitle' => $this->emailTitle,
                'emailBody' => $this->emailBody,
                'requestedDocuments' => $this->requestedDocuments,
                'otherDocumentNote' => $this->otherDocumentNote,
            ]);

        foreach ($this->mailAttachments as $attachment) {
            if (!$attachment instanceof UploadedFile) {
                continue;
            }

            $mail->attach(
                $attachment->getRealPath(),
                [
                    'as' => $attachment->getClientOriginalName(),
                    'mime' => $attachment->getClientMimeType() ?? $attachment->getMimeType(),
                ]
            );
        }

        return $mail;
    }
}
