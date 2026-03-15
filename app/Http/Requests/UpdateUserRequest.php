<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('user');

        return [
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'position' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'is_active' => ['sometimes', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }
}