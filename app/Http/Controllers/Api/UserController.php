<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class UserController
{
    public function index()
    {
        $users = User::all();

        return response()->json($users);
    }

    public function show(User $user)
    {
        if (Gate::denies('view', $user)) {abort(403);}
        return response()->json($user);
    }

    public function store(Request $request)
    {
        if (Gate::denies('create', User::class)) {abort(403);}

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'phone' => 'required|digits:8|unique:users,phone',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,manager,executor',
        ]);

        if ($request->password) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user = User::create($validated);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        if (Gate::denies('update', $user)) {abort(403);}

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'surname' => 'sometimes|string|max:255',
            'phone' => 'sometimes|digits:8|unique:users,phone,' . $user->id,
            'password' => 'sometimes|string|min:6',
            'role' => 'sometimes|in:admin,manager,executor',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ]);
    }

    public function destroy(User $user)
    {
        if (Gate::denies('delete', $user)) {abort(403);}

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
