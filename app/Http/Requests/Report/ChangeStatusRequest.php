<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $statusId = $this->input('status_id');
        $subStatusRule = Rule::exists('sub_statuses', 'id');

        if ($statusId) {
            $subStatusRule->where('status_id', $statusId);
        }

        return [
            'status_id' => ['required', 'integer', 'exists:statuses,id'],
            'sub_status_id' => ['nullable', 'integer', $subStatusRule],
            'comment' => ['nullable', 'string', 'max:2000'],
            'damage_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
