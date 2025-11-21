<?php

namespace App\Http\Requests\Report;

use App\Constants\ClaimantType;
use App\Constants\DamageType;
use App\Constants\EstimatedCost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $damageTypes = array_values((new \ReflectionClass(DamageType::class))->getConstants());
        $estimatedCosts = array_values((new \ReflectionClass(EstimatedCost::class))->getConstants());
        $claimantTypes = array_values((new \ReflectionClass(ClaimantType::class))->getConstants());
        $statusId = $this->input('status_id');
        $subStatusRule = Rule::exists('sub_statuses', 'id');

        if ($statusId) {
            $subStatusRule->where('status_id', $statusId);
        }

        // Get the Report model that has been resolved by Route Model Binding
        $report = $this->route('report');

        return [
            'damage_id' => ['sometimes', 'nullable', 'string', Rule::unique('reports')->ignore($report)],
            'building_uuid' => ['sometimes', 'string', 'exists:buildings,uuid'],
            'notifier_uuid' => ['sometimes', 'string', 'exists:notifiers,uuid'],
            'damage_type' => ['sometimes', Rule::in($damageTypes)],
            'estimated_cost' => ['sometimes', Rule::in($estimatedCosts)],
            'damage_description' => ['sometimes', 'string'],
            'damage_date' => ['sometimes', 'date'],
            'claimant_type' => ['sometimes', Rule::in($claimantTypes)],
            'claimant_name' => ['nullable', 'string', 'max:255'],
            'claimant_email' => ['nullable', 'email', 'max:255'],
            'claimant_phone_number' => ['nullable', 'string', 'max:255'],
            'claimant_account_number' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['sometimes', 'string', 'max:255'],
            'contact_phone_number' => ['sometimes', 'string', 'max:255'],
            'damaged_building_name' => ['nullable', 'string', 'max:255'],
            'damaged_building_number' => ['sometimes', 'string', 'max:255'],
            'damaged_floor' => ['sometimes', 'string', 'max:255'],
            'damaged_unit_or_door' => ['nullable', 'string', 'max:255'],
            'status_id' => ['sometimes', 'integer', 'exists:statuses,id', 'required_with:sub_status_id'],
            'sub_status_id' => ['sometimes', 'nullable', 'integer', $subStatusRule],
        ];
    }
}
