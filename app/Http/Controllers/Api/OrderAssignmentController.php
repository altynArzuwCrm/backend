<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\User;
use App\Models\Product;
use App\Models\Stage;
use App\Models\OrderStageAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Notifications\OrderAssigned;

class OrderAssignmentController extends Controller
{
    public function index(Request $request)
    {
        if (Gate::denies('viewAny', OrderAssignment::class)) {
            abort(403, 'Доступ запрещён');
        }

        $user = Auth::user();
        $query = OrderAssignment::query()->with(['user.roles', 'order', 'assignedBy']);

        if (!$user->hasAnyRole(['admin', 'manager'])) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(
            $query->paginate(10)
        );
    }

    public function show(OrderAssignment $assignment)
    {
        if (Gate::denies('view', $assignment)) {
            abort(403, 'Доступ запрещён');
        }

        return response()->json($assignment->load(['user.roles', 'order', 'assignedBy']));
    }

    public function assign(Request $request, Order $order)
    {
        if (Gate::denies('assign', OrderAssignment::class)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_type' => 'sometimes|string',
            'assigned_stages' => 'sometimes|array',
            'assigned_stages.*' => 'string|exists:stages,name',
        ]);

        $user = User::findOrFail($data['user_id']);
        if (!$user->is_active) {
            return response()->json([
                'message' => 'Нельзя назначить неактивного пользователя',
            ], 422);
        }
        // Получаем все доступные роли из базы данных
        $availableRoles = \App\Models\Role::pluck('name')->toArray();

        // Если ролей нет, используем базовые роли
        if (empty($availableRoles)) {
            $availableRoles = ['designer', 'print_operator', 'workshop_worker', 'engraving_operator'];
        }

        if (!$user->hasAnyRole($availableRoles)) {
            return response()->json([
                'message' => 'User must have one of the following roles: ' . implode(', ', $availableRoles),
            ], 422);
        }
        $product = $order->product;
        $roleType = $data['role_type'] ?? $user->roles()->whereIn('name', $availableRoles)->first()?->name;
        $assignment = OrderAssignment::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'assigned_by' => Auth::user()->id,
            'role_type' => $roleType,
        ]);

        // Handle stage assignments with new system
        if ($product) {
            $productStages = $product->getAvailableStages();
            foreach ($productStages as $stage) {
                $assignment->assignToStage($stage->name);
            }
        }

        $user->notify(new OrderAssigned($order, Auth::user()));
        return response()->json([
            'message' => 'User assigned successfully',
            'assignment' => $assignment,
        ]);
    }

    public function bulkAssign(Request $request, Order $order)
    {
        if (Gate::denies('assign', OrderAssignment::class)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'assignments' => 'required|array|min:1',
            'assignments.*.user_id' => 'required|exists:users,id',
            'assignments.*.role_type' => 'sometimes|string',
            'assignments.*.assigned_stages' => 'sometimes|array',
            'assignments.*.assigned_stages.*' => 'integer|exists:stages,id',
        ]);

        $createdAssignments = [];
        $errors = [];

        // Получаем все доступные роли из базы данных
        $availableRoles = \App\Models\Role::pluck('name')->toArray();

        // Если ролей нет, используем базовые роли
        if (empty($availableRoles)) {
            $availableRoles = ['designer', 'print_operator', 'workshop_worker', 'engraving_operator'];
        }

        foreach ($data['assignments'] as $index => $assignmentData) {
            try {
                $user = User::findOrFail($assignmentData['user_id']);

                // Проверка активности пользователя
                if (!$user->is_active) {
                    $errors[] = "Строка {$index}: Пользователь неактивен";
                    continue;
                }

                // Проверка ролей пользователя
                if (!$user->roles()->whereIn('name', $availableRoles)->exists()) {
                    $errors[] = "Строка {$index}: Пользователь не имеет нужной роли";
                    continue;
                }

                // Определение роли
                $roleType = $assignmentData['role_type'] ?? $user->roles()->whereIn('name', $availableRoles)->first()?->name;

                // Проверка существующего назначения
                $existingAssignment = OrderAssignment::where('order_id', $order->id)
                    ->where('user_id', $user->id)
                    ->where('role_type', $roleType)
                    ->first();

                if ($existingAssignment) {
                    $errors[] = "Строка {$index}: Пользователь уже назначен на этот заказ с этой ролью";
                    continue;
                }

                // Создание назначения
                $created = OrderAssignment::create([
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'assigned_by' => Auth::user()->id,
                    'role_type' => $roleType,
                ]);

                // Назначение на стадии
                if (isset($assignmentData['assigned_stages']) && !empty($assignmentData['assigned_stages'])) {
                    foreach ($assignmentData['assigned_stages'] as $stageId) {
                        $created->assignToStage($stageId);
                    }
                } else {
                    // Автоназначение на стадии по роли и текущей стадии заказа
                    $currentStage = Stage::where('name', $order->current_stage)->first();
                    if ($currentStage) {
                        $roleStages = $currentStage->roles()->where('name', $roleType)->where('stage_roles.auto_assign', true)->get();
                        foreach ($roleStages as $stage) {
                            $created->assignToStage($stage->id);
                        }
                    }
                }

                // Отправка уведомления
                $user->notify(new OrderAssigned($order, Auth::user()));
                $createdAssignments[] = $created;
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

    /**
     * Массовое назначение пользователей на множество заказов
     */
    public function bulkAssignGlobal(Request $request)
    {
        if (Gate::denies('assign', OrderAssignment::class)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'assignments' => 'required|array|min:1',
            'assignments.*.order_id' => 'required|exists:orders,id',
            'assignments.*.user_id' => 'required|exists:users,id',
            'assignments.*.role_type' => 'required|string',
            'assignments.*.assigned_stages' => 'sometimes|array',
            'assignments.*.assigned_stages.*' => 'integer|exists:stages,id',
        ]);

        $createdAssignments = [];
        $errors = [];
        $allowedRoles = ['designer', 'print_operator', 'workshop_worker', 'engraving_operator'];

        foreach ($data['assignments'] as $index => $assignmentData) {
            try {
                $order = Order::findOrFail($assignmentData['order_id']);
                $user = User::findOrFail($assignmentData['user_id']);

                // Проверки
                if (!$user->is_active) {
                    $errors[] = "Строка {$index}: Пользователь неактивен";
                    continue;
                }

                if (!$user->roles()->whereIn('name', $allowedRoles)->exists()) {
                    $errors[] = "Строка {$index}: Пользователь не имеет нужной роли";
                    continue;
                }

                // Проверка существующего назначения
                $existingAssignment = OrderAssignment::where('order_id', $order->id)
                    ->where('user_id', $user->id)
                    ->where('role_type', $assignmentData['role_type'])
                    ->first();

                if ($existingAssignment) {
                    $errors[] = "Строка {$index}: Пользователь уже назначен на заказ {$order->id}";
                    continue;
                }

                // Создание назначения
                $created = OrderAssignment::create([
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'assigned_by' => Auth::user()->id,
                    'role_type' => $assignmentData['role_type'],
                ]);

                // Назначение на стадии
                if (isset($assignmentData['assigned_stages'])) {
                    foreach ($assignmentData['assigned_stages'] as $stageId) {
                        $created->assignToStage($stageId);
                    }
                }

                $createdAssignments[] = $created;
            } catch (\Exception $e) {
                $errors[] = "Строка {$index}: " . $e->getMessage();
            }
        }

        return response()->json([
            'message' => 'Массовое назначение завершено',
            'created_assignments' => $createdAssignments,
            'total_created' => count($createdAssignments),
            'errors' => $errors
        ], !empty($errors) ? 207 : 201);
    }

    /**
     * Массовое переназначение пользователей
     */
    public function bulkReassign(Request $request)
    {
        if (Gate::denies('assign', OrderAssignment::class)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'reassignments' => 'required|array|min:1',
            'reassignments.*.assignment_id' => 'required|exists:order_assignments,id',
            'reassignments.*.new_user_id' => 'required|exists:users,id',
            'reassignments.*.reason' => 'sometimes|string|max:255',
        ]);

        $updatedAssignments = [];
        $errors = [];

        foreach ($data['reassignments'] as $index => $reassignmentData) {
            try {
                $assignment = OrderAssignment::findOrFail($reassignmentData['assignment_id']);
                $newUser = User::findOrFail($reassignmentData['new_user_id']);

                if (!$newUser->is_active) {
                    $errors[] = "Строка {$index}: Новый пользователь неактивен";
                    continue;
                }

                $oldUser = $assignment->user;
                $assignment->user_id = $newUser->id;
                $assignment->save();

                // Логирование переназначения
                Log::info('Массовое переназначение', [
                    'assignment_id' => $assignment->id,
                    'old_user_id' => $oldUser->id,
                    'new_user_id' => $newUser->id,
                    'reason' => $reassignmentData['reason'] ?? 'Не указана',
                    'reassigned_by' => Auth::user()->id
                ]);

                $updatedAssignments[] = $assignment;
            } catch (\Exception $e) {
                $errors[] = "Строка {$index}: " . $e->getMessage();
            }
        }

        return response()->json([
            'message' => 'Массовое переназначение завершено',
            'updated_assignments' => $updatedAssignments,
            'total_updated' => count($updatedAssignments),
            'errors' => $errors
        ], !empty($errors) ? 207 : 200);
    }

    /**
     * Массовое обновление стадий у назначений
     */
    public function bulkUpdate(Request $request)
    {
        if (Gate::denies('assign', OrderAssignment::class)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'updates' => 'required|array|min:1',
            'updates.*.assignment_id' => 'required|exists:order_assignments,id',
            'updates.*.assigned_stages' => 'required|array',
            'updates.*.assigned_stages.*' => 'integer|exists:stages,id',
        ]);

        $updatedAssignments = [];
        $errors = [];

        foreach ($data['updates'] as $index => $updateData) {
            try {
                $assignment = OrderAssignment::findOrFail($updateData['assignment_id']);

                // Удаляем старые связи со стадиями
                $assignment->orderStageAssignments()->delete();

                // Добавляем новые стадии
                foreach ($updateData['assigned_stages'] as $stageId) {
                    $assignment->assignToStage($stageId);
                }

                $updatedAssignments[] = $assignment;
            } catch (\Exception $e) {
                $errors[] = "Строка {$index}: " . $e->getMessage();
            }
        }

        return response()->json([
            'message' => 'Массовое обновление завершено',
            'updated_assignments' => $updatedAssignments,
            'total_updated' => count($updatedAssignments),
            'errors' => $errors
        ], !empty($errors) ? 207 : 200);
    }

    public function updateStatus(Request $request, OrderAssignment $assignment)
    {
        if (Gate::denies('updateStatus', $assignment)) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,in_progress,cancelled,under_review,approved',
            'assigned_stages' => 'sometimes|array',
            'assigned_stages.*' => 'string|exists:stages,name',
        ]);

        $user = Auth::user();
        $newStatus = $request->status;

        if ($user->hasRole('manager') && $newStatus !== 'approved') {
            return response()->json([
                'message' => 'Менеджер может менять статус только на "одобрено"'
            ], 403);
        }

        $oldStatus = $assignment->status;
        $assignment->status = $request->status;
        foreach (['has_design_stage', 'has_print_stage', 'has_engraving_stage', 'has_workshop_stage'] as $stageField) {
            if ($request->has($stageField)) {
                $assignment->$stageField = $request->boolean($stageField);
            }
        }
        $assignment->save();

        if ($oldStatus !== $assignment->status) {
            \Log::info('Статус назначения изменился', [
                'assignment_id' => $assignment->id,
                'old_status' => $oldStatus,
                'new_status' => $assignment->status
            ]);

            $adminsAndManagers = \App\Models\User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'manager']);
            })->get();
            \Log::info('Найдено админов и менеджеров: ' . $adminsAndManagers->count());

            foreach ($adminsAndManagers as $admin) {
                \Log::info('Отправляем уведомление пользователю', [
                    'user_id' => $admin->id,
                    'username' => $admin->username,
                    'role' => $admin->role
                ]);
                $admin->notify(new \App\Notifications\AssignmentStatusChanged($assignment, auth()->user()));
            }
        }

        $response = [
            'message' => 'Статус обновлён',
            'status' => $assignment->status,
            'assignment_id' => $assignment->id
        ];

        if ($oldStatus !== 'approved' && $request->status === 'approved') {
            $order = $assignment->order;

            if ($order->wasChanged('stage')) {
                $response['stage_transition'] = [
                    'from' => $order->getOriginal('stage'),
                    'to' => $order->stage,
                    'message' => 'Заказ автоматически переведен на следующую стадию'
                ];
            }
        }

        return response()->json($response);
    }

    public function updateStages(Request $request, OrderAssignment $assignment)
    {
        if (Gate::denies('update', $assignment)) {
            abort(403, 'Доступ запрещён');
        }
        $data = $request->validate([
            'assigned_stages' => 'sometimes|array',
            'assigned_stages.*' => 'string|exists:stages,name',
        ]);
        $assignment->update([
            'has_design_stage' => $request->input('has_design_stage', $assignment->has_design_stage),
            'has_print_stage' => $request->input('has_print_stage', $assignment->has_print_stage),
            'has_engraving_stage' => $request->input('has_engraving_stage', $assignment->has_engraving_stage),
            'has_workshop_stage' => $request->input('has_workshop_stage', $assignment->has_workshop_stage),
        ]);
        return response()->json([
            'message' => 'Назначение обновлено',
            'assignment' => $assignment,
        ]);
    }

    public function destroy(OrderAssignment $assignment)
    {
        if (Gate::denies('delete', $assignment)) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }
        if ($assignment->status == 'cancelled') {
            $assignedUser = $assignment->user;
            if ($assignedUser) {
                \Log::info('Отправляем уведомление об удалении назначения', [
                    'user_id' => $assignedUser->id,
                    'username' => $assignedUser->username,
                    'assignment_id' => $assignment->id,
                    'order_id' => $assignment->order_id
                ]);
                $assignedUser->notify(new \App\Notifications\AssignmentRemoved($assignment, auth()->user()));
            }

            $assignment->delete();
        } else {
            return response()->json([
                'message' => 'You can\'t delete this assignment',
            ], 422);
        }

        return response()->json(['message' => 'Cancelled assignment deleted successfully']);
    }
}
