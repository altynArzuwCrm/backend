<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stage;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class StageController extends Controller
{
    public function index()
    {
        if (Gate::denies('viewAny', Stage::class)) {
            abort(403, 'Доступ запрещён');
        }

        $stages = Stage::with('roles')
            ->active()
            ->ordered()
            ->get();

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
            'is_active' => 'boolean',
            'is_initial' => 'boolean',
            'is_final' => 'boolean',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'roles' => 'nullable|array',
            'roles.*.role_id' => 'required|exists:roles,id',
            'roles.*.is_required' => 'boolean',
            'roles.*.auto_assign' => 'boolean',
        ]);

        // Ensure only one initial stage
        if ($data['is_initial'] ?? false) {
            Stage::where('is_initial', true)->update(['is_initial' => false]);
        }

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
            'is_active' => $data['is_active'] ?? true,
            'is_initial' => $data['is_initial'] ?? false,
            'is_final' => $data['is_final'] ?? false,
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
            'is_active' => 'boolean',
            'is_initial' => 'boolean',
            'is_final' => 'boolean',
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

        // Ensure only one initial stage
        if (($data['is_initial'] ?? false) && !$stage->is_initial) {
            Stage::where('is_initial', true)->update(['is_initial' => false]);
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

        // Prevent deletion if stage is being used
        if ($stage->orders()->exists()) {
            return response()->json([
                'message' => 'Невозможно удалить стадию, которая используется в заказах'
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
        if (Gate::denies('viewAny', \App\Models\User::class)) {
            abort(403, 'Доступ запрещён');
        }

        try {
            // Получаем роли, связанные со стадией
            $stageRoles = $stage->roles()->with(['users' => function ($query) {
                $query->where('is_active', true)
                    ->select('users.id', 'users.name', 'users.username');
            }])->get();

            $usersByRole = [];

            foreach ($stageRoles as $role) {
                $users = $role->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'username' => $user->username
                    ];
                });

                $usersByRole[$role->name] = [
                    'role' => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'display_name' => $role->display_name
                    ],
                    'users' => $users
                ];
            }

            return response()->json([
                'stage' => [
                    'id' => $stage->id,
                    'name' => $stage->name,
                    'display_name' => $stage->display_name,
                    'color' => $stage->color
                ],
                'users_by_role' => $usersByRole
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getUsersByStageRoles: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка при получении пользователей по ролям стадии',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllUsersByStageRoles(Request $request)
    {
        if (Gate::denies('viewAny', \App\Models\User::class)) {
            abort(403, 'Доступ запрещён');
        }

        try {
            // Получаем все активные стадии с их ролями
            $stages = Stage::active()->with(['roles' => function ($query) {
                $query->with(['users' => function ($userQuery) {
                    $userQuery->where('is_active', true)
                        ->select('users.id', 'users.name', 'users.username');
                }]);
            }])->get();

            $usersByStage = [];

            foreach ($stages as $stage) {
                $usersByRole = [];

                foreach ($stage->roles as $role) {
                    $users = $role->users->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'username' => $user->username
                        ];
                    });

                    $usersByRole[$role->name] = [
                        'role' => [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => $role->display_name
                        ],
                        'users' => $users
                    ];
                }

                $usersByStage[$stage->name] = [
                    'stage' => [
                        'id' => $stage->id,
                        'name' => $stage->name,
                        'display_name' => $stage->display_name,
                        'color' => $stage->color
                    ],
                    'users_by_role' => $usersByRole
                ];
            }

            return response()->json($usersByStage);
        } catch (\Exception $e) {
            Log::error('Error in getAllUsersByStageRoles: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка при получении пользователей по ролям стадий',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
