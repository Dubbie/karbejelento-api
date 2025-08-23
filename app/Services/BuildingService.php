<?php

namespace App\Services;

use App\Models\Building;
use App\Models\BuildingManagement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
}
