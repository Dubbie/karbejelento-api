<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\ChangeStatusRequest;
use App\Http\Requests\Report\StoreReportRequest;
use App\Http\Requests\Report\UpdateReportRequest;
use App\Models\Report;
use App\Models\Status;
use App\Models\SubStatus;
use App\Services\ReportService;
use App\Services\ReportStatusTransitionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
        protected ReportStatusTransitionService $transitionService,
    ) {}

    public function index(Request $request)
    {
        return $this->reportService->getAllReportsForUser($request->user(), $request);
    }

    public function store(StoreReportRequest $request)
    {
        $report = $this->reportService->createReport($request->validated(), $request->user());
        return response()->json($report, Response::HTTP_CREATED);
    }

    public function show(Report $report)
    {
        // Eager load all necessary relationships for the detail view
        return $report->load([
            'building.managementHistory.customer.manager',
            'createdBy',
            'notifier',
            'attachments',
            'status',
            'subStatus',
            'statusHistories' => [
                'user:id,uuid,name',
                'status:id,uuid,name',
                'subStatus:id,uuid,name',
            ],
            'currentStatusHistory' => [
                'user:id,uuid,name',
                'status:id,uuid,name',
                'subStatus:id,uuid,name',
            ],
        ]);
    }

    public function update(UpdateReportRequest $request, Report $report)
    {
        $updatedReport = $this->reportService->updateReport($report, $request->validated());
        return response()->json($updatedReport);
    }

    public function changeStatus(ChangeStatusRequest $request, Report $report)
    {
        $status = Status::findOrFail($request->input('status_id'));
        $subStatusId = $request->input('sub_status_id');
        $subStatus = $subStatusId ? SubStatus::findOrFail($subStatusId) : null;

        $updatedReport = $this->transitionService->transition(
            $report,
            $status,
            $subStatus,
            $request->user(),
            $request->validated()
        );

        return response()->json($updatedReport);
    }

    public function uploadAttachments(Request $request, Report $report)
    {
        $request->validate([
            'attachments' => ['required', 'array'],
            'attachments.*' => ['required', 'file', 'max:10240'], // 10MB max per file
            'categories' => ['required', 'array'],
            'categories.*' => ['required', 'string'],
        ]);

        if (count($request->file('attachments')) !== count($request->input('categories'))) {
            return response()->json(['message' => 'Mismatch between files and categories.'], 422);
        }

        $attachments = $this->reportService->addAttachments(
            $report,
            $request->file('attachments'),
            $request->input('categories'),
            $request->user()
        );

        return response()->json($attachments, Response::HTTP_CREATED);
    }
}
