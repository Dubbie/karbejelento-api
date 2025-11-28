<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Insurer\StoreInsurerRequest;
use App\Http\Requests\Insurer\UpdateInsurerRequest;
use App\Http\Resources\InsurerResource;
use App\Models\Insurer;
use App\Services\InsurerService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InsurerController extends Controller
{
    public function __construct(private readonly InsurerService $insurerService) {}

    /**
     * List insurers.
     *
     * Retrieve paginated insurers.
     *
     * @response array{
     *     data: array<\App\Http\Resources\InsurerResource>,
     *     meta: array{
     *         totalItems: int,
     *         itemCount: int,
     *         itemsPerPage: int,
     *         totalPages: int,
     *         currentPage: int
     *     }
     * }
     */
    public function index(Request $request)
    {
        $insurers = $this->insurerService->getAllInsurers($request);

        return $this->paginatedResponse($insurers, InsurerResource::class);
    }

    /**
     * Create insurer.
     *
     * Persist a new insurer record.
     */
    public function store(StoreInsurerRequest $request)
    {
        $insurer = $this->insurerService->createInsurer($request->validated());

        /**
         * @status 201
         * @body \App\Http\Resources\InsurerResource
         */
        return InsurerResource::make($insurer)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Show insurer.
     *
     * Display a single insurer.
     *
     * @response \App\Http\Resources\InsurerResource
     */
    public function show(Insurer $insurer)
    {
        return InsurerResource::make($insurer);
    }

    /**
     * Update insurer.
     *
     * Modify the provided insurer with validated data.
     *
     * @response \App\Http\Resources\InsurerResource
     */
    public function update(UpdateInsurerRequest $request, Insurer $insurer)
    {
        $this->insurerService->updateInsurer($insurer, $request->validated());

        return InsurerResource::make($insurer->fresh());
    }

    /**
     * Delete insurer.
     *
     * Remove the specified insurer permanently.
     */
    public function destroy(Insurer $insurer): Response
    {
        $this->insurerService->deleteInsurer($insurer);

        return response()->noContent();
    }
}
