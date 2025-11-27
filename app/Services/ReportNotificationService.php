<?php

namespace App\Services;

use App\Constants\NotificationEvent;
use App\Constants\NotificationRecipientType;
use App\Mail\ReportEventNotificationMail;
use App\Models\NotificationRule;
use App\Models\NotificationRuleRecipient;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;

class ReportNotificationService
{
    /**
     * Relationships required to build notification payloads.
     *
     * @var array<int, string>
     */
    protected array $reportRelations = [
        'building.managementHistory.customer.manager',
        'createdBy',
        'notifier',
        'status',
        'subStatus',
        'currentStatusHistory.user',
    ];

    /**
     * @param array<string, mixed> $context
     */
    public function dispatch(string $event, Report $report, array $context = []): void
    {
        if (!in_array($event, NotificationEvent::all(), true)) {
            return;
        }

        $report->loadMissing($this->reportRelations);

        $rules = NotificationRule::query()
            ->with(['recipients', 'status', 'subStatus'])
            ->where('event', $event)
            ->where('is_active', true)
            ->get()
            ->filter(fn (NotificationRule $rule) => $this->ruleMatchesContext($rule, $context));

        if ($rules->isEmpty()) {
            return;
        }

        $message = $this->buildMessage($event, $report, $context);
        if ($message === null) {
            return;
        }

        $dispatched = [];

        foreach ($rules as $rule) {
            $emails = $this->resolveRecipientsForRule($rule, $report);
            if (empty($emails)) {
                continue;
            }

            foreach ($emails as $email) {
                if (isset($dispatched[$email])) {
                    continue;
                }

                Mail::to($email)->send(new ReportEventNotificationMail(
                    $report,
                    $message['subject'],
                    $message['intro'],
                    $message['details'],
                ));
                $dispatched[$email] = true;
            }
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function buildMessage(string $event, Report $report, array $context): ?array
    {
        $reportIdentifier = $report->damage_id ?: $report->uuid;
        $buildingName = $report->building?->name;

        return match ($event) {
            NotificationEvent::REPORT_CREATED => [
                'subject' => "Report {$reportIdentifier} created",
                'intro' => trim("A new report has been created" . ($buildingName ? " for {$buildingName}" : '') . '.'),
                'details' => [
                    'Damage type' => $report->damage_type,
                    'Current status' => $report->status?->name ?? 'Unknown',
                    'Claimant' => $report->claimant_name ?: 'N/A',
                    'Created by' => $report->createdBy?->name ?: 'N/A',
                ],
            ],
            NotificationEvent::DAMAGE_ID_UPDATED => [
                'subject' => "Damage ID updated for report {$reportIdentifier}",
                'intro' => 'The insurer damage identifier has been updated.',
                'details' => [
                    'Previous damage ID' => Arr::get($context, 'previous_damage_id') ?: 'N/A',
                    'New damage ID' => $report->damage_id ?: 'N/A',
                    'Changed by' => $report->currentStatusHistory?->user?->name ?: 'N/A',
                ],
            ],
            NotificationEvent::STATUS_CHANGED => [
                'subject' => "Report {$reportIdentifier} status changed",
                'intro' => 'The report status has been updated.',
                'details' => [
                    'Previous status' => Arr::get($context, 'previous_status_name') ?: 'N/A',
                    'Previous sub-status' => Arr::get($context, 'previous_sub_status_name') ?: 'N/A',
                    'New status' => $report->status?->name ?? 'N/A',
                    'New sub-status' => $report->subStatus?->name ?? 'N/A',
                    'Changed by' => $report->currentStatusHistory?->user?->name ?: 'N/A',
                    'Comment' => $report->currentStatusHistory?->comment ?: '—',
                ],
            ],
            NotificationEvent::REPORT_CLOSED => [
                'subject' => "Report {$reportIdentifier} closed",
                'intro' => 'The report has been closed.',
                'details' => [
                    'Closure reason' => $report->subStatus?->name ?? 'N/A',
                    'Closed by' => $report->currentStatusHistory?->user?->name ?: 'N/A',
                    'Comment' => $report->currentStatusHistory?->comment ?: '—',
                ],
            ],
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $context
     */
    private function ruleMatchesContext(NotificationRule $rule, array $context): bool
    {
        $statusId = $context['status_id'] ?? null;
        $subStatusId = $context['sub_status_id'] ?? null;

        if ($rule->status_id && $rule->status_id !== $statusId) {
            return false;
        }

        if ($rule->sub_status_id && $rule->sub_status_id !== $subStatusId) {
            return false;
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    private function resolveRecipientsForRule(NotificationRule $rule, Report $report): array
    {
        $emails = [];

        foreach ($rule->recipients as $recipient) {
            $emails = array_merge($emails, $this->resolveRecipient($recipient, $report));
        }

        return array_values(array_unique(array_filter($emails, fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))));
    }

    /**
     * @return array<int, string>
     */
    private function resolveRecipient(NotificationRuleRecipient $recipient, Report $report): array
    {
        return match ($recipient->recipient_type) {
            NotificationRecipientType::CUSTOM_EMAIL => $recipient->recipient_value ? [$recipient->recipient_value] : [],
            NotificationRecipientType::ROLE => $this->emailsForRole($recipient->recipient_value),
            NotificationRecipientType::REPORT_CREATOR => $this->emailsFromValue($report->createdBy?->email),
            NotificationRecipientType::REPORT_NOTIFIER => $this->emailsFromValue($report->notifier?->email),
            NotificationRecipientType::REPORT_CLAIMANT => $this->emailsFromValue($report->claimant_email),
            NotificationRecipientType::BUILDING_CUSTOMER => $this->emailsFromValue($report->building?->current_customer?->email),
            NotificationRecipientType::BUILDING_CUSTOMER_MANAGER => $this->emailsFromValue(
                $report->building?->current_customer?->manager?->email
            ),
            default => [],
        };
    }

    /**
     * @return array<int, string>
     */
    private function emailsForRole(?string $role): array
    {
        if (!$role) {
            return [];
        }

        return User::query()
            ->where('role', $role)
            ->where('is_active', true)
            ->pluck('email')
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function emailsFromValue(?string $email): array
    {
        return $email && filter_var($email, FILTER_VALIDATE_EMAIL) ? [$email] : [];
    }
}
