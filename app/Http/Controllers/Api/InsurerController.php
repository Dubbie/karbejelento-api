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
     */
    public function index(Request $request)
    {
        $insurers = $this->insurerService->getAllInsurers($request);

        return $this->paginatedResponse($insurers, InsurerResource::class);
    }

    /**
     * Create insurer.
     */
    public function store(StoreInsurerRequest $request)
    {
        $insurer = $this->insurerService->createInsurer($request->validated());

        return InsurerResource::make($insurer)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Show insurer.
     */
    public function show(Insurer $insurer)
    {
        return InsurerResource::make($insurer);
    }

    /**
     * Update insurer.
     */
    public function update(UpdateInsurerRequest $request, Insurer $insurer)
    {
        $this->insurerService->updateInsurer($insurer, $request->validated());

        return InsurerResource::make($insurer->fresh());
    }

    /**
     * Delete insurer.
     */
    public function destroy(Insurer $insurer): Response
    {
        $this->insurerService->deleteInsurer($insurer);

        return response()->noContent();
    }
}
