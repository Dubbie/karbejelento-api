<?php

namespace App\Services;

use App\Constants\ReportStatus;
use App\Models\Building;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\ReportStatusHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReportService
{
    public function createReport(array $data, User $user): Report
    {
        $building = Building::findOrFail($data['building_id']);

        return DB::transaction(function () use ($data, $user, $building) {
            $report = Report::create(array_merge($data, [
                'uuid' => Str::uuid(),
                'created_by_user_id' => $user->id,
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
    public function addAttachments(Report $report, array $files, array $categories, User $user): \Illuminate\Database\Eloquent\Collection
    {
        $attachments = collect($files)->map(function (UploadedFile $file, $index) use ($report, $categories, $user) {
            // Store the file and get its path. 'attachments' is the disk name.
            $path = $file->store('attachments', 'public');

            return ReportAttachment::create([
                'uuid' => Str::uuid(),
                'report_id' => $report->id,
                'uploaded_by_user_id' => $user->id,
                'file_path' => $path,
                'file_name_original' => $file->getClientOriginalName(),
                'file_mime_type' => $file->getMimeType(),
                'file_size_bytes' => $file->getSize(),
                'category' => $categories[$index] ?? 'other',
            ]);
        });

        return $attachments;
    }
}
