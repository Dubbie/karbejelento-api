<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationRuleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'event' => $this->event,
            'is_active' => $this->is_active,
            'status' => $this->whenLoaded('status', function () {
                return $this->status ? [
                    'uuid' => $this->status->uuid,
                    'name' => $this->status->name,
                ] : null;
            }),
            'sub_status' => $this->whenLoaded('subStatus', function () {
                return $this->subStatus ? [
                    'uuid' => $this->subStatus->uuid,
                    'name' => $this->subStatus->name,
                ] : null;
            }),
            'recipients' => $this->whenLoaded('recipients', function () {
                return $this->recipients->map(fn ($recipient) => [
                    'type' => $recipient->recipient_type,
                    'value' => $recipient->recipient_value,
                ])->values();
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
