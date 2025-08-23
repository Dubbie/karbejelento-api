<?php

namespace App\Services;

use App\Constants\ReportStatus;
use App\Models\Building;
use App\Models\Notifier;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\ReportStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReportService
{
    public function createReport(array $data, User $user): Report
    {
        $building = Building::where('uuid', $data['building_uuid'])->first();
        $notifier = Notifier::where('uuid', $data['notifier_uuid'])->first();

        if (!$building) {
            throw new \InvalidArgumentException('Invalid building UUID.');
        }

        if (!$notifier) {
            throw new \InvalidArgumentException('Invalid notifier UUID.');
        }

        unset($data['building_uuid'], $data['notifier_uuid']);

        return DB::transaction(function () use ($data, $user, $building, $notifier) {
            $report = Report::create(array_merge($data, [
                'uuid' => Str::uuid(),
                'created_by_user_id' => $user->id,
                'building_id' => $building->id,
                'notifier_id' => $notifier->id,
                'bond_number' => $building->bond_number, // Snapshot data
                'insurer' => $building->insurer,
            ]));

            ReportStatusHistory::create([
                'report_id' => $report->id,
                'status' => ReportStatus::NEW,
                'user_id' => $user->id,
                'notes' => 'Report created.',
            ]);

            return $report;
        });
    }

    public function getAllReports(User $user, Request $request): array
    {
        $query = Report::forUser($user)
            ->with(['building', 'notifier']); // Eager load relationships

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
}
