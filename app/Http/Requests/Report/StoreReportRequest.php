<?php

namespace App\Http\Requests\Report;

use App\Constants\ClaimantType;
use App\Constants\DamageType;
use App\Constants\EstimatedCost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
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
        // Using array_values and ReflectionClass makes the rule dynamic.
        $damageTypes = array_values((new \ReflectionClass(DamageType::class))->getConstants());
        $estimatedCosts = array_values((new \ReflectionClass(EstimatedCost::class))->getConstants());
        $claimantTypes = array_values((new \ReflectionClass(ClaimantType::class))->getConstants());

        return [
            // Core relationships
            'building_uuid' => ['required', 'string', 'exists:buildings,uuid'],
            'notifier_uuid' => ['required', 'string', 'exists:notifiers,uuid'],

            // Damage details
            'damage_type' => ['required', Rule::in($damageTypes)],
            'estimated_cost' => ['required', Rule::in($estimatedCosts)],
            'damage_description' => ['required', 'string'],
            'damage_date' => ['required', 'date'],

            // Claimant details
            'claimant_type' => ['required', Rule::in($claimantTypes)],
            'claimant_name' => ['nullable', 'string', 'max:255'],
            'claimant_email' => ['nullable', 'email', 'max:255'],
            'claimant_phone_number' => ['nullable', 'string', 'max:255'],
            'building_account_number' => ['nullable', 'string', 'max:255'],
            'claimant_account_number' => ['nullable', 'string', 'max:255'],

            // Contact details
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_phone_number' => ['required', 'string', 'max:255'],

            // Damage location
            'damaged_building_name' => ['nullable', 'string', 'max:255'],
            'damaged_building_number' => ['required', 'string', 'max:255'],
            'damaged_floor' => ['required', 'string', 'max:255'],
            'damaged_unit_or_door' => ['nullable', 'string', 'max:255'],
        ];
    }
}
