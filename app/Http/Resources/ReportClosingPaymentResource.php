<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ReportClosingPayment */
class ReportClosingPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'recipient' => $this->recipient,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_date' => $this->payment_date?->toDateString(),
            'payment_time' => $this->payment_time,
            'created_by' => $this->whenLoaded('createdBy', fn () => UserResource::make($this->createdBy)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
