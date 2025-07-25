<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Notifications\OrderAssigned;

class OrderAssignmentController extends Controller
{
    public function index(Request $request)
    {
        if (Gate::denies('viewAny', OrderAssignment::class)) {
            abort(403, 'Доступ запрещён');
        }

        $user = auth()->user();
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
            'has_design_stage' => 'sometimes|boolean',
            'has_print_stage' => 'sometimes|boolean',
            'has_engraving_stage' => 'sometimes|boolean',
            'has_workshop_stage' => 'sometimes|boolean',
        ]);

        $user = User::findOrFail($data['user_id']);
        if (!$user->is_active) {
            return response()->json([
                'message' => 'Нельзя назначить неактивного пользователя',
            ], 422);
        }
        $allowedRoles = ['designer', 'print_operator', 'workshop_worker', 'engraving_operator'];
        if (!$user->hasAnyRole($allowedRoles)) {
            return response()->json([
                'message' => 'User must have one of the following roles: ' . implode(', ', $allowedRoles),
            ], 422);
        }
        $product = $order->product;
        // Определяем роль, если не передана явно
        $roleType = $data['role_type'] ?? $user->roles()->whereIn('name', $allowedRoles)->first()?->name;
        $assignment = OrderAssignment::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'assigned_by' => auth()->user()->id,
            'role_type' => $roleType,
            'has_design_stage' => $request->has('has_design_stage') ? $request->boolean('has_design_stage') : ($order->has_design_stage ?? $product->has_design_stage),
            'has_print_stage' => $request->has('has_print_stage') ? $request->boolean('has_print_stage') : ($order->has_print_stage ?? $product->has_print_stage),
            'has_engraving_stage' => $request->has('has_engraving_stage') ? $request->boolean('has_engraving_stage') : ($order->has_engraving_stage ?? $product->has_engraving_stage),
            'has_workshop_stage' => $request->has('has_workshop_stage') ? $request->boolean('has_workshop_stage') : ($order->has_workshop_stage ?? $product->has_workshop_stage),
        ]);
        $user->notify(new OrderAssigned($order, auth()->user()));
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
            'assignments.*.has_design_stage' => 'sometimes|boolean',
            'assignments.*.has_print_stage' => 'sometimes|boolean',
            'assignments.*.has_engraving_stage' => 'sometimes|boolean',
            'assignments.*.has_workshop_stage' => 'sometimes|boolean',
        ]);

        $product = $order->product;
        $createdAssignments = [];
        $errors = [];
        $allowedRoles = ['designer', 'print_operator', 'workshop_worker', 'engraving_operator'];
        foreach ($data['assignments'] as $index => $assignmentData) {
            try {
                $user = User::findOrFail($assignmentData['user_id']);
                if (!$user->is_active) {
                    $errors[] = "Строка {$index}: Пользователь неактивен";
                    continue;
                }
                if (!$user->hasAnyRole($allowedRoles)) {
                    $errors[] = "Строка {$index}: Пользователь не имеет нужной роли";
                    continue;
                }
                $existingAssignment = OrderAssignment::where('order_id', $order->id)
                    ->where('user_id', $user->id)
                    ->where('role_type', $assignmentData['role_type'] ?? $user->roles()->whereIn('name', $allowedRoles)->first()?->name)
                    ->first();
                if ($existingAssignment) {
                    $errors[] = "Строка {$index}: Пользователь уже назначен на этот заказ с этой ролью";
                    continue;
                }
                // --- Автоподстановка чекбокса по роли, если явно не передано ни одного чекбокса ---
                $stageFields = [
                    'designer' => 'has_design_stage',
                    'print_operator' => 'has_print_stage',
                    'engraving_operator' => 'has_engraving_stage',
                    'workshop_worker' => 'has_workshop_stage',
                ];
                $hasAnyStage = array_key_exists('has_design_stage', $assignmentData)
                    || array_key_exists('has_print_stage', $assignmentData)
                    || array_key_exists('has_engraving_stage', $assignmentData)
                    || array_key_exists('has_workshop_stage', $assignmentData);
                $roleType = $assignmentData['role_type'] ?? $user->roles()->whereIn('name', $allowedRoles)->first()?->name;
                if (!$hasAnyStage && $roleType) {
                    foreach ($stageFields as $role => $stageField) {
                        $assignmentData[$stageField] = ($role === $roleType);
                    }
                }
                $created = OrderAssignment::create([
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'assigned_by' => auth()->user()->id,
                    'role_type' => $roleType,
                    'has_design_stage' => array_key_exists('has_design_stage', $assignmentData) ? (bool)$assignmentData['has_design_stage'] : ($order->has_design_stage ?? $product->has_design_stage),
                    'has_print_stage' => array_key_exists('has_print_stage', $assignmentData) ? (bool)$assignmentData['has_print_stage'] : ($order->has_print_stage ?? $product->has_print_stage),
                    'has_engraving_stage' => array_key_exists('has_engraving_stage', $assignmentData) ? (bool)$assignmentData['has_engraving_stage'] : ($order->has_engraving_stage ?? $product->has_engraving_stage),
                    'has_workshop_stage' => array_key_exists('has_workshop_stage', $assignmentData) ? (bool)$assignmentData['has_workshop_stage'] : ($order->has_workshop_stage ?? $product->has_workshop_stage),
                ]);
                $user->notify(new OrderAssigned($order, auth()->user()));
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

    public function updateStatus(Request $request, OrderAssignment $assignment)
    {
        if (Gate::denies('updateStatus', $assignment)) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,in_progress,cancelled,under_review,approved',
            'has_design_stage' => 'sometimes|boolean',
            'has_print_stage' => 'sometimes|boolean',
            'has_engraving_stage' => 'sometimes|boolean',
            'has_workshop_stage' => 'sometimes|boolean',
        ]);

        $user = auth()->user();
        $newStatus = $request->status;

        // Проверка: менеджер может менять статус только на "approved"
        if ($user->hasRole('manager') && $newStatus !== 'approved') {
            return response()->json([
                'message' => 'Менеджер может менять статус только на "одобрено"'
            ], 403);
        }

        $oldStatus = $assignment->status;
        $assignment->status = $request->status;
        // Обновляем чекбоксы стадий, если они переданы
        foreach (['has_design_stage', 'has_print_stage', 'has_engraving_stage', 'has_workshop_stage'] as $stageField) {
            if ($request->has($stageField)) {
                $assignment->$stageField = $request->boolean($stageField);
            }
        }
        $assignment->save();

        // Уведомление для админа и менеджера
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

            // Уведомление для назначенного пользователя при отмене назначения
            // if ($request->status === 'cancelled') {
            //     $assignedUser = $assignment->user;
            //     if ($assignedUser) {
            //         \Log::info('Отправляем уведомление об отмене назначения', [
            //             'user_id' => $assignedUser->id,
            //             'username' => $assignedUser->username,
            //             'assignment_id' => $assignment->id,
            //             'order_id' => $assignment->order_id
            //         ]);
            //         $assignedUser->notify(new \App\Notifications\AssignmentRemoved($assignment, auth()->user()));
            //     }
            // }
        }

        $response = [
            'message' => 'Статус обновлён',
            'status' => $assignment->status,
            'assignment_id' => $assignment->id
        ];

        // Если статус изменился на "approved", проверяем переход стадии
        if ($oldStatus !== 'approved' && $request->status === 'approved') {
            $order = $assignment->order;

            // Проверяем, произошел ли переход стадии
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
            'has_design_stage' => 'sometimes|boolean',
            'has_print_stage' => 'sometimes|boolean',
            'has_engraving_stage' => 'sometimes|boolean',
            'has_workshop_stage' => 'sometimes|boolean',
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
            // Отправляем уведомление назначенному пользователю перед удалением
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
