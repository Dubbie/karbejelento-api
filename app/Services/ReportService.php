<?php

namespace App\Services;

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

class ReportService
{
    public function createReport(array $data, User $user): Report
    {
        $building = Building::where('uuid', $data['building_uuid'])->first();
        $notifier = Notifier::where('uuid', $data['notifier_uuid'])->first();
        $defaultStatus = Status::where('name', ReportStatus::REPORTED_TO_DAMARISK)->firstOrFail();

        if (!$building) {
            throw new \InvalidArgumentException('Invalid building UUID.');
        }

        if (!$notifier) {
            throw new \InvalidArgumentException('Invalid notifier UUID.');
        }

        unset($data['building_uuid'], $data['notifier_uuid']);

        return DB::transaction(function () use ($data, $user, $building, $notifier, $defaultStatus) {
            // Create the report
            $report = Report::create(array_merge($data, [
                'uuid' => Str::uuid(),
                'created_by_user_id' => $user->id,
                'building_id' => $building->id,
                'notifier_id' => $notifier->id,
                'status_id' => $defaultStatus->id,
                'bond_number' => $building->bond_number, // Snapshot data
                'insurer' => $building->insurer,
            ]));

            // Create the default status history
            $statusHistory = $report->statusHistories()->create([
                'uuid' => Str::uuid(),
                'user_id' => $options['user_id'] ?? Auth::id(),
                'status_id' => $defaultStatus->id,
                'sub_status_id' => null,
            ]);

            // Update the report's current status
            $report->current_status_history_id = $statusHistory->id;
            $report->save();

            // Refresh the report so it has everything
            $report->refresh();

            return $report;
        });
    }

    public function getAllReportsForUser(User $user, Request $request): array
    {
        $query = Report::forUser($user)
            ->with(['building', 'notifier', 'status', 'subStatus']); // Eager load relationships

        // -- Apply Filters --
        if ($search = $request->input('searchQuery')) {
            $query->where(function ($q) use ($search) {
                $q->where('damage_id', 'ILIKE', "%{$search}%")
                    ->orWhereHas('building', fn($bq) => $bq->where('name', 'ILIKE', "%{$search}%"));
            });
        }
        if ($statuses = $request->input('statuses')) {
            $statuses = explode(',', $statuses);
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
            ->with(['building', 'notifier', 'status', 'subStatus']); // Eager load relationships

        // -- Apply Filters --
        if ($search = $request->input('searchQuery')) {
            $query->where(function ($q) use ($search) {
                $q->where('damage_id', 'ILIKE', "%{$search}%")
                    ->orWhereHas('building', fn($bq) => $bq->where('name', 'ILIKE', "%{$search}%"));
            });
        }
        if ($statuses = $request->input('statuses')) {
            $query->whereIn('current_status', is_array($statuses) ? $statuses : [$statuses]);
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
        // Use a transaction to ensure all database operations succeed or none do.
        DB::transaction(function () use ($report, $statusId, $subStatusId, $options) {
            $comment = $options['comment'] ?? null;

            // if (isset($options['missing_inspection_data'])) {
            //     $missingData = $options['missing_inspection_data'];
            //     if ($missingData['action'] === 'update_contact') {
            //         // Log the change and update the report's inspector phone.
            //         $oldPhone = $report->inspector_phone;
            //         $comment .= "\n[Rendszer] Elérhetőség frissítve. Régi: {$oldPhone}, Új: {$missingData['new_contact_info']}";
            //         $report->update(['inspector_phone' => $missingData['new_contact_info']]);
            //     }
            // }

            // Step 1: Create the history record with the potentially modified comment.
            $history = $report->statusHistories()->create([
                'user_id' => $options['user_id'] ?? Auth::id(),
                'status_id' => $statusId,
                'sub_status_id' => $subStatusId,
                'comment' => trim($comment),
            ]);

            // Step 2: Update the report's main record.
            $report->update([
                'status_id' => $history->status_id,
                'sub_status_id' => $history->sub_status_id,
                'current_status_history_id' => $history->id,
            ]);

            // if (isset($options['inspection_data'])) {
            //     $report->onSiteInspection()->updateOrCreate(
            //         ['report_id' => $report->id],
            //         $options['inspection_data']
            //     );
            // }

            // Step 3: Update the report's attachments.
            if (!empty($options['attachments'])) {
                /** @var FileService $fs */
                $fs = resolve('App\Services\FileService');
                $attachmentBatch = [];
                foreach ($options['attachments'] as $attachment) {
                    $file = $fs->createFile($attachment, 'report/' . $report->id . '/status_attachments/', Auth::id());
                    $attachmentBatch[] = ['file_id' => $file->id, 'category' => 'other'];
                }
                $report->attachments()->createMany($attachmentBatch);
            }
        });

        // Refresh the report model to ensure all relations are up-to-date.
        $report->refresh();

        // Step 4: Handle Notifications.
        // Check if the new status is a "Closed" status.
        // if ($report->status->name === ReportStatus::CLOSED) {
        //     $this->mailService->sendReportClosedMails($report);
        // } else {
        //     $this->mailService->sendReportStatusChangedMails($report);
        // }

        return $report;
    }
}
