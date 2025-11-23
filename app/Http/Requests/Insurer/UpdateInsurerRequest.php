<?php

namespace App\Http\Requests\Insurer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInsurerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get validation rules.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $insurer = $this->route('insurer');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('insurers', 'name')->ignore($insurer?->id),
            ],
        ];
    }
}
