<?php

namespace App\Mail;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReportEventNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<string, string> $details
     */
    public function __construct(
        public Report $report,
        private string $subjectLine,
        private string $introLine,
        public array $details = []
    ) {}

    public function build(): self
    {
        return $this
            ->subject($this->subjectLine)
            ->view('emails.report-event-notification')
            ->with([
                'subjectLine' => $this->subjectLine,
                'introLine' => $this->introLine,
                'details' => $this->details,
                'report' => $this->report,
            ]);
    }
}
