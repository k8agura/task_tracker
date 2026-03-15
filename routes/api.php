<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskStatusController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ProfileController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('tasks', TaskController::class);

    Route::get('/task-statuses', [TaskStatusController::class, 'index']);
    Route::get('/task-statuses/{taskStatus}', [TaskStatusController::class, 'show']);

    Route::get('/tasks/{task}/messages', [MessageController::class, 'index']);
    Route::post('/tasks/{task}/messages', [MessageController::class, 'store']);
    Route::get('/tasks/{task}/messages/{message}', [MessageController::class, 'show']);
    Route::delete('/tasks/{task}/messages/{message}', [MessageController::class, 'destroy']);
    Route::post('/tasks/{task}/complete', [TaskController::class, 'complete']);

    Route::get('/tasks/{task}/attachments', [AttachmentController::class, 'indexTaskAttachments']);
    Route::get('/messages/{message}/attachments', [AttachmentController::class, 'indexMessageAttachments']);
    Route::post('/attachments', [AttachmentController::class, 'store']);
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download']);
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

    Route::apiResource('reports', ReportController::class)->only(['index', 'store', 'show', 'destroy']);
    Route::get('/reports-workload', [ReportController::class, 'workload']);
});