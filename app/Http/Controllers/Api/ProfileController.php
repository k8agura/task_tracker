<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(
            $request->user()->load(['department', 'roles'])
        );
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'Профиль обновлён',
            'user' => $user->fresh()->load(['department', 'roles']),
        ]);
    }
}