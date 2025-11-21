<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\StoreReportRequest;
use App\Http\Requests\Report\UpdateReportRequest;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

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
                'user:id,name',
                'status:id,name',
                'subStatus:id,name',
            ],
            'currentStatusHistory' => [
                'user:id,name',
            ],
        ]);
    }

    public function update(UpdateReportRequest $request, Report $report)
    {
        $updatedReport = $this->reportService->updateReport($report, $request->validated(), $request->user());
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
