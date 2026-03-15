<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['sometimes', 'required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'status_id' => ['sometimes', 'required', 'exists:task_statuses,id'],
            'due_date' => ['nullable', 'date'],
            'parent_task_id' => ['nullable', 'exists:tasks,id'],
            'performers' => ['nullable', 'array'],
            'performers.*.user_id' => ['required', 'exists:users,id'],
            'performers.*.role' => ['nullable', 'string', 'max:255'],
        ];
    }
}