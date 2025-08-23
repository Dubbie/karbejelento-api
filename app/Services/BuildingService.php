<?php

namespace App\Services;

use App\Constants\BuildingImportStatus;
use App\Exports\BuildingTemplateExport;
use App\Imports\BuildingsImport;
use App\Models\Building;
use App\Models\BuildingImport;
use App\Models\BuildingManagement;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BuildingService
{
    /**
     * Creates a building and its initial management record in a transaction.
     */
    public function createBuilding(array $data): Building
    {
        $customerId = $data['customer_id'];
        unset($data['customer_id']); // Remove it from building data

        return DB::transaction(function () use ($data, $customerId) {
            // 1. Create the building
            $building = Building::create(array_merge($data, ['uuid' => Str::uuid()]));

            // 2. Create the initial management record
            BuildingManagement::create([
                'building_id' => $building->id,
                'customer_id' => $customerId,
                'start_date' => now(),
            ]);

            return $building;
        });
    }

    /**
     * Finds all buildings based on the user's role and query parameters.
     */
    public function getAllBuildings(User $user, Request $request): array
    {
        $query = Building::forUser($user);

        return $query->advancedPaginate($request, [
            'sortableFields' => ['name', 'city', 'postcode'],
            'filterableFields' => ['name', 'city', 'postcode', 'insurer'],
        ]);
    }

    /**
     * Updates a building.
     */
    public function updateBuilding(Building $building, array $data): bool
    {
        return $building->update($data);
    }

    /**
     * Deletes a building.
     */
    public function deleteBuilding(Building $building): bool
    {
        return $building->delete();
    }

    /**
     * Finds all notifiers for a building's current customer.
     */
    public function getNotifiersForBuilding(Building $building)
    {
        return $building->current_customer?->notifiers ?? collect();
    }

    /**
     * Handles the logic for importing buildings from a spreadsheet.
     * This implementation is synchronous but designed for easy conversion to a queued job.
     */
    public function processImport(UploadedFile $file, string $customerId, User $uploader): BuildingImport
    {
        $path = $file->store('imports');
        $customer = User::find($customerId);

        $importRecord = BuildingImport::create([
            'uuid' => Str::uuid(),
            'user_id' => $uploader->id,
            'customer_id' => $customer->id,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'status' => BuildingImportStatus::PROCESSING,
        ]);

        // For a truly production-ready system at scale, this entire
        try {
            $importer = new BuildingsImport($importRecord, $this);
            Excel::import($importer, $path);

            // Refresh the model to get changes made by the job.
            $importRecord->refresh();

            // The errors are already saved. We just update the final status.
            $importRecord->update([
                'status' => BuildingImportStatus::COMPLETED,
            ]);
        } catch (Exception $e) {
            $importRecord->update([
                'status' => BuildingImportStatus::FAILED,
                'errors' => ['system' => ['The import process failed.', $e->getMessage()]],
            ]);
            Log::error('Building import process failed catastrophically.', ['exception' => $e]);
        }

        return $importRecord;
    }
}
