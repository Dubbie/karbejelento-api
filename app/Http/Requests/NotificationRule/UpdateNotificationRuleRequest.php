<?php

namespace App\Http\Requests\NotificationRule;

use App\Constants\NotificationEvent;
use App\Constants\NotificationRecipientType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'event' => ['sometimes', 'string', Rule::in(NotificationEvent::all())],
            'status_uuid' => ['nullable', 'string', Rule::exists('statuses', 'uuid')],
            'sub_status_uuid' => ['nullable', 'string', Rule::exists('sub_statuses', 'uuid')],
            'is_active' => ['sometimes', 'boolean'],
            'recipients' => ['sometimes', 'array', 'min:1'],
            'recipients.*.type' => ['required_with:recipients', 'string', Rule::in(NotificationRecipientType::all())],
            'recipients.*.value' => ['nullable', 'string', 'max:255'],
        ];
    }
}
