<?php

namespace App\Http\Requests\Insurer;

use Illuminate\Foundation\Http\FormRequest;

class StoreInsurerRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255', 'unique:insurers,name'],
        ];
    }
}
