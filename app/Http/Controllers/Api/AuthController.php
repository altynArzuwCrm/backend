<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\UserResource;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|confirmed|min:6',
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
        ]);

        $user = User::create([
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
        ]);
        $user->roles()->sync($data['roles']);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->fresh('roles')),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $data['username'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Неверный логин или пароль'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Ваш аккаунт деактивирован. Обратитесь к администратору.'], 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->fresh('roles')),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Вы успешно вышли']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('roles');
        return new UserResource($user);
    }
}
