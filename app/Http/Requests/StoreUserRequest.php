<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'position' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'is_active' => ['sometimes', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }
}