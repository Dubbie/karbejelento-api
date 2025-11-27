<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\ChangeStatusRequest;
use App\Http\Requests\Report\StoreReportRequest;
use App\Http\Requests\Report\UpdateDamageIdRequest;
use App\Http\Requests\Report\UpdateReportRequest;
use App\Models\Report;
use App\Models\Status;
use App\Models\SubStatus;
use App\Http\Resources\ReportAttachmentResource;
use App\Http\Resources\ReportResource;
use App\Services\ReportService;
use App\Services\ReportStatusTransitionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    /**
     * Constructor
     *
     * Instantiate the controller with its collaborating services.
     */
    public function __construct(
        protected ReportService $reportService,
        protected ReportStatusTransitionService $transitionService,
    ) {}

    /**
     * Eager load relationships used when serializing reports.
     */
    protected array $fullReportRelations = [
        'building.insurer',
        'building.managementHistory.customer.manager',
        'building.managementHistory.insurer',
        'createdBy',
        'notifier',
        'attachments.uploadedBy',
        'status',
        'subStatus',
        'statusHistories.user',
        'statusHistories.status',
        'statusHistories.subStatus',
        'currentStatusHistory.user',
        'currentStatusHistory.status',
        'currentStatusHistory.subStatus',
        'documentRequests.items.files',
        'closingPayments.createdBy',
        'insurer',
        'duplicateOfReport',
    ];

    protected function loadFullReport(Report $report): Report
    {
        return $report->load($this->fullReportRelations);
    }

    /**
     * List Reports
     *
     * Return a paginated list of reports visible to the current user.
     */
    public function index(Request $request)
    {
        $reports = $this->reportService->getAllReportsForUser($request->user(), $request);

        return $this->paginatedResponse($reports, ReportResource::class);
    }

    /**
     * Create Report
     *
     * Store a newly created report and its initial status history.
     */
    public function store(StoreReportRequest $request)
    {
        $report = $this->reportService->createReport($request->validated(), $request->user());
        $report = $this->loadFullReport($report);

        return ReportResource::make($report)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Show Report
     *
     * Display a single report with detailed related data.
     */
    public function show(Report $report)
    {
        return ReportResource::make($this->loadFullReport($report));
    }

    /**
     * Update Report
     *
     * Update mutable report attributes (excluding status).
     */
    public function update(UpdateReportRequest $request, Report $report)
    {
        $updatedReport = $this->reportService->updateReport($report, $request->validated());
        return ReportResource::make($this->loadFullReport($updatedReport));
    }

    /**
     * Change Status
     *
     * Transition a report to a new status based on workflow rules.
     */
    public function changeStatus(ChangeStatusRequest $request, Report $report)
    {
        $statusName = $request->input('status');
        $status = Status::where('name', $statusName)->firstOrFail();

        $subStatusName = $request->input('sub_status');
        $subStatus = null;
        if ($subStatusName) {
            $subStatus = $status->subStatuses()->where('name', $subStatusName)->first();

            if (!$subStatus) {
                throw ValidationException::withMessages([
                    'sub_status' => ['The provided sub-status does not belong to the selected status.'],
                ]);
            }
        }

        $payload = $request->transitionPayload();
        $updatedReport = $this->transitionService->transition(
            $report,
            $status,
            $subStatus,
            $request->user(),
            $payload
        );

        return ReportResource::make($this->loadFullReport($updatedReport));
    }

    /**
     * Upload Attachments
     *
     * Attach uploaded files to a report.
     */
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

        $attachments->load('uploadedBy');

        return ReportAttachmentResource::collection($attachments)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update Damage ID
     *
     * Update the insurer-provided damage identifier without changing status.
     */
    public function updateDamageId(UpdateDamageIdRequest $request, Report $report)
    {
        $validated = $request->validated();

        $updatedReport = $this->reportService->updateDamageId(
            $report,
            $validated['damage_id'],
            $request->user(),
            $validated['comment'] ?? null,
        );

        return ReportResource::make($this->loadFullReport($updatedReport));
    }
}
