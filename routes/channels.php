<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('task.{taskId}', function (User $user, int $taskId) {
    $task = Task::with(['creator', 'performers'])->find($taskId);

    if (!$task) {
        return false;
    }

    if ($user->hasRole('admin')) {
        return true;
    }

    if ($task->creator_id === $user->id) {
        return true;
    }

    if ($task->performers->contains('id', $user->id)) {
        return true;
    }

    if (
        $user->department_id &&
        $task->creator &&
        $task->creator->department_id === $user->department_id
    ) {
        return true;
    }

    return false;
});