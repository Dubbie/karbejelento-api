<?php

namespace App\Services;

use App\Constants\NotificationEvent;
use App\Constants\ReportStatus;
use App\Models\Building;
use App\Models\Notifier;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReportService
{
    public function __construct(private ReportNotificationService $notificationService) {}

    public function createReport(array $data, User $user): Report
    {
        $building = Building::where('uuid', $data['building_uuid'])->first();
        $notifier = Notifier::where('uuid', $data['notifier_uuid'])->first();
        $defaultStatus = Status::where('name', ReportStatus::REPORTED_TO_DAMARISK)->firstOrFail();

        if (!$building) {
            throw new \InvalidArgumentException('Invalid building UUID.');
        }

        if (!$building->insurer_id) {
            throw new \InvalidArgumentException('Building is missing an insurer reference.');
        }

        if (!$notifier) {
            throw new \InvalidArgumentException('Invalid notifier UUID.');
        }

        unset($data['building_uuid'], $data['notifier_uuid']);

        $report = DB::transaction(function () use ($data, $user, $building, $notifier, $defaultStatus) {
            $report = Report::create(array_merge($data, [
                'uuid' => Str::uuid(),
                'created_by_user_id' => $user->id,
                'building_id' => $building->id,
                'notifier_id' => $notifier->id,
                'status_id' => $defaultStatus->id,
                'sub_status_id' => null,
                'bond_number' => $building->bond_number, // Snapshot data
                'insurer_id' => $building->insurer_id,
            ]));

            $report = $this->changeReportStatus($report, $defaultStatus->id, null, [
                'user_id' => $user->id,
                'comment' => 'Report created.',
                'notify' => false,
            ]);

            return $report;
        });

        $this->notificationService->dispatch(NotificationEvent::REPORT_CREATED, $report, [
            'status_id' => $report->status_id,
            'sub_status_id' => $report->sub_status_id,
            'triggered_by_user_id' => $user->id,
        ]);

        return $report;
    }

    public function getAllReportsForUser(User $user, Request $request): array
    {
        $query = Report::forUser($user)
            ->with(['building.insurer', 'notifier', 'status', 'subStatus', 'insurer', 'duplicateOfReport']); // Eager load relationships

        // -- Apply Filters --
        if ($search = $request->input('searchQuery')) {
            $query->where(function ($q) use ($search) {
                $q->where('damage_id', 'ILIKE', "%{$search}%")
                    ->orWhereHas('building', fn($bq) => $bq->where('name', 'ILIKE', "%{$search}%"));
            });
        }
        if ($statuses = $request->input('statuses')) {
            $statuses = is_array($statuses) ? $statuses : explode(',', $statuses);
            $query->whereHas('status', fn($q) => $q->whereIn('name', $statuses));
        }
        if ($dateFrom = $request->input('dateFrom')) {
            $query->where('created_at', '>=', $dateFrom);
        }
        // ... add other filters (dateTo, damageType)

        return $query->advancedPaginate($request, [
            'sortableFields' => ['id', 'damage_id', 'created_at'],
            'filterableFields' => [], // Custom filtering is handled above
        ]);
    }

    public function getAllReportsForBuilding(Building $building, Request $request): array
    {
        $query = Report::forBuilding($building)
            ->with(['building.insurer', 'notifier', 'status', 'subStatus', 'insurer', 'duplicateOfReport']); // Eager load relationships

        // -- Apply Filters --
        if ($search = $request->input('searchQuery')) {
            $query->where(function ($q) use ($search) {
                $q->where('damage_id', 'ILIKE', "%{$search}%")
                    ->orWhereHas('building', fn($bq) => $bq->where('name', 'ILIKE', "%{$search}%"));
            });
        }
        if ($statuses = $request->input('statuses')) {
            $statuses = is_array($statuses) ? $statuses : explode(',', $statuses);
            $query->whereHas('status', fn($q) => $q->whereIn('name', $statuses));
        }
        if ($dateFrom = $request->input('dateFrom')) {
            $query->where('created_at', '>=', $dateFrom);
        }
        // ... add other filters (dateTo, damageType)

        return $query->advancedPaginate($request, [
            'sortableFields' => ['id', 'damage_id', 'created_at'],
            'filterableFields' => [], // Custom filtering is handled above
        ]);
    }

    public function updateReport(Report $report, array $data): Report
    {
        $report->update($data);
        return $report->fresh();
    }

    /**
     * @param UploadedFile[] $files
     */
    public function addAttachments(Report $report, array $files, array $categories, User $user): Collection
    {
        $createdAttachments = []; // Start with a plain array

        foreach ($files as $index => $file) {
            $path = $file->store('attachments', 'public');

            $createdAttachments[] = ReportAttachment::create([
                'uuid' => Str::uuid(),
                'report_id' => $report->id,
                'uploaded_by_user_id' => $user->id,
                'file_path' => $path,
                'file_name_original' => $file->getClientOriginalName(),
                'file_mime_type' => $file->getMimeType(),
                'file_size_bytes' => $file->getSize(),
                'category' => $categories[$index] ?? 'other',
            ]);
        }

        // Convert the plain array of models into a proper Eloquent Collection
        return new Collection($createdAttachments);
    }

    /**
     * The single, authoritative method for changing a report's status.
     * It handles history creation, report updates, attachments, and notifications.
     *
     * @param Report $report The report to update.
     * @param int $statusId The ID of the new main status.
     * @param int|null $subStatusId The ID of the new sub-status, or null.
     * @param array $options Additional data like 'comment', 'user_id', 'attachments'.
     * @return Report The updated report instance.
     */
    public function changeReportStatus(Report $report, int $statusId, ?int $subStatusId, array $options = []): Report
    {
        $report->loadMissing(['status', 'subStatus']);
        $status = Status::with('subStatuses')->findOrFail($statusId);
        $subStatus = null;

        if ($subStatusId !== null) {
            $subStatus = $status->subStatuses->firstWhere('id', $subStatusId);

            if (!$subStatus) {
                throw ValidationException::withMessages([
                    'sub_status_id' => ['The provided sub-status does not belong to the selected status.'],
                ]);
            }
        }

        $previousStatusId = $report->status?->id;
        $previousStatusName = $report->status?->name;
        $previousSubStatusId = $report->subStatus?->id;
        $previousSubStatusName = $report->subStatus?->name;
        $shouldNotify = (bool) ($options['notify'] ?? true);

        $updatedReport = DB::transaction(function () use ($report, $status, $subStatus, $options) {
            $comment = $options['comment'] ?? null;
            $comment = $comment !== null ? trim((string) $comment) : null;

            $history = $report->statusHistories()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $options['user_id'] ?? Auth::id(),
                'status_id' => $status->id,
                'sub_status_id' => $subStatus?->id,
                'comment' => $comment,
            ]);

            $report->update([
                'status_id' => $status->id,
                'sub_status_id' => $subStatus?->id,
                'current_status_history_id' => $history->id,
            ]);
            return $report->fresh(['status', 'subStatus', 'currentStatusHistory.user']);
        });

        if ($shouldNotify) {
            $event = $status->name === ReportStatus::CLOSED
                ? NotificationEvent::REPORT_CLOSED
                : NotificationEvent::STATUS_CHANGED;

            $this->notificationService->dispatch($event, $updatedReport, [
                'status_id' => $status->id,
                'sub_status_id' => $subStatus?->id,
                'previous_status_id' => $previousStatusId,
                'previous_status_name' => $previousStatusName,
                'previous_sub_status_id' => $previousSubStatusId,
                'previous_sub_status_name' => $previousSubStatusName,
                'triggered_by_user_id' => $options['user_id'] ?? Auth::id(),
            ]);
        }

        return $updatedReport;
    }

    public function updateDamageId(Report $report, string $damageId, User $actor, ?string $comment = null): Report
    {
        $previousDamageId = $report->damage_id;

        $updatedReport = DB::transaction(function () use ($report, $damageId, $actor, $comment) {
            $report->update([
                'damage_id' => $damageId,
            ]);

            $history = $report->statusHistories()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $actor->id,
                'status_id' => $report->status_id,
                'sub_status_id' => $report->sub_status_id,
                'comment' => $comment ?? 'Damage ID updated.',
            ]);

            $report->update(['current_status_history_id' => $history->id]);

            return $report->fresh(['status', 'subStatus', 'currentStatusHistory.user']);
        });

        $this->notificationService->dispatch(NotificationEvent::DAMAGE_ID_UPDATED, $updatedReport, [
            'previous_damage_id' => $previousDamageId,
            'status_id' => $updatedReport->status_id,
            'sub_status_id' => $updatedReport->sub_status_id,
            'triggered_by_user_id' => $actor->id,
        ]);

        return $updatedReport;
    }
}
