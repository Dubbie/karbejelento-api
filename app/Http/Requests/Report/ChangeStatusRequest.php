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

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::exists('statuses', 'name')],
            'sub_status' => ['nullable', 'string', Rule::exists('sub_statuses', 'name')],
            'comment' => ['nullable', 'string', 'max:2000'],
            'payload' => ['nullable', 'array'],
        ];
    }

    /**
     * Merge the optional payload bag with top-level fields like comment.
     *
     * @return array<string, mixed>
     */
    public function transitionPayload(): array
    {
        $payload = $this->input('payload', []);

        if (!is_array($payload)) {
            $payload = [];
        }

        if ($this->filled('comment')) {
            $payload['comment'] = $this->input('comment');
        }

        return $payload;
    }
}
