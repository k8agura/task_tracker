<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Models\Report;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            (new Middleware('permission:reports.view'))->only(['index', 'show']),
            (new Middleware('permission:reports.create'))->only(['store']),
            (new Middleware('permission:reports.delete'))->only(['destroy']),
        ];
    }
    
    public function index()
    {
        return response()->json(
            Report::with('creator')->latest()->paginate(15)
        );
    }

    public function store(StoreReportRequest $request)
    {
        $filters = $request->validated();

        $tasksQuery = Task::with(['status', 'creator.department', 'performers', 'completedBy']);

        if (!empty($filters['date_from'])) {
            $tasksQuery->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $tasksQuery->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['status_id'])) {
            $tasksQuery->where('status_id', $filters['status_id']);
        }

        if (!empty($filters['creator_id'])) {
            $tasksQuery->where('creator_id', $filters['creator_id']);
        }

        if (!empty($filters['department_id'])) {
            $tasksQuery->whereHas('creator', function ($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }

        $tasks = $tasksQuery->get();

        $doneTasks = $tasks->filter(fn($task) => $task->status?->code === 'done');
        $inProgressTasks = $tasks->filter(fn($task) => $task->status?->code === 'in_progress');
        $createdTasks = $tasks->filter(fn($task) => $task->status?->code === 'created');
        $reviewTasks = $tasks->filter(fn($task) => $task->status?->code === 'review');
        $cancelledTasks = $tasks->filter(fn($task) => $task->status?->code === 'cancelled');

        $overdueTasks = $tasks->filter(function ($task) {
            return $task->due_date
                && $task->due_date->isPast()
                && $task->status?->code !== 'done';
        });

        $doneDurations = $doneTasks
            ->filter(fn($task) => $task->created_at && $task->updated_at)
            ->map(fn($task) => $task->created_at->diffInDays($task->updated_at));

        $topClosers = $doneTasks
            ->groupBy(function ($task) {
                if ($task->completedBy) {
                    return trim(
                        ($task->completedBy->last_name ?? '') . ' ' .
                        ($task->completedBy->first_name ?? '') . ' ' .
                        ($task->completedBy->middle_name ?? '')
                    );
                }

                if ($task->creator) {
                    return trim(
                        ($task->creator->last_name ?? '') . ' ' .
                        ($task->creator->first_name ?? '') . ' ' .
                        ($task->creator->middle_name ?? '')
                    );
                }

                return 'Неизвестно';
            })
            ->map->count()
            ->sortDesc()
            ->take(5)
            ->map(fn ($count, $name) => [
                'name' => $name,
                'count' => $count,
            ])
            ->values();

        $closedTaskDetails = $doneTasks
            ->map(function ($task) {
                $completedByName = $task->completedBy
                    ? trim(
                        ($task->completedBy->last_name ?? '') . ' ' .
                        ($task->completedBy->first_name ?? '') . ' ' .
                        ($task->completedBy->middle_name ?? '')
                    )
                    : null;

                $creatorName = $task->creator
                    ? trim(
                        ($task->creator->last_name ?? '') . ' ' .
                        ($task->creator->first_name ?? '') . ' ' .
                        ($task->creator->middle_name ?? '')
                    )
                    : null;

                return [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'closed_by' => $completedByName ?: ($creatorName ?: 'Неизвестно'),
                    'completed_at' => $task->completed_at?->toDateTimeString(),
                    'completion_days' => ($task->created_at && $task->completed_at)
                        ? $task->created_at->diffInDays($task->completed_at)
                        : null,
                ];
            })
            ->values();

        $data = [
            'total_tasks' => $tasks->count(),
            'open_tasks' => $createdTasks->count(),
            'in_progress_tasks' => $inProgressTasks->count(),
            'review_tasks' => $reviewTasks->count(),
            'done_tasks' => $doneTasks->count(),
            'cancelled_tasks' => $cancelledTasks->count(),
            'overdue_tasks' => $overdueTasks->count(),

            'avg_completion_days' => $doneDurations->count()
                ? round($doneDurations->avg(), 1)
                : 0,

            'tasks_by_status' => $tasks
                ->groupBy(fn($task) => $task->status?->name ?? 'Без статуса')
                ->map->count(),

            'tasks_by_priority' => $tasks
                ->groupBy('priority')
                ->map->count(),

            'tasks_by_creator' => $tasks
                ->groupBy(fn($task) => $task->creator?->full_name ?? 'Неизвестно')
                ->map->count(),

            'top_closers' => $topClosers,
            'closed_task_details' => $closedTaskDetails,
        ];

        $report = Report::create([
            'name' => $filters['name'],
            'created_by' => $request->user()->id,
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
            'filters' => $filters,
            'data' => $data,
        ]);

        return response()->json(
            $report->load('creator'),
            201
        );
    }

    public function show(Report $report)
    {
        return response()->json(
            $report->load('creator')
        );
    }

    public function workload(Request $request)
    {
        $query = \App\Models\User::query()
            ->with('department')
            ->where('is_active', true)
            ->withCount(['tasks as assigned_tasks_count' => function ($q) {
                $q->whereHas('status', function ($statusQ) {
                    $statusQ->where('code', '!=', 'done');
                });
            }]);

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }

        $users = $query->orderByDesc('assigned_tasks_count')
            ->orderBy('last_name')
            ->get();

        return response()->json($users);
    }

    public function destroy(Report $report)
    {
        $report->delete();

        return response()->json([
            'message' => 'Отчёт удалён',
        ]);
    }
}