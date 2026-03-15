<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240'],
            'task_id' => ['nullable', 'exists:tasks,id'],
            'message_id' => ['nullable', 'exists:messages,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $taskId = $this->input('task_id');
            $messageId = $this->input('message_id');

            if (!$taskId && !$messageId) {
                $validator->errors()->add('task_id', 'Нужно передать task_id или message_id.');
            }

            if ($taskId && $messageId) {
                $validator->errors()->add('message_id', 'Нельзя одновременно передавать task_id и message_id.');
            }
        });
    }
}