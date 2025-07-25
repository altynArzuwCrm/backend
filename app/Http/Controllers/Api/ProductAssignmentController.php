<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductAssignmentResource;
use App\Models\Product;
use App\Models\ProductAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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
            ->get()
            ->groupBy('role_type');

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

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_type' => 'required|in:designer,print_operator,engraving_operator,workshop_worker',
            'is_active' => 'sometimes|boolean'
        ]);

        // Проверяем, что пользователь имеет соответствующую роль
        $user = User::findOrFail($data['user_id']);
        if (!$user->hasRole($data['role_type'])) {
            return response()->json([
                'message' => 'Пользователь не имеет роль ' . $data['role_type']
            ], 422);
        }

        // Проверяем уникальность назначения
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

        return response()->json([
            'message' => 'Назначение создано',
            'assignment' => new ProductAssignmentResource($assignment->load('user'))
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

        return response()->json([
            'message' => 'Назначение обновлено',
            'assignment' => new ProductAssignmentResource($assignment->load('user'))
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

        return response()->json([
            'message' => 'Назначение удалено'
        ]);
    }

    public function bulkAssign(Request $request, Product $product)
    {
        if (Gate::denies('update', $product)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'assignments' => 'required|array|min:1',
            'assignments.*.user_id' => 'required|exists:users,id',
            'assignments.*.role_type' => 'required|in:designer,print_operator,engraving_operator,workshop_worker',

            'assignments.*.is_active' => 'sometimes|boolean'
        ]);

        $createdAssignments = [];
        $errors = [];

        foreach ($data['assignments'] as $index => $assignmentData) {
            try {
                // Проверяем роль пользователя
                $user = User::findOrFail($assignmentData['user_id']);
                if (!$user->hasRole($assignmentData['role_type'])) {
                    $errors[] = "Строка {$index}: Пользователь не имеет роль {$assignmentData['role_type']}";
                    continue;
                }

                // Проверяем уникальность
                $existingAssignment = $product->assignments()
                    ->where('user_id', $assignmentData['user_id'])
                    ->where('role_type', $assignmentData['role_type'])
                    ->first();

                if ($existingAssignment) {
                    $errors[] = "Строка {$index}: Пользователь уже назначен на эту роль";
                    continue;
                }

                $assignment = $product->assignments()->create([
                    'user_id' => $assignmentData['user_id'],
                    'role_type' => $assignmentData['role_type'],
                    'is_active' => $assignmentData['is_active'] ?? true
                ]);

                $createdAssignments[] = $assignment->load('user');
            } catch (\Exception $e) {
                $errors[] = "Строка {$index}: " . $e->getMessage();
            }
        }

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

        $roleType = $request->validate([
            'role_type' => 'required|in:designer,print_operator,engraving_operator,workshop_worker'
        ])['role_type'];

        // Получаем пользователей с соответствующей ролью
        $users = User::whereHas('roles', function ($q) use ($roleType) {
            $q->where('name', $roleType);
        })
            ->where('is_active', true)
            ->get();

        // Исключаем уже назначенных пользователей
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
 