<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        if (Gate::denies('viewAny', User::class)) {
            abort(403, 'Доступ запрещён');
        }
        $users = User::with('role')->paginate(20);

        return UserResource::collection($users);
    }

    public function show(User $user)
    {
        if (Gate::denies('view', $user)) {
            abort(403, 'Доступ запрещён');
        }
        $this->authorize('view', $user);

        return new UserResource($user->load('role'));
    }

    public function store(Request $request)
    {
        if (Gate::denies('create', User::class)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'name' => 'required|string',
            'image' => 'nullable|image|max:2048',
            'username' => 'required|string|unique:users,username',
            'phone' => 'nullable|string',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('images', 'public');
        }

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'image' => $data['image'] ?? null,
            'role_id' => $data['role_id'],
        ]);

        return new UserResource($user->load('role'));
    }

    public function update(Request $request, User $user)
    {
        if (Gate::denies('update', $user)) {
            abort(403, 'Доступ запрещён');
        }
        $data = $request->validate([
            'name' => 'sometimes|string',
            'phone' => 'nullable|string',
            'password' => 'nullable|string|min:6',
            'image' => 'nullable|image|max:2048',
            'role_id' => 'sometimes|required|exists:roles,id',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public');
            $data['image'] = $imagePath;
        }

        if (isset($data['name'])) {
            $user->name = $data['name'];
        }
        if (isset($data['phone'])) {
            $user->phone = $data['phone'];
        }
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        if (isset($data['role_id'])) {
            $user->role_id = $data['role_id'];
        }
        $user->save();

        return new UserResource($user->load('role'));
    }

    public function destroy(User $user)
    {
        if (Gate::denies('delete', $user)) {
            abort(403, 'Доступ запрещён');
        }

        $user->delete();

        return response()->json(['message' => 'Пользователь удалён']);
    }
}
