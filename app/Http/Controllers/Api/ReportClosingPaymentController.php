<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportClosingPayment\StoreReportClosingPaymentRequest;
use App\Http\Requests\ReportClosingPayment\UpdateReportClosingPaymentRequest;
use App\Http\Resources\ReportClosingPaymentResource;
use App\Models\Report;
use App\Models\ReportClosingPayment;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ReportClosingPaymentController extends Controller
{
    public function index(Report $report)
    {
        $payments = $report->closingPayments()->with('createdBy')->get();

        return ReportClosingPaymentResource::collection($payments);
    }

    public function store(StoreReportClosingPaymentRequest $request, Report $report)
    {
        $payload = $this->preparePayload($request->validated());

        $payment = $report->closingPayments()->create(array_merge($payload, [
            'uuid' => (string) Str::uuid(),
            'created_by_user_id' => $request->user()->id,
        ]));

        $payment->load('createdBy');

        return ReportClosingPaymentResource::make($payment)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateReportClosingPaymentRequest $request,
        Report $report,
        ReportClosingPayment $closingPayment
    ) {
        $this->assertPaymentBelongsToReport($closingPayment, $report);

        $payload = $this->preparePayload($request->validated());

        $closingPayment->update($payload);

        $closingPayment->load('createdBy');

        return ReportClosingPaymentResource::make($closingPayment);
    }

    public function destroy(Report $report, ReportClosingPayment $closingPayment)
    {
        $this->assertPaymentBelongsToReport($closingPayment, $report);

        $closingPayment->delete();

        return response()->noContent();
    }

    private function assertPaymentBelongsToReport(ReportClosingPayment $payment, Report $report): void
    {
        if ($payment->report_id !== $report->id) {
            abort(Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function preparePayload(array $data): array
    {
        if (isset($data['currency'])) {
            $data['currency'] = strtoupper($data['currency']);
        }

        return $data;
    }
}
