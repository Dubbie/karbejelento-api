<?php

namespace App\Http\Requests\Building;

use App\Constants\StreetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBuildingRequest extends FormRequest
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
        $building = $this->route('building');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'postcode' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:255'],
            'street_name' => ['sometimes', 'string', 'max:255'],
            'street_number' => ['sometimes', 'string', 'max:255'],
            'street_type' => ['nullable', 'string', Rule::in(array_values((new \ReflectionClass(StreetType::class))->getConstants()))],
            'bond_number' => ['sometimes', 'string', Rule::unique('buildings', 'bond_number')->ignore($building)],
            'account_number' => ['sometimes', 'string'],
            'insurer_uuid' => ['sometimes', 'uuid', 'exists:insurers,uuid'],
            'customer_id' => ['sometimes', 'integer', 'exists:users,id'],
        ];
    }
}
