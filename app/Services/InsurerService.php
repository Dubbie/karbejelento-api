<?php

namespace App\Services;

use App\Models\Insurer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InsurerService
{
    /**
     * Retrieve paginated insurers with filtering support.
     */
    public function getAllInsurers(Request $request): array
    {
        return Insurer::query()->advancedPaginate($request, [
            'sortableFields' => ['name', 'created_at'],
            'filterableFields' => ['name'],
        ]);
    }

    /**
     * Persist a new insurer.
     */
    public function createInsurer(array $data): Insurer
    {
        return Insurer::create([
            'uuid' => Str::uuid(),
            'name' => $data['name'],
        ]);
    }

    /**
     * Update an insurer.
     */
    public function updateInsurer(Insurer $insurer, array $data): bool
    {
        return $insurer->update($data);
    }

    /**
     * Delete an insurer.
     */
    public function deleteInsurer(Insurer $insurer): bool
    {
        return $insurer->delete();
    }
}
