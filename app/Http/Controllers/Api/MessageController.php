<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Message;
use App\Models\Task;
use App\Models\TaskHistory;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Events\ChatMessageSent;

class MessageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            (new Middleware('permission:messages.view'))->only(['index', 'show']),
            (new Middleware('permission:messages.create'))->only(['store']),
            (new Middleware('permission:messages.delete'))->only(['destroy']),
        ];
    }

    public function index(Task $task)
    {
        $task->loadMissing('chat');

        if (!$task->chat) {
            return response()->json([]);
        }

        $messages = Message::with(['user.department', 'attachments'])
            ->where('chat_id', $task->chat->id)
            ->orderBy('created_at')
            ->get();

        return response()->json($messages);
    }

    public function store(StoreMessageRequest $request, Task $task)
    {
        $task->loadMissing('chat');

        if (!$task->chat) {
            $task->chat()->create();
            $task->load('chat');
        }

        $message = Message::create([
            'chat_id' => $task->chat->id,
            'user_id' => $request->user()->id,
            'message' => $request->validated()['message'],
        ]);

        TaskHistory::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'action' => 'message_created',
            'old_values' => null,
            'new_values' => $message->toArray(),
            'comment' => 'Добавлено сообщение в чат задачи',
        ]);

        broadcast(new ChatMessageSent($message))->toOthers();

        return response()->json(
            $message->load(['user.department', 'attachments']),
            201
        );
    }

    public function show(Task $task, Message $message)
    {
        $task->loadMissing('chat');

        if (!$task->chat || $message->chat_id !== $task->chat->id) {
            return response()->json([
                'message' => 'Сообщение не относится к указанной задаче',
            ], 404);
        }

        return response()->json(
            $message->load(['user.department', 'attachments'])
        );
    }

    public function destroy(Request $request, Task $task, Message $message)
    {
        $task->loadMissing('chat');

        if (!$task->chat || $message->chat_id !== $task->chat->id) {
            return response()->json([
                'message' => 'Сообщение не относится к указанной задаче',
            ], 404);
        }

        TaskHistory::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'action' => 'message_deleted',
            'old_values' => $message->toArray(),
            'new_values' => null,
            'comment' => 'Сообщение удалено из чата задачи',
        ]);

        $message->delete();

        return response()->json([
            'message' => 'Сообщение удалено',
        ]);
    }
}