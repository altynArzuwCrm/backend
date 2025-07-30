<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        if (Gate::denies('viewAny', Role::class)) {
            abort(403, 'Доступ запрещён');
        }

        $roles = Role::withCount('users')->with('stages')->orderBy('display_name')->get();
        return response()->json($roles);
    }

    public function store(Request $request)
    {
        if (Gate::denies('create', Role::class)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name|regex:/^[a-z_]+$/',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $role = Role::create($data);

        return response()->json($role, 201);
    }

    public function show(Role $role)
    {
        if (Gate::denies('view', $role)) {
            abort(403, 'Доступ запрещён');
        }

        return response()->json($role->load(['users', 'stages']));
    }

    public function update(Request $request, Role $role)
    {
        if (Gate::denies('update', $role)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', 'regex:/^[a-z_]+$/', Rule::unique('roles')->ignore($role)],
            'display_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $role->update($data);

        return response()->json($role->load('stages'));
    }

    public function destroy(Role $role)
    {
        if (Gate::denies('delete', $role)) {
            abort(403, 'Доступ запрещён');
        }

        // Prevent deletion if role is being used in active assignments
        $activeAssignmentsCount = $role->users()->whereHas('assignments', function ($query) {
            $query->whereHas('order', function ($orderQuery) {
                $orderQuery->where('is_archived', false);
            });
        })->count();

        if ($activeAssignmentsCount > 0) {
            return response()->json([
                'message' => "Невозможно удалить роль, которая используется в {$activeAssignmentsCount} активных назначениях"
            ], 422);
        }

        // Remove role from all users
        $role->users()->detach();

        // Remove role from all stages
        $role->stages()->detach();

        $role->delete();

        return response()->json([
            'message' => 'Роль успешно удалена'
        ]);
    }

    public function assignUsers(Request $request, Role $role)
    {
        if (Gate::denies('update', $role)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $role->users()->attach($data['user_ids']);

        return response()->json([
            'message' => 'Пользователи успешно назначены на роль'
        ]);
    }

    public function removeUsers(Request $request, Role $role)
    {
        if (Gate::denies('update', $role)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $role->users()->detach($data['user_ids']);

        return response()->json([
            'message' => 'Пользователи успешно исключены из роли'
        ]);
    }
}
