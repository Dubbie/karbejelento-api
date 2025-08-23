<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Building\StoreBuildingRequest;
use App\Http\Requests\Building\UpdateBuildingRequest;
use App\Models\Building;
use App\Services\BuildingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class BuildingController extends Controller
{
    public function __construct(protected BuildingService $buildingService) {}

    public function index(Request $request): array
    {
        $user = $request->user();
        return $this->buildingService->getAllBuildings($user, $request);
    }

    public function store(StoreBuildingRequest $request)
    {
        $building = $this->buildingService->createBuilding($request->validated());
        return response()->json($building, Response::HTTP_CREATED);
    }

    public function show(Building $building): Building
    {
        $building->load('managementHistory.customer');
        return $building;
    }

    public function update(UpdateBuildingRequest $request, Building $building): JsonResponse
    {
        $this->buildingService->updateBuilding($building, $request->validated());
        return response()->json($building->fresh());
    }

    public function destroy(Building $building): Response
    {
        $this->buildingService->deleteBuilding($building);
        return response()->noContent();
    }

    public function notifiers(Building $building): Collection
    {
        return $this->buildingService->getNotifiersForBuilding($building);
    }
}
