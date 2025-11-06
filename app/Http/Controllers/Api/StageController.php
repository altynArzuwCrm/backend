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
use Illuminate\Support\Facades\DB;
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

        $stage = Stage::with('roles')->find($stage->id);
        return response()->json($stage, 201);
    }

    public function show(Stage $stage)
    {
        if (Gate::denies('view', $stage)) {
            abort(403, 'Доступ запрещён');
        }

        $stage = Stage::with('roles')->find($stage->id);
        return response()->json($stage);
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

        $stage = Stage::with('roles')->find($stage->id);
        return response()->json($stage);
    }

    public function destroy(Stage $stage)
    {
        if (Gate::denies('delete', $stage)) {
            abort(403, 'Доступ запрещён');
        }

        // Проверяем использование этапа во ВСЕХ заказах (активных и архивированных)
        $allOrdersCount = $stage->orders()->count();
        $activeOrdersCount = $stage->orders()->where('is_archived', false)->count();
        $archivedOrdersCount = $stage->orders()->where('is_archived', true)->count();
        
        // Проверяем использование этапа в продуктах (product_stages)
        $productsCount = DB::table('product_stages')
            ->where('stage_id', $stage->id)
            ->count();
        
        $errors = [];
        
        if ($allOrdersCount > 0) {
            $errors[] = "Этап используется в {$allOrdersCount} заказах";
            if ($activeOrdersCount > 0) {
                $errors[] = "из них {$activeOrdersCount} активных";
            }
            if ($archivedOrdersCount > 0) {
                $errors[] = "и {$archivedOrdersCount} архивированных";
            }
        }
        
        if ($productsCount > 0) {
            $errors[] = "Этап используется в {$productsCount} продуктах";
        }
        
        if (!empty($errors)) {
            return response()->json([
                'message' => 'Невозможно удалить этап: ' . implode(', ', $errors) . '. Сначала удалите или измените все связанные заказы и продукты.',
                'errors' => [
                    'orders_count' => $allOrdersCount,
                    'active_orders_count' => $activeOrdersCount,
                    'archived_orders_count' => $archivedOrdersCount,
                    'products_count' => $productsCount
                ]
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

        // Оптимизация: используем whereExists вместо whereHas
        $users = \App\Models\User::whereExists(function ($subquery) use ($roleIds) {
            $subquery->select(\Illuminate\Support\Facades\DB::raw(1))
                ->from('user_roles')
                ->whereColumn('user_roles.user_id', 'users.id')
                ->whereIn('user_roles.role_id', $roleIds);
        })
        ->select('id', 'name', 'username', 'email', 'phone', 'is_active')
        ->with(['roles' => function ($q) {
            $q->select('roles.id', 'roles.name', 'roles.display_name');
        }])
        ->get();

        return response()->json($users);
    }

    public function getAllUsersByStageRoles(Request $request)
    {
        $user = Auth::user();

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

            // Убрано подробное отладочное логирование

            $result = [];
            foreach ($stages as $stage) {
                $users = collect();
                foreach ($stage->roles as $role) {
                    $users = $users->merge($role->users);
                }
                $result[$stage->id] = $users->unique('id')->values();
            }

            // Убрано отладочное логирование результата

            return $result;
        }, [CacheService::TAG_STAGES, CacheService::TAG_USERS, CacheService::TAG_ROLES]);

        return response()->json($result);
    }
}
