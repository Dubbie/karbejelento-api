<?php

namespace App\Http\Requests\Building;

use App\Constants\StreetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBuildingRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'postcode' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'street_name' => ['required', 'string', 'max:255'],
            'street_number' => ['required', 'string', 'max:255'],
            'street_type' => ['nullable', 'string', Rule::in(array_values((new \ReflectionClass(StreetType::class))->getConstants()))],
            'bond_number' => ['required', 'string', 'unique:buildings,bond_number'],
            'account_number' => ['required', 'string'],
            'insurer' => ['required', 'string'],
            'customer_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
