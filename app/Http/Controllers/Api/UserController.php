<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            (new Middleware('permission:users.view'))->only(['index', 'show']),
            (new Middleware('permission:users.create'))->only(['store']),
            (new Middleware('permission:users.update'))->only(['update']),
            (new Middleware('permission:users.delete'))->only(['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $query = User::with(['department', 'roles']);

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('last_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('middle_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $query->orderBy('last_name')->orderBy('first_name')->paginate(15)
        );
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        $data['is_active'] = $data['is_active'] ?? true;

        $user = User::create($data);

        if (!empty($roles)) {
            $user->syncRoles($roles);
        }

        return response()->json(
            $user->load(['department', 'roles']),
            201
        );
    }

    public function show(User $user)
    {
        return response()->json(
            $user->load(['department', 'roles'])
        );
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();
        $roles = $data['roles'] ?? null;
        unset($data['roles']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        if (is_array($roles)) {
            $user->syncRoles($roles);
        }

        return response()->json(
            $user->load(['department', 'roles'])
        );
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'message' => 'Пользователь удалён',
        ]);
    }
}