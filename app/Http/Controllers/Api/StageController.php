<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stage;
use App\Models\Role;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class StageController extends Controller
{
    public function index()
    {
        if (Gate::denies('viewAny', Stage::class)) {
            abort(403, 'Доступ запрещён');
        }

        // Кэшируем стадии на 4 часа для быстрых ответов
        $stages = CacheService::rememberWithTags(CacheService::PATTERN_STAGES_WITH_ROLES, 14400, function () {
            return Stage::with('roles')
                ->ordered()
                ->get();
        }, [CacheService::TAG_STAGES]);

        return response()->json($stages);
    }

    public function store(Request $request)
    {
        if (Gate::denies('create', Stage::class)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:stages,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'required|integer|min:1',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'roles' => 'nullable|array',
            'roles.*.role_id' => 'required|exists:roles,id',
            'roles.*.is_required' => 'boolean',
            'roles.*.auto_assign' => 'boolean',
        ]);

        // Adjust orders of other stages
        if (isset($data['order'])) {
            Stage::where('order', '>=', $data['order'])
                ->increment('order');
        }

        $stage = Stage::create([
            'name' => $data['name'],
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
            'order' => $data['order'],
            'color' => $data['color'] ?? '#6366f1',
        ]);

        // Attach roles if provided
        if (isset($data['roles'])) {
            $roleData = [];
            foreach ($data['roles'] as $roleInfo) {
                $roleData[$roleInfo['role_id']] = [
                    'is_required' => $roleInfo['is_required'] ?? false,
                    'auto_assign' => $roleInfo['auto_assign'] ?? true,
                ];
            }
            $stage->roles()->attach($roleData);
        }

        return response()->json($stage->load('roles'), 201);
    }

    public function show(Stage $stage)
    {
        if (Gate::denies('view', $stage)) {
            abort(403, 'Доступ запрещён');
        }

        return response()->json($stage->load('roles'));
    }

    public function update(Request $request, Stage $stage)
    {
        if (Gate::denies('update', $stage)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('stages')->ignore($stage)],
            'display_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'order' => 'sometimes|integer|min:1',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'roles' => 'nullable|array',
            'roles.*.role_id' => 'required|exists:roles,id',
            'roles.*.is_required' => 'boolean',
            'roles.*.auto_assign' => 'boolean',
        ]);

        // Handle order changes
        if (isset($data['order']) && $data['order'] !== $stage->order) {
            $oldOrder = $stage->order;
            $newOrder = $data['order'];

            if ($newOrder > $oldOrder) {
                // Moving down: decrease order of stages between old and new positions
                Stage::whereBetween('order', [$oldOrder + 1, $newOrder])
                    ->decrement('order');
            } else {
                // Moving up: increase order of stages between new and old positions
                Stage::whereBetween('order', [$newOrder, $oldOrder - 1])
                    ->increment('order');
            }
        }

        $stage->update($data);

        // Update roles if provided
        if (isset($data['roles'])) {
            $roleData = [];
            foreach ($data['roles'] as $roleInfo) {
                $roleData[$roleInfo['role_id']] = [
                    'is_required' => $roleInfo['is_required'] ?? false,
                    'auto_assign' => $roleInfo['auto_assign'] ?? true,
                ];
            }
            $stage->roles()->sync($roleData);
        }

        return response()->json($stage->load('roles'));
    }

    public function destroy(Stage $stage)
    {
        if (Gate::denies('delete', $stage)) {
            abort(403, 'Доступ запрещён');
        }

        // Prevent deletion if stage is being used in active orders
        $activeOrdersCount = $stage->orders()->where('is_archived', false)->count();
        if ($activeOrdersCount > 0) {
            return response()->json([
                'message' => "Невозможно удалить стадию, которая используется в {$activeOrdersCount} активных заказах"
            ], 422);
        }

        // Adjust orders of remaining stages
        Stage::where('order', '>', $stage->order)
            ->decrement('order');

        $stage->delete();

        return response()->json([
            'message' => 'Стадия успешно удалена'
        ]);
    }

    public function reorder(Request $request)
    {
        if (Gate::denies('updateAny', Stage::class)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'stages' => 'required|array',
            'stages.*.id' => 'required|exists:stages,id',
            'stages.*.order' => 'required|integer|min:1',
        ]);

        foreach ($data['stages'] as $stageData) {
            Stage::where('id', $stageData['id'])
                ->update(['order' => $stageData['order']]);
        }

        return response()->json([
            'message' => 'Порядок стадий успешно обновлен'
        ]);
    }

    public function availableRoles()
    {
        // Временно убираем проверку прав для отладки
        // if (Gate::denies('viewAny', Role::class)) {
        //     abort(403, 'Доступ запрещён');
        // }

        $roles = Role::orderBy('display_name')->get();
        return response()->json($roles);
    }

    public function getUsersByStageRoles(Request $request, Stage $stage)
    {
        if (Gate::denies('view', $stage)) {
            abort(403, 'Доступ запрещён');
        }

        $roleIds = $stage->roles()->pluck('roles.id');

        if ($roleIds->isEmpty()) {
            return response()->json([]);
        }

        $users = \App\Models\User::whereHas('roles', function ($query) use ($roleIds) {
            $query->whereIn('roles.id', $roleIds);
        })->with('roles')->get();

        return response()->json($users);
    }

    public function getAllUsersByStageRoles(Request $request)
    {
        $user = Auth::user();
        Log::info('StageController::getAllUsersByStageRoles called', [
            'user_id' => $user ? $user->id : null,
            'user_roles' => $user ? $user->roles->pluck('name')->toArray() : []
        ]);

        // Временно убираем проверку прав для отладки
        // if (Gate::denies('viewAny', Stage::class)) {
        //     Log::warning('Access denied for getAllUsersByStageRoles', [
        //         'user_id' => auth()->id()
        //     ]);
        //     abort(403, 'Доступ запрещён');
        // }

        // Кешируем результат на 5 минут с тегами для инвалидации (уменьшено с 30 минут)
        $cacheKey = 'stages_users_by_roles_all';
        $result = CacheService::rememberWithTags($cacheKey, 300, function () {
            $stages = Stage::with(['roles.users' => function ($query) {
                $query->select('users.id', 'name', 'username', 'phone', 'is_active')
                    ->where('is_active', true); // Только активные пользователи
            }])->get();

            // Дополнительная проверка: логируем всех пользователей с ролями
            $allUsersWithRoles = User::with('roles')->where('is_active', true)->get();
            Log::info('All active users with roles', [
                'total_users' => $allUsersWithRoles->count(),
                'users_with_roles' => $allUsersWithRoles->filter(function ($user) {
                    return $user->roles->count() > 0;
                })->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'roles' => $user->roles->pluck('name')->toArray()
                    ];
                })->toArray()
            ]);

            Log::info('Stages loaded with roles and users', [
                'stages_count' => $stages->count(),
                'stages_with_roles' => $stages->map(function ($stage) {
                    return [
                        'stage_id' => $stage->id,
                        'stage_name' => $stage->name,
                        'roles_count' => $stage->roles->count(),
                        'users_count' => $stage->roles->sum(function ($role) {
                            return $role->users->count();
                        })
                    ];
                })->toArray()
            ]);

            $result = [];
            foreach ($stages as $stage) {
                $users = collect();
                foreach ($stage->roles as $role) {
                    $users = $users->merge($role->users);
                }
                $result[$stage->id] = $users->unique('id')->values();
            }

            Log::info('getAllUsersByStageRoles result', [
                'result_keys' => array_keys($result),
                'total_users' => collect($result)->flatten(1)->count()
            ]);

            return $result;
        }, [CacheService::TAG_STAGES, CacheService::TAG_USERS, CacheService::TAG_ROLES]);

        return response()->json($result);
    }
}
