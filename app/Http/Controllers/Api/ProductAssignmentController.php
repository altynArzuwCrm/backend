<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductAssignmentResource;
use App\Models\Product;
use App\Models\ProductAssignment;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;

class ProductAssignmentController extends Controller
{
    public function index(Request $request, Product $product)
    {
        if (Gate::denies('view', $product)) {
            abort(403, 'Доступ запрещён');
        }

        $assignments = $product->assignments()
            ->with('user')
            ->orderBy('role_type')
            ->get();

        return response()->json([
            'product_id' => $product->id,
            'assignments' => ProductAssignmentResource::collection($assignments)
        ]);
    }

    public function store(Request $request, Product $product)
    {
        if (Gate::denies('update', $product)) {
            abort(403, 'Доступ запрещён');
        }

        // Получаем все доступные роли из базы данных
        $availableRoles = \App\Models\Role::pluck('name')->toArray();

        // Если ролей нет, используем базовые роли
        if (empty($availableRoles)) {
            $availableRoles = ['designer', 'print_operator', 'engraving_operator', 'workshop_worker'];
        }

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_type' => 'required|string|in:' . implode(',', $availableRoles),
            'is_active' => 'sometimes|boolean'
        ]);

        $user = User::findOrFail($data['user_id']);
        if (!$user->hasRole($data['role_type'])) {
            return response()->json([
                'message' => 'Пользователь не имеет роль ' . $data['role_type']
            ], 422);
        }

        $existingAssignment = $product->assignments()
            ->where('user_id', $data['user_id'])
            ->where('role_type', $data['role_type'])
            ->first();

        if ($existingAssignment) {
            return response()->json([
                'message' => 'Пользователь уже назначен на эту роль для данного продукта'
            ], 422);
        }

        $assignment = $product->assignments()->create([
            'user_id' => $data['user_id'],
            'role_type' => $data['role_type'],
            'is_active' => $data['is_active'] ?? true
        ]);

        // Очищаем кэш продуктов после создания назначения
        CacheService::invalidateProductCaches();

        // Используем with() вместо load() для предотвращения N+1 проблемы
        $assignment = ProductAssignment::with('user')->find($assignment->id);
        return response()->json([
            'message' => 'Назначение создано',
            'assignment' => new ProductAssignmentResource($assignment)
        ], 201);
    }

    public function update(Request $request, Product $product, ProductAssignment $assignment)
    {
        if (Gate::denies('update', $product)) {
            abort(403, 'Доступ запрещён');
        }

        if ($assignment->product_id !== $product->id) {
            abort(404, 'Назначение не найдено');
        }

        $data = $request->validate([
            'is_active' => 'sometimes|boolean'
        ]);

        $assignment->update($data);

        // Очищаем кэш продуктов после обновления назначения
        CacheService::invalidateProductCaches();

        // Используем with() вместо load() для предотвращения N+1 проблемы
        $assignment = ProductAssignment::with('user')->find($assignment->id);
        return response()->json([
            'message' => 'Назначение обновлено',
            'assignment' => new ProductAssignmentResource($assignment)
        ]);
    }

    public function destroy(Product $product, ProductAssignment $assignment)
    {
        if (Gate::denies('update', $product)) {
            abort(403, 'Доступ запрещён');
        }

        if ($assignment->product_id !== $product->id) {
            abort(404, 'Назначение не найдено');
        }

        $assignment->delete();

        // Очищаем кэш продуктов после удаления назначения
        CacheService::invalidateProductCaches();

        return response()->json([
            'message' => 'Назначение удалено'
        ]);
    }

    public function bulkAssign(Request $request, Product $product)
    {
        if (Gate::denies('update', $product)) {
            abort(403, 'Доступ запрещён');
        }

        // Получаем все доступные роли из базы данных
        $availableRoles = \App\Models\Role::pluck('name')->toArray();

        // Если ролей нет, используем базовые роли
        if (empty($availableRoles)) {
            $availableRoles = ['designer', 'print_operator', 'engraving_operator', 'workshop_worker'];
        }

        $data = $request->validate([
            'assignments' => 'array',
            'assignments.*.user_id' => 'required|exists:users,id',
            'assignments.*.role_type' => 'required|string|in:' . implode(',', $availableRoles),
            'assignments.*.is_active' => 'sometimes|boolean'
        ]);

        $currentAssignments = $product->assignments()->get();

        // Если массив назначений пустой, удаляем все существующие назначения
        if (empty($data['assignments'])) {
            foreach ($currentAssignments as $assignment) {
                $assignment->delete();
            }
        } else {
            $newAssignmentsMap = collect($data['assignments'])->keyBy(function ($a) {
                return $a['user_id'] . '_' . $a['role_type'];
            });

            foreach ($currentAssignments as $assignment) {
                $key = $assignment->user_id . '_' . $assignment->role_type;

                if ($newAssignmentsMap->has($key)) {
                    $newData = $newAssignmentsMap->get($key);
                    $assignment->update([
                        'is_active' => $newData['is_active'] ?? true
                    ]);
                    $newAssignmentsMap->forget($key);
                } else {
                    // Физическое удаление назначения
                    $assignment->delete();
                }
            }
        }

        $createdAssignments = [];
        $errors = [];

        // Создаем новые назначения только если массив не пустой
        if (!empty($data['assignments'])) {
            foreach ($newAssignmentsMap as $assignmentData) {
                try {
                    $user = \App\Models\User::findOrFail($assignmentData['user_id']);
                    if (!$user->hasRole($assignmentData['role_type'])) {
                        $errors[] = "Пользователь не имеет роль {$assignmentData['role_type']}";
                        continue;
                    }

                    $assignment = $product->assignments()->create([
                        'user_id' => $assignmentData['user_id'],
                        'role_type' => $assignmentData['role_type'],
                        'is_active' => $assignmentData['is_active'] ?? true
                    ]);
                    // Используем with() вместо load() для предотвращения N+1 проблемы
                    $assignment = ProductAssignment::with('user')->find($assignment->id);
                    $createdAssignments[] = $assignment;
                } catch (\Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }

        // Очищаем кэш продуктов после изменения назначений
        CacheService::invalidateProductCaches();

        $response = [
            'message' => 'Массовое назначение завершено',
            'created_assignments' => $createdAssignments,
            'total_created' => count($createdAssignments)
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, !empty($errors) ? 207 : 201);
    }

    public function getAvailableUsers(Request $request, Product $product)
    {
        if (Gate::denies('view', $product)) {
            abort(403, 'Доступ запрещён');
        }

        // Получаем все доступные роли из базы данных
        $availableRoles = \App\Models\Role::pluck('name')->toArray();

        // Если ролей нет, используем базовые роли
        if (empty($availableRoles)) {
            $availableRoles = ['designer', 'print_operator', 'engraving_operator', 'workshop_worker'];
        }

        $roleType = $request->validate([
            'role_type' => 'required|string|in:' . implode(',', $availableRoles)
        ])['role_type'];

        // Оптимизация: используем whereExists вместо whereHas
        $users = User::whereExists(function ($subquery) use ($roleType) {
                $subquery->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('user_roles')
                    ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                    ->whereColumn('user_roles.user_id', 'users.id')
                    ->where('roles.name', $roleType);
            })
            ->select('id', 'name', 'username')
            ->where('is_active', true)
            ->get();

        $assignedUserIds = $product->assignments()
            ->where('role_type', $roleType)
            ->pluck('user_id');

        $availableUsers = $users->whereNotIn('id', $assignedUserIds);

        return response()->json([
            'role_type' => $roleType,
            'available_users' => $availableUsers
        ]);
    }
}
