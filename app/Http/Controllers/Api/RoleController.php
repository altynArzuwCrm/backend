<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        if (Gate::denies('viewAny', Role::class)) {
            abort(403, 'Доступ запрещён');
        }

        // Кэшируем роли на 2 часа для быстрых ответов
        $roles = CacheService::rememberWithTags(CacheService::PATTERN_ROLES_WITH_USERS, 7200, function () {
            return Role::select('id', 'name', 'display_name', 'description', 'created_at', 'updated_at')
                ->withCount('users')
                ->with(['stages' => function ($q) {
                    $q->select('stages.id', 'stages.name', 'stages.display_name', 'stages.order');
                }])
                ->orderBy('display_name')
                ->get();
        }, [CacheService::TAG_ROLES]);

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

        // Инвалидируем кэш ролей
        CacheService::invalidateRoleCaches();

        return response()->json($role, 201);
    }

    public function show(Role $role)
    {
        if (Gate::denies('view', $role)) {
            abort(403, 'Доступ запрещён');
        }

        // Оптимизация: загружаем только необходимые поля
        $role = Role::select('id', 'name', 'display_name', 'description', 'created_at', 'updated_at')
            ->with([
                'users' => function ($q) {
                    $q->select('id', 'name', 'username');
                },
                'stages' => function ($q) {
                    $q->select('stages.id', 'stages.name', 'stages.display_name', 'stages.order');
                }
            ])
            ->find($role->id);
        return response()->json($role);
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

        // Инвалидируем кэш ролей
        CacheService::invalidateRoleCaches();

        // Оптимизация: загружаем только необходимые поля
        $role = Role::select('id', 'name', 'display_name', 'description', 'created_at', 'updated_at')
            ->with(['stages' => function ($q) {
                $q->select('id', 'name', 'display_name', 'order');
            }])
            ->find($role->id);
        return response()->json($role);
    }

    public function destroy(Role $role)
    {
        if (Gate::denies('delete', $role)) {
            abort(403, 'Доступ запрещён');
        }

        // Prevent deletion if role is being used in active assignments
        // Оптимизация: используем whereExists вместо whereHas
        $activeAssignmentsCount = \App\Models\User::whereExists(function ($subquery) use ($role) {
            $subquery->select(\Illuminate\Support\Facades\DB::raw(1))
                ->from('user_roles')
                ->whereColumn('user_roles.user_id', 'users.id')
                ->where('user_roles.role_id', $role->id);
        })
        ->whereExists(function ($subquery) {
            $subquery->select(\Illuminate\Support\Facades\DB::raw(1))
                ->from('order_assignments')
                ->join('orders', 'order_assignments.order_id', '=', 'orders.id')
                ->whereColumn('order_assignments.user_id', 'users.id')
                ->where('orders.is_archived', false);
        })
        ->count();

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

        // Инвалидируем кэш ролей
        CacheService::invalidateRoleCaches();

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

        // Инвалидируем кэш ролей и пользователей
        CacheService::invalidateRoleCaches();

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

        // Инвалидируем кэш ролей и пользователей
        CacheService::invalidateRoleCaches();

        return response()->json([
            'message' => 'Пользователи успешно исключены из роли'
        ]);
    }
}
