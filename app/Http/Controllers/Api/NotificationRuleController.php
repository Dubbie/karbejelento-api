<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationRule\StoreNotificationRuleRequest;
use App\Http\Requests\NotificationRule\UpdateNotificationRuleRequest;
use App\Http\Resources\NotificationRuleResource;
use App\Models\NotificationRule;
use App\Models\Status;
use App\Models\SubStatus;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NotificationRuleController extends Controller
{
    public function index()
    {
        $rules = NotificationRule::query()
            ->with(['recipients', 'status', 'subStatus'])
            ->orderBy('name')
            ->get();

        return NotificationRuleResource::collection($rules);
    }

    public function store(StoreNotificationRuleRequest $request)
    {
        $validated = $request->validated();

        [$statusId, $subStatusId] = $this->resolveStatusReferences(
            $validated['status_uuid'] ?? null,
            $validated['sub_status_uuid'] ?? null
        );

        $this->assertSubStatusBelongsToStatus($statusId, $subStatusId);

        $rule = DB::transaction(function () use ($validated, $statusId, $subStatusId) {
            $rule = NotificationRule::create([
                'name' => $validated['name'],
                'event' => $validated['event'],
                'status_id' => $statusId,
                'sub_status_id' => $subStatusId,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $this->syncRecipients($rule, $validated['recipients']);

            return $rule->fresh(['recipients', 'status', 'subStatus']);
        });

        return NotificationRuleResource::make($rule)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(NotificationRule $notificationRule)
    {
        $notificationRule->load(['recipients', 'status', 'subStatus']);

        return NotificationRuleResource::make($notificationRule);
    }

    public function update(UpdateNotificationRuleRequest $request, NotificationRule $notificationRule)
    {
        $validated = $request->validated();

        $statusUuidProvided = Arr::exists($validated, 'status_uuid');
        $subStatusUuidProvided = Arr::exists($validated, 'sub_status_uuid');

        [$newStatusId, $newSubStatusId] = $this->resolveStatusReferences(
            $validated['status_uuid'] ?? null,
            $validated['sub_status_uuid'] ?? null
        );

        $statusIdForValidation = $statusUuidProvided ? $newStatusId : $notificationRule->status_id;
        $subStatusIdForValidation = $subStatusUuidProvided ? $newSubStatusId : $notificationRule->sub_status_id;

        $this->assertSubStatusBelongsToStatus($statusIdForValidation, $subStatusIdForValidation);

        DB::transaction(function () use ($notificationRule, $validated, $statusUuidProvided, $subStatusUuidProvided, $newStatusId, $newSubStatusId) {
            $fieldsToUpdate = Arr::only($validated, ['name', 'event', 'is_active']);

            if ($statusUuidProvided) {
                $fieldsToUpdate['status_id'] = $newStatusId;
            }

            if ($subStatusUuidProvided) {
                $fieldsToUpdate['sub_status_id'] = $newSubStatusId;
            }

            if (!empty($fieldsToUpdate)) {
                $notificationRule->update($fieldsToUpdate);
            }

            if (Arr::exists($validated, 'recipients')) {
                $this->syncRecipients($notificationRule, $validated['recipients']);
            }
        });

        $notificationRule->load(['recipients', 'status', 'subStatus']);

        return NotificationRuleResource::make($notificationRule);
    }

    public function destroy(NotificationRule $notificationRule)
    {
        $notificationRule->delete();

        return response()->noContent();
    }

    private function syncRecipients(NotificationRule $rule, array $recipients): void
    {
        $rule->recipients()->delete();

        foreach ($recipients as $recipient) {
            $rule->recipients()->create([
                'recipient_type' => $recipient['type'],
                'recipient_value' => $recipient['value'] ?? null,
            ]);
        }
    }

    private function resolveStatusReferences(?string $statusUuid, ?string $subStatusUuid): array
    {
        $statusId = null;
        $subStatusId = null;

        if ($statusUuid) {
            $statusId = Status::where('uuid', $statusUuid)->value('id');
        }

        if ($subStatusUuid) {
            $subStatusId = SubStatus::where('uuid', $subStatusUuid)->value('id');
        }

        return [$statusId, $subStatusId];
    }

    private function assertSubStatusBelongsToStatus(?int $statusId, ?int $subStatusId): void
    {
        if (!$statusId || !$subStatusId) {
            return;
        }

        $isValid = SubStatus::where('id', $subStatusId)
            ->where('status_id', $statusId)
            ->exists();

        if (!$isValid) {
            throw ValidationException::withMessages([
                'sub_status_uuid' => ['The selected sub-status does not belong to the provided status.'],
            ]);
        }
    }
}
