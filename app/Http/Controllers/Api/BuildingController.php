<?php

namespace App\Http\Controllers\Api;

use App\Exports\BuildingTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Building\ImportBuildingsRequest;
use App\Http\Requests\Building\StoreBuildingRequest;
use App\Http\Requests\Building\UpdateBuildingRequest;
use App\Models\Building;
use App\Services\BuildingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    /**
     * Generate and return a template file for importing buildings.
     */
    public function generateImportTemplate(): BinaryFileResponse
    {
        return Excel::download(new BuildingTemplateExport($this->buildingService), 'building-import-template.xlsx');
    }

    /**
     * Accept a spreadsheet file to import new buildings for a customer.
     */
    public function import(ImportBuildingsRequest $request): JsonResponse
    {
        $importJob = $this->buildingService->processImport(
            $request->file('file'),
            $request->input('customer_id'),
            $request->user()
        );

        return response()->json($importJob, 201);
    }
}
