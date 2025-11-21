<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDamageIdRequest extends FormRequest
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
        $report = $this->route('report');

        return [
            'damage_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('reports', 'damage_id')->ignore($report),
            ],
        ];
    }
}
