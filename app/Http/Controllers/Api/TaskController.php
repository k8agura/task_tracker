<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Chat;
use App\Models\Task;
use App\Models\TaskHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Requests\CompleteTaskRequest;
use App\Models\TaskStatus;

class TaskController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            (new Middleware('permission:tasks.view'))->only(['index', 'show']),
            (new Middleware('permission:tasks.create'))->only(['store']),
            (new Middleware('permission:tasks.update'))->only(['update', 'complete']),
            (new Middleware('permission:tasks.delete'))->only(['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $query = Task::with([
            'status',
            'creator',
            'performers',
            'chat',
        ]);

        $user = $request->user();

        if (!$user->hasRole('admin') && $user->department_id) {
            $query->where(function ($q) use ($user) {
                $q->where('creator_id', $user->id)
                ->orWhereHas('performers', function ($subQ) use ($user) {
                    $subQ->where('users.id', $user->id);
                })
                ->orWhereHas('creator', function ($subQ) use ($user) {
                    $subQ->where('department_id', $user->department_id);
                });
            });
        }

        if ($request->filled('status_id')) {
            $query->where('status_id', $request->integer('status_id'));
        }

        if ($request->filled('creator_id')) {
            $query->where('creator_id', $request->integer('creator_id'));
        }

        if ($request->filled('performer_id')) {
            $performerId = $request->integer('performer_id');
            $query->whereHas('performers', function ($q) use ($performerId) {
                $q->where('users.id', $performerId);
            });
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('due_date_from')) {
            $query->whereDate('due_date', '>=', $request->input('due_date_from'));
        }

        if ($request->filled('due_date_to')) {
            $query->whereDate('due_date', '<=', $request->input('due_date_to'));
        }

        $sort = $request->input('sort', 'newest');

        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'due_asc':
                $query->orderByRaw('due_date IS NULL, due_date asc');
                break;
            case 'due_desc':
                $query->orderByRaw('due_date IS NULL, due_date desc');
                break;
            case 'title_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'title_desc':
                $query->orderBy('title', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        return response()->json(
            $query->paginate(15)
        );
    }

    public function store(StoreTaskRequest $request)
    {
        $data = $request->validated();
        $performers = $data['performers'] ?? [];
        unset($data['performers']);

        $task = DB::transaction(function () use ($data, $performers, $request) {
            $data['creator_id'] = $request->user()->id;

            $task = Task::create($data);

            if (!empty($performers)) {
                $syncData = [];
                foreach ($performers as $performer) {
                    $syncData[$performer['user_id']] = [
                        'role' => $performer['role'] ?? 'executor',
                    ];
                }
                $task->performers()->sync($syncData);
            }

            Chat::create([
                'task_id' => $task->id,
            ]);

            TaskHistory::create([
                'task_id' => $task->id,
                'user_id' => $request->user()->id,
                'action' => 'created',
                'old_values' => null,
                'new_values' => $task->fresh()->toArray(),
                'comment' => 'Задача создана',
            ]);

            return $task;
        });

        return response()->json(
            $task->load(['status', 'creator', 'performers', 'chat']),
            201
        );
    }

    public function show(Task $task)
    {
        $user = request()->user();
        if (
            !$user->hasRole('admin') &&
            $user->department_id &&
            $task->creator?->department_id !== $user->department_id &&
            $task->creator_id !== $user->id &&
            !$task->performers()->where('users.id', $user->id)->exists()
        ) {
            return response()->json([
                'message' => 'Доступ к задаче запрещён',
            ], 403);
        }
        return response()->json(
            $task->load([
                'status',
                'creator.department',
                'performers.department',
                'histories.user',
                'chat.messages.user',
                'attachments',
                'childTasks',
                'parentTask',
                'completedBy',
            ])
        );
    }
    
    public function complete(CompleteTaskRequest $request, Task $task)
    {
        $user = $request->user();

        $isAdmin = $user->hasRole('admin');
        $isCreator = $task->creator_id === $user->id;
        $isPerformer = $task->performers()->where('users.id', $user->id)->exists();

        if (!$isAdmin && !$isCreator && !$isPerformer) {
            return response()->json([
                'message' => 'Завершать задачу может только её исполнитель, создатель или администратор',
            ], 403);
        }

        if (in_array($task->status?->code, ['done', 'cancelled'])) {
            return response()->json([
                'message' => 'Эта задача уже завершена и не может быть завершена повторно',
            ], 422);
        }

        $status = TaskStatus::where('code', $request->input('status_code'))->first();

        if (!$status) {
            return response()->json([
                'message' => 'Итоговый статус не найден',
            ], 422);
        }

        $oldValues = $task->fresh()->load('performers')->toArray();

        $task->update([
            'status_id' => $status->id,
            'completion_report' => $request->input('completion_report'),
            'completed_at' => now(),
            'completed_by' => $user->id,
        ]);

        TaskHistory::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'action' => 'completed',
            'old_values' => $oldValues,
            'new_values' => $task->fresh()->load('performers')->toArray(),
            'comment' => 'Задача завершена. Итоговый статус: ' . $status->name,
        ]);

        return response()->json([
            'message' => 'Задача успешно завершена',
            'task' => $task->fresh()->load([
                'status',
                'creator.department',
                'performers.department',
                'histories.user',
                'chat.messages.user',
                'attachments',
                'childTasks',
                'parentTask',
                'completedBy',
            ]),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $user = $request->user();

        if (!$user->hasRole('admin') && $task->creator_id !== $user->id) {
            return response()->json([
                'message' => 'Редактировать задачу может только её создатель или администратор',
            ], 403);
        }
        $data = $request->validated();
        $performers = $data['performers'] ?? null;
        unset($data['performers']);

        $updatedTask = DB::transaction(function () use ($task, $data, $performers, $request) {
            $oldValues = $task->fresh()->load('performers')->toArray();

            $task->update($data);

            if (is_array($performers)) {
                $syncData = [];
                foreach ($performers as $performer) {
                    $syncData[$performer['user_id']] = [
                        'role' => $performer['role'] ?? 'executor',
                    ];
                }
                $task->performers()->sync($syncData);
            }

            TaskHistory::create([
                'task_id' => $task->id,
                'user_id' => $request->user()->id,
                'action' => 'updated',
                'old_values' => $oldValues,
                'new_values' => $task->fresh()->load('performers')->toArray(),
                'comment' => 'Задача обновлена',
            ]);

            return $task;
        });

        return response()->json(
            $updatedTask->load(['status', 'creator', 'performers', 'chat'])
        );
    }

    public function destroy(Request $request, Task $task)
    {
        $user = $request->user();
        if (!$user->hasRole('admin') && $task->creator_id !== $user->id) {
            return response()->json([
                'message' => 'Удалять задачу может только её создатель или администратор',
            ], 403);
        }
        DB::transaction(function () use ($task, $request) {
            TaskHistory::create([
                'task_id' => $task->id,
                'user_id' => $request->user()?->id,
                'action' => 'deleted',
                'old_values' => $task->load('performers')->toArray(),
                'new_values' => null,
                'comment' => 'Задача удалена',
            ]);

            $task->delete();
        });

        return response()->json([
            'message' => 'Задача удалена',
        ]);
    }
}