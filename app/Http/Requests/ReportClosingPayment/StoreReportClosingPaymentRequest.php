<?php

namespace App\Http\Requests\ReportClosingPayment;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportClosingPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'payment_date' => ['required', 'date'],
            'payment_time' => ['nullable', 'date_format:H:i'],
        ];
    }
}
