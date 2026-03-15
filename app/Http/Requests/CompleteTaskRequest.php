<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status_code' => ['required', Rule::in(['done', 'cancelled'])],
            'completion_report' => ['required', 'string', 'min:10'],
        ];
    }
}