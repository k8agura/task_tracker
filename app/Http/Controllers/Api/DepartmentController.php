<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DepartmentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            (new Middleware('permission:departments.view'))->only(['index', 'show']),
            (new Middleware('permission:departments.create'))->only(['store']),
            (new Middleware('permission:departments.update'))->only(['update']),
            (new Middleware('permission:departments.delete'))->only(['destroy']),
        ];
    }

    public function index()
    {
        return response()->json(
            Department::withCount('users')->orderBy('name')->get()
        );
    }

    public function store(StoreDepartmentRequest $request)
    {
        $department = Department::create($request->validated());

        return response()->json($department, 201);
    }

    public function show(Department $department)
    {
        return response()->json(
            $department->load('users')
        );
    }

    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $department->update($request->validated());

        return response()->json($department);
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return response()->json([
            'message' => 'Подразделение удалено',
        ]);
    }
}