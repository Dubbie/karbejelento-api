<?php

namespace App\Http\Requests\User;

use App\Constants\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $user = $this->route('user'); // Get user from the route

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['sometimes', Rule::in([UserRole::ADMIN, UserRole::MANAGER, UserRole::CUSTOMER, UserRole::DAMAGE_SOLVER])],
            'manager_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
