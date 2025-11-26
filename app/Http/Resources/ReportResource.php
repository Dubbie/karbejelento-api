<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'damage_id' => $this->damage_id,
            'damage_type' => $this->damage_type,
            'damage_description' => $this->damage_description,
            'damage_details' => [
                'building_name' => $this->damaged_building_name,
                'building_number' => $this->damaged_building_number,
                'floor' => $this->damaged_floor,
                'unit_or_door' => $this->damaged_unit_or_door,
                'date' => $this->damage_date?->toDateString(),
                'estimated_cost' => $this->estimated_cost,
            ],
            'claimant' => [
                'type' => $this->claimant_type,
                'name' => $this->claimant_name,
                'email' => $this->claimant_email,
                'phone_number' => $this->claimant_phone_number,
                'account_number' => $this->claimant_account_number,
            ],
            'contact' => [
                'name' => $this->contact_name,
                'phone_number' => $this->contact_phone_number,
            ],
            'status' => $this->whenLoaded('status', fn () => StatusResource::make($this->status)),
            'sub_status' => $this->whenLoaded('subStatus', fn () => SubStatusResource::make($this->subStatus)),
            'building' => $this->whenLoaded('building', fn () => BuildingResource::make($this->building)),
            'notifier' => $this->whenLoaded('notifier', fn () => NotifierResource::make($this->notifier)),
            'insurer' => $this->whenLoaded('insurer', fn () => InsurerResource::make($this->insurer)),
            'created_by' => $this->whenLoaded('createdBy', fn () => UserResource::make($this->createdBy)),
            'attachments' => $this->whenLoaded('attachments', function () {
                return ReportAttachmentResource::collection($this->attachments);
            }),
            'status_histories' => $this->whenLoaded('statusHistories', function () {
                return ReportStatusHistoryResource::collection($this->statusHistories);
            }),
            'current_status_history' => $this->whenLoaded('currentStatusHistory', function () {
                return ReportStatusHistoryResource::make($this->currentStatusHistory);
            }),
            'document_requests' => $this->whenLoaded('documentRequests', function () {
                return DocumentRequestResource::collection($this->documentRequests);
            }),
            'closing_payments' => $this->whenLoaded('closingPayments', function () {
                return ReportClosingPaymentResource::collection($this->closingPayments);
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
