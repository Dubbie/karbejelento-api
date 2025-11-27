<?php

namespace App\Http\Requests\ReportClosingPayment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReportClosingPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient' => ['sometimes', 'required', 'string', 'max:255'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'payment_date' => ['sometimes', 'required', 'date'],
            'payment_time' => ['sometimes', 'nullable', 'date_format:H:i'],
        ];
    }
}
