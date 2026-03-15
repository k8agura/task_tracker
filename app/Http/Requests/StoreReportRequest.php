<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'status_id' => ['nullable', 'exists:task_statuses,id'],
            'creator_id' => ['nullable', 'exists:users,id'],
        ];
    }
}