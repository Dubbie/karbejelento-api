<?php

namespace Tests\Feature;

use App\Constants\NotificationEvent;
use App\Constants\NotificationRecipientType;
use App\Constants\UserRole;
use App\Models\NotificationRule;
use App\Models\Status;
use App\Models\SubStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationRuleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_notification_rules(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $status = Status::factory()->create(['name' => 'Status A']);
        $subStatus = SubStatus::factory()->create(['status_id' => $status->id, 'name' => 'Sub A']);

        $payload = [
            'name' => 'Customer alert',
            'event' => NotificationEvent::STATUS_CHANGED,
            'status_uuid' => $status->uuid,
            'sub_status_uuid' => $subStatus->uuid,
            'recipients' => [
                ['type' => NotificationRecipientType::REPORT_CLAIMANT],
                ['type' => NotificationRecipientType::ROLE, 'value' => UserRole::DAMAGE_SOLVER],
            ],
        ];

        $createResponse = $this->postJson('/api/v1/notification-rules', $payload);
        $createResponse->assertCreated()
            ->assertJsonPath('name', 'Customer alert')
            ->assertJsonPath('status.uuid', $status->uuid)
            ->assertJsonCount(2, 'recipients');

        $ruleUuid = $createResponse->json('uuid');

        $this->assertDatabaseHas('notification_rules', [
            'uuid' => $ruleUuid,
            'status_id' => $status->id,
            'sub_status_id' => $subStatus->id,
        ]);

        $listResponse = $this->getJson('/api/v1/notification-rules');
        $listResponse->assertOk()->assertJsonFragment(['uuid' => $ruleUuid]);

        $updateResponse = $this->patchJson('/api/v1/notification-rules/' . $ruleUuid, [
            'name' => 'Updated alert',
            'is_active' => false,
            'recipients' => [
                ['type' => NotificationRecipientType::CUSTOM_EMAIL, 'value' => 'ops@example.com'],
            ],
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('name', 'Updated alert')
            ->assertJsonPath('is_active', false)
            ->assertJsonCount(1, 'recipients');

        $ruleId = NotificationRule::where('uuid', $ruleUuid)->value('id');

        $this->assertDatabaseHas('notification_rule_recipients', [
            'notification_rule_id' => $ruleId,
            'recipient_type' => NotificationRecipientType::CUSTOM_EMAIL,
            'recipient_value' => 'ops@example.com',
        ]);

        $deleteResponse = $this->deleteJson('/api/v1/notification-rules/' . $ruleUuid);
        $deleteResponse->assertNoContent();

        $this->assertDatabaseMissing('notification_rules', ['uuid' => $ruleUuid]);
    }

    public function test_sub_status_must_belong_to_status(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $status = Status::factory()->create(['name' => 'Parent']);
        $otherStatus = Status::factory()->create(['name' => 'Other']);
        $foreignSub = SubStatus::factory()->create(['status_id' => $otherStatus->id]);

        $response = $this->postJson('/api/v1/notification-rules', [
            'name' => 'Invalid rule',
            'event' => NotificationEvent::STATUS_CHANGED,
            'status_uuid' => $status->uuid,
            'sub_status_uuid' => $foreignSub->uuid,
            'recipients' => [
                ['type' => NotificationRecipientType::REPORT_NOTIFIER],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('sub_status_uuid');
    }

    public function test_non_admin_users_are_forbidden(): void
    {
        Sanctum::actingAs(User::factory()->manager()->create());

        $response = $this->getJson('/api/v1/notification-rules');

        $response->assertStatus(403);
    }
}
