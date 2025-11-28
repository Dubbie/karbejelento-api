<?php

namespace App\Http\Controllers\Api;

use App\Exports\BuildingTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Building\ImportBuildingsRequest;
use App\Http\Requests\Building\StoreBuildingRequest;
use App\Http\Requests\Building\UpdateBuildingRequest;
use App\Models\Building;
use App\Http\Resources\BuildingImportResource;
use App\Http\Resources\BuildingResource;
use App\Http\Resources\NotifierResource;
use App\Http\Resources\ReportResource;
use App\Services\BuildingService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BuildingController extends Controller
{
    /**
     * Constructor
     *
     * Create a new controller instance with required services.
     */
    public function __construct(protected BuildingService $buildingService, protected ReportService $reportService) {}

    /**
     * List Buildings
     *
     * Retrieve paginated buildings visible to the authenticated user.
     *
     * @response array{
     *     data: array<\App\Http\Resources\BuildingResource>,
     *     meta: array{
     *         totalItems: int,
     *         itemCount: int,
     *         itemsPerPage: int,
     *         totalPages: int,
     *         currentPage: int
     *     }
     * }
     */
    public function index(Request $request): array
    {
        $user = $request->user();
        $buildings = $this->buildingService->getAllBuildings($user, $request);

        return $this->paginatedResponse($buildings, BuildingResource::class);
    }

    /**
     * Create Building
     *
     * Persist a new building record.
     */
    public function store(StoreBuildingRequest $request)
    {
        $building = $this->buildingService->createBuilding($request->validated());
        $building->load('insurer', 'managementHistory.customer', 'managementHistory.insurer');

        /**
         * @status 201
         * @body \App\Http\Resources\BuildingResource
         */
        return BuildingResource::make($building)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Show Building
     *
     * Display a single building with related management information.
     *
     * @response \App\Http\Resources\BuildingResource
     */
    public function show(Building $building)
    {
        return BuildingResource::make($building->load('insurer', 'managementHistory.customer', 'managementHistory.insurer'));
    }

    /**
     * Update Building
     *
     * Modify the provided building with validated data.
     *
     * @response \App\Http\Resources\BuildingResource
     */
    public function update(UpdateBuildingRequest $request, Building $building)
    {
        $this->buildingService->updateBuilding($building, $request->validated());
        return BuildingResource::make($building->fresh()->load('insurer', 'managementHistory.customer', 'managementHistory.insurer'));
    }

    /**
     * Delete Building
     *
     * Remove the specified building permanently.
     */
    public function destroy(Building $building): Response
    {
        $this->buildingService->deleteBuilding($building);
        return response()->noContent();
    }

    /**
     * List Notifiers
     *
     * Retrieve notifiers available for the given building.
     *
     * @response array<\App\Http\Resources\NotifierResource>
     */
    public function notifiers(Building $building)
    {
        $notifiers = $this->buildingService->getNotifiersForBuilding($building);
        return NotifierResource::collection($notifiers);
    }

    /**
     * Download Import Template
     *
     * Generate and return a template file for importing buildings.
     *
     * @response \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function generateImportTemplate(): BinaryFileResponse
    {
        return Excel::download(new BuildingTemplateExport($this->buildingService), 'building-import-template.xlsx');
    }

    /**
     * Import Buildings
     *
     * Accept a spreadsheet file to import new buildings for a customer.
     *
     * @response \App\Http\Resources\BuildingImportResource
     */
    public function import(ImportBuildingsRequest $request): JsonResponse
    {
        $importJob = $this->buildingService->processImport(
            $request->file('file'),
            $request->input('customer_id'),
            $request->user()
        );

        $importJob->load(['uploader', 'customer']);

        return BuildingImportResource::make($importJob)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Building Reports
     *
     * Fetch reports that belong to a specific building.
     *
     * @response array{
     *     data: array<\App\Http\Resources\ReportResource>,
     *     meta: array{
     *         totalItems: int,
     *         itemCount: int,
     *         itemsPerPage: int,
     *         totalPages: int,
     *         currentPage: int
     *     }
     * }
     */
    public function reports(Request $request, Building $building)
    {
        $reports = $this->reportService->getAllReportsForBuilding($building, $request);

        return $this->paginatedResponse($reports, ReportResource::class);
    }
}
