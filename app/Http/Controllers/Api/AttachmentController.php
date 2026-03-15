<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Attachment;
use App\Models\Message;
use App\Models\Task;
use App\Models\TaskHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AttachmentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            (new Middleware('permission:attachments.view'))->only(['indexTaskAttachments', 'indexMessageAttachments', 'download']),
            (new Middleware('permission:attachments.create'))->only(['store']),
            (new Middleware('permission:attachments.delete'))->only(['destroy']),
        ];
    }

    public function indexTaskAttachments(Task $task)
    {
        return response()->json(
            $task->attachments()->with('user')->latest()->get()
        );
    }

    public function indexMessageAttachments(Message $message)
    {
        return response()->json(
            $message->attachments()->with('user')->latest()->get()
        );
    }

    public function store(StoreAttachmentRequest $request)
    {
        $file = $request->file('file');
        $taskId = $request->input('task_id');
        $messageId = $request->input('message_id');

        $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('attachments', $storedName, 'public');

        $attachment = Attachment::create([
            'task_id' => $taskId,
            'message_id' => $messageId,
            'user_id' => $request->user()->id,
            'original_name' => $file->getClientOriginalName(),
            'file_name' => $storedName,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'path' => $path,
        ]);

        if ($taskId) {
            TaskHistory::create([
                'task_id' => $taskId,
                'user_id' => $request->user()->id,
                'action' => 'attachment_uploaded',
                'old_values' => null,
                'new_values' => $attachment->toArray(),
                'comment' => 'Файл прикреплён к задаче',
            ]);
        }

        if ($messageId) {
            $message = Message::find($messageId);

            if ($message) {
                $task = Task::whereHas('chat', function ($q) use ($message) {
                    $q->where('id', $message->chat_id);
                })->first();

                if ($task) {
                    TaskHistory::create([
                        'task_id' => $task->id,
                        'user_id' => $request->user()->id,
                        'action' => 'attachment_uploaded_to_message',
                        'old_values' => null,
                        'new_values' => $attachment->toArray(),
                        'comment' => 'Файл прикреплён к сообщению',
                    ]);
                }
            }
        }

        return response()->json(
            $attachment->load('user'),
            201
        );
    }

    public function download(Attachment $attachment)
    {
        if (!Storage::disk('public')->exists($attachment->path)) {
            return response()->json([
                'message' => 'Файл не найден',
            ], 404);
        }

        return Storage::disk('public')->download(
            $attachment->path,
            $attachment->original_name
        );
    }

    public function destroy(Request $request, Attachment $attachment)
    {
        $taskId = $attachment->task_id;

        if (!$taskId && $attachment->message_id) {
            $message = Message::find($attachment->message_id);

            if ($message) {
                $task = Task::whereHas('chat', function ($q) use ($message) {
                    $q->where('id', $message->chat_id);
                })->first();

                $taskId = $task?->id;
            }
        }

        if (Storage::disk('public')->exists($attachment->path)) {
            Storage::disk('public')->delete($attachment->path);
        }

        if ($taskId) {
            TaskHistory::create([
                'task_id' => $taskId,
                'user_id' => $request->user()->id,
                'action' => 'attachment_deleted',
                'old_values' => $attachment->toArray(),
                'new_values' => null,
                'comment' => 'Файл удалён',
            ]);
        }

        $attachment->delete();

        return response()->json([
            'message' => 'Файл удалён',
        ]);
    }
}