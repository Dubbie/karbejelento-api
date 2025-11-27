<?php

namespace Tests\Feature;

use App\Constants\NotificationEvent;
use App\Constants\NotificationRecipientType;
use App\Constants\ReportStatus;
use App\Mail\ReportEventNotificationMail;
use App\Models\BuildingManagement;
use App\Models\NotificationRule;
use App\Models\Report;
use App\Models\ReportStatusHistory;
use App\Models\Status;
use App\Models\SubStatus;
use App\Models\User;
use App\Services\ReportNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReportNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_emails_for_matching_rules(): void
    {
        Mail::fake();

        $status = Status::factory()->create(['name' => ReportStatus::WAITING_FOR_INSURER_DAMAGE_ID]);
        $subStatus = SubStatus::factory()->create(['status_id' => $status->id, 'name' => 'Awaiting Contact']);
        $report = $this->makeReportWithCurrentHistory($status, $subStatus);

        $rule = NotificationRule::factory()
            ->for($status, 'status')
            ->for($subStatus, 'subStatus')
            ->create(['event' => NotificationEvent::STATUS_CHANGED]);

        $rule->recipients()->createMany([
            [
                'recipient_type' => NotificationRecipientType::REPORT_CLAIMANT,
                'recipient_value' => null,
            ],
            [
                'recipient_type' => NotificationRecipientType::REPORT_NOTIFIER,
                'recipient_value' => null,
            ],
        ]);

        $this->dispatchNotification(NotificationEvent::STATUS_CHANGED, $report, [
            'status_id' => $status->id,
            'sub_status_id' => $subStatus->id,
            'previous_status_name' => 'Previous',
            'previous_sub_status_name' => 'Old Sub',
        ]);

        Mail::assertSent(ReportEventNotificationMail::class, function (ReportEventNotificationMail $mail) use ($report) {
            return $mail->hasTo($report->claimant_email)
                && $mail->details['New status'] === ReportStatus::WAITING_FOR_INSURER_DAMAGE_ID;
        });

        Mail::assertSent(ReportEventNotificationMail::class, function (ReportEventNotificationMail $mail) use ($report) {
            return $mail->hasTo($report->notifier->email)
                && $mail->details['Previous status'] === 'Previous';
        });
    }

    public function test_it_respects_status_and_sub_status_filters(): void
    {
        Mail::fake();

        $matchingStatus = Status::factory()->create(['name' => ReportStatus::UNDER_INSURER_ADMINISTRATION]);
        $nonMatchingStatus = Status::factory()->create(['name' => ReportStatus::REPORTED_TO_DAMARISK]);
        $matchingSub = SubStatus::factory()->create(['status_id' => $matchingStatus->id]);
        $report = $this->makeReportWithCurrentHistory($matchingStatus, $matchingSub);

        $matchingRule = NotificationRule::factory()
            ->for($matchingStatus, 'status')
            ->for($matchingSub, 'subStatus')
            ->create(['event' => NotificationEvent::STATUS_CHANGED]);
        $matchingRule->recipients()->create([
            'recipient_type' => NotificationRecipientType::REPORT_CLAIMANT,
            'recipient_value' => null,
        ]);

        $nonMatchingRule = NotificationRule::factory()
            ->for($nonMatchingStatus, 'status')
            ->create(['event' => NotificationEvent::STATUS_CHANGED]);
        $nonMatchingRule->recipients()->create([
            'recipient_type' => NotificationRecipientType::REPORT_CLAIMANT,
            'recipient_value' => null,
        ]);

        $this->dispatchNotification(NotificationEvent::STATUS_CHANGED, $report, [
            'status_id' => $matchingStatus->id,
            'sub_status_id' => $matchingSub->id,
        ]);

        Mail::assertSent(ReportEventNotificationMail::class, 1);
    }

    public function test_it_deduplicates_recipients_across_rules(): void
    {
        Mail::fake();

        $status = Status::factory()->create(['name' => ReportStatus::DATA_OR_DOCUMENT_DEFICIENCY]);
        $report = $this->makeReportWithCurrentHistory($status, null);

        $firstRule = NotificationRule::factory()->create(['event' => NotificationEvent::REPORT_CREATED]);
        $firstRule->recipients()->create([
            'recipient_type' => NotificationRecipientType::CUSTOM_EMAIL,
            'recipient_value' => 'shared@example.com',
        ]);

        $secondRule = NotificationRule::factory()->create(['event' => NotificationEvent::REPORT_CREATED]);
        $secondRule->recipients()->create([
            'recipient_type' => NotificationRecipientType::CUSTOM_EMAIL,
            'recipient_value' => 'shared@example.com',
        ]);

        $this->dispatchNotification(NotificationEvent::REPORT_CREATED, $report, [
            'status_id' => $status->id,
        ]);

        Mail::assertSent(ReportEventNotificationMail::class, 1);
    }

    public function test_role_recipients_receive_emails(): void
    {
        Mail::fake();

        $status = Status::factory()->create(['name' => ReportStatus::REPORTED_TO_DAMARISK]);
        $report = $this->makeReportWithCurrentHistory($status, null);
        $solver = User::factory()->create(['role' => 'damage_solver', 'email' => 'solver@example.com']);

        $rule = NotificationRule::factory()->create(['event' => NotificationEvent::REPORT_CREATED]);
        $rule->recipients()->create([
            'recipient_type' => NotificationRecipientType::ROLE,
            'recipient_value' => $solver->role,
        ]);

        $this->dispatchNotification(NotificationEvent::REPORT_CREATED, $report, [
            'status_id' => $status->id,
        ]);

        Mail::assertSent(ReportEventNotificationMail::class, function (ReportEventNotificationMail $mail) use ($solver) {
            return $mail->hasTo($solver->email);
        });
    }

    private function makeReportWithCurrentHistory(Status $status, ?SubStatus $subStatus): Report
    {
        $report = Report::factory()->create([
            'status_id' => $status->id,
            'sub_status_id' => $subStatus?->id,
        ]);

        $manager = User::factory()->manager()->create();
        $customer = User::factory()->customer()->create(['manager_id' => $manager->id]);

        BuildingManagement::factory()->create([
            'building_id' => $report->building_id,
            'customer_id' => $customer->id,
            'insurer_id' => $report->building->insurer_id,
        ]);

        $history = ReportStatusHistory::factory()->create([
            'report_id' => $report->id,
            'status_id' => $status->id,
            'sub_status_id' => $subStatus?->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $report->update(['current_status_history_id' => $history->id]);

        return $report->fresh([
            'notifier',
            'status',
            'subStatus',
            'currentStatusHistory.user',
            'building.managementHistory.customer.manager',
        ]);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function dispatchNotification(string $event, Report $report, array $context): void
    {
        app(ReportNotificationService::class)->dispatch($event, $report, $context);
    }
}
