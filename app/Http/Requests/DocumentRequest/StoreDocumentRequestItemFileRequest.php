<?php

namespace App\Http\Requests\DocumentRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreDocumentRequestItemFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'file' => ['nullable', 'file', 'max:10240'],
            'files' => ['nullable', 'array', 'min:1'],
            'files.*' => ['file', 'max:10240'],
        ];
    }

    protected function after(): array
    {
        return [
            function (Validator $validator) {
                $hasSingle = $this->hasFile('file');
                $hasMultiple = $this->hasFile('files');

                if (!$hasSingle && !$hasMultiple) {
                    $validator->errors()->add('file', 'At least one file must be provided.');
                }
            },
        ];
    }
}
