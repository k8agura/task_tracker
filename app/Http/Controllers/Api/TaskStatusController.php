<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaskStatus;

class TaskStatusController extends Controller
{
    public function index()
    {
        return response()->json(
            TaskStatus::orderBy('id')->get()
        );
    }

    public function show(TaskStatus $taskStatus)
    {
        return response()->json($taskStatus);
    }
}