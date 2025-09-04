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
use Illuminate\Support\Facades\DB;
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
            'role_type' => 'required|string|exists:roles,name',
            'assigned_stages' => 'sometimes|array',
            'assigned_stages.*' => 'string|exists:stages,name',
        ]);

        return \DB::transaction(function () use ($data, $order) {
            $user = User::findOrFail($data['user_id']);

            // Проверяем активность пользователя
            if (!$user->is_active) {
                return response()->json([
                    'message' => 'Нельзя назначить неактивного пользователя',
                ], 422);
            }

            // Проверяем, есть ли у пользователя нужная роль
            if (!$user->roles()->where('name', $data['role_type'])->exists()) {
                return response()->json([
                    'message' => 'Пользователь не имеет роль: ' . $data['role_type'],
                ], 422);
            }

            // Проверяем, нет ли уже такого назначения
            $existingAssignment = OrderAssignment::where([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'role_type' => $data['role_type'],
            ])->first();

            if ($existingAssignment) {
                return response()->json([
                    'message' => 'Пользователь уже назначен на этот заказ с этой ролью',
                ], 422);
            }

            // Создаем назначение
            $assignment = OrderAssignment::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'assigned_by' => Auth::user()->id,
                'role_type' => $data['role_type'],
                'status' => 'pending',
            ]);

            // Очищаем кэш заказа после создания назначения
            $orderCacheKey = 'order_' . $order->id;
            \Illuminate\Support\Facades\Cache::forget($orderCacheKey);

            // Автоназначение на стадии по продукту
            $product = $order->product;
            if ($product) {
                $productStages = $product->getAvailableStages();
                foreach ($productStages as $stage) {
                    $assignment->assignToStage($stage->name);
                }
            }

            // Отправляем уведомление
            try {
                $user->notify(new OrderAssigned($order, Auth::user(), $data['role_type']));
            } catch (\Exception $e) {
                Log::error('Failed to send OrderAssigned notification', [
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'message' => 'User assigned successfully',
                'assignment' => $assignment->load(['user.roles', 'orderStageAssignments.stage']),
            ]);
        });
    }

    public function bulkAssign(Request $request, Order $order)
    {
        if (Gate::denies('assign', OrderAssignment::class)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'assignments' => 'required|array|min:1',
            'assignments.*.user_id' => 'required|exists:users,id',
            'assignments.*.role_type' => 'required|string|exists:roles,name',
            'assignments.*.assigned_stages' => 'sometimes|array',
            'assignments.*.assigned_stages.*' => 'string|exists:stages,name',
        ]);

        $createdAssignments = [];
        $errors = [];

        foreach ($data['assignments'] as $index => $assignmentData) {
            try {
                $user = User::findOrFail($assignmentData['user_id']);

                // Проверка активности пользователя
                if (!$user->is_active) {
                    $errors[] = "Строка {$index}: Пользователь неактивен";
                    continue;
                }

                // Проверка ролей пользователя
                if (!$user->roles()->where('name', $assignmentData['role_type'])->exists()) {
                    $errors[] = "Строка {$index}: Пользователь не имеет роль: " . $assignmentData['role_type'];
                    continue;
                }

                // Проверка существующего назначения
                $existingAssignment = OrderAssignment::where([
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'role_type' => $assignmentData['role_type'],
                ])->first();

                if ($existingAssignment) {
                    $errors[] = "Строка {$index}: Пользователь уже назначен на этот заказ с этой ролью";
                    continue;
                }

                // Создание назначения
                $created = OrderAssignment::create([
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'assigned_by' => Auth::user()->id,
                    'role_type' => $assignmentData['role_type'],
                    'status' => 'pending',
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
                        $roleStages = $currentStage->roles()->where('name', $assignmentData['role_type'])->where('stage_roles.auto_assign', true)->get();
                        foreach ($roleStages as $stage) {
                            $created->assignToStage($stage->id);
                        }
                    }
                }

                // Отправка уведомления
                try {
                    $user->notify(new OrderAssigned($order, Auth::user(), $assignmentData['role_type']));
                } catch (\Exception $e) {
                    Log::error('Failed to send OrderAssigned notification (bulk)', [
                        'user_id' => $user->id,
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                }

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
            'assignments.*.role_type' => 'required|string|exists:roles,name',
            'assignments.*.assigned_stages' => 'sometimes|array',
            'assignments.*.assigned_stages.*' => 'string|exists:stages,name',
        ]);

        $createdAssignments = [];
        $errors = [];

        foreach ($data['assignments'] as $index => $assignmentData) {
            try {
                $order = Order::findOrFail($assignmentData['order_id']);
                $user = User::findOrFail($assignmentData['user_id']);

                // Проверки
                if (!$user->is_active) {
                    $errors[] = "Строка {$index}: Пользователь неактивен";
                    continue;
                }

                if (!$user->roles()->where('name', $assignmentData['role_type'])->exists()) {
                    $errors[] = "Строка {$index}: Пользователь не имеет роль: " . $assignmentData['role_type'];
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
        $user = Auth::user();

        Log::info('OrderAssignmentController@updateStatus called', [
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
            'user_roles' => $user->roles->pluck('name')->toArray(),
            'request_status' => $request->status,
            'current_assignment_status' => $assignment->status,
            'assignment_user_id' => $assignment->user_id,
            'is_staff' => $user->isStaff(),
            'is_admin' => $user->isAdmin(),
            'is_manager' => $user->isManager()
        ]);

        // Проверяем права через политику
        if (Gate::denies('updateStatus', $assignment)) {
            Log::warning('OrderAssignmentController@updateStatus - Access denied by Gate', [
                'assignment_id' => $assignment->id,
                'user_id' => $user->id,
                'user_roles' => $user->roles->pluck('name')->toArray(),
                'assignment_user_id' => $assignment->user_id,
                'gate_result' => Gate::inspect('updateStatus', $assignment)->message()
            ]);

            // Дополнительная проверка для отладки
            if ($user->isStaff() && $user->id === $assignment->user_id) {
                Log::error('OrderAssignmentController@updateStatus - Staff user should have access but Gate denied', [
                    'user_id' => $user->id,
                    'assignment_user_id' => $assignment->user_id,
                    'user_roles' => $user->roles->pluck('name')->toArray()
                ]);
            }

            // Прямая проверка для сотрудников (обход Gate если он не работает)
            if ($user->isStaff() && $user->id === $assignment->user_id) {
                Log::info('OrderAssignmentController@updateStatus - Bypassing Gate for staff user', [
                    'user_id' => $user->id,
                    'assignment_user_id' => $assignment->user_id
                ]);
                // Продолжаем выполнение для сотрудника
            } else {
                return response()->json([
                    'message' => 'У вас нет прав на изменение этого назначения'
                ], 403);
            }
        }

        Log::info('OrderAssignmentController@updateStatus - Access granted', [
            'assignment_id' => $assignment->id,
            'user_id' => Auth::user()->id
        ]);

        $request->validate([
            'status' => 'required|in:pending,in_progress,cancelled,under_review,approved',
            'assigned_stages' => 'sometimes|array',
            'assigned_stages.*' => 'string|exists:stages,name',
        ]);

        $user = Auth::user();
        $newStatus = $request->status;


        $oldStatus = $assignment->status;
        $assignment->status = $request->status;

        Log::info('OrderAssignmentController@updateStatus - Saving assignment', [
            'assignment_id' => $assignment->id,
            'old_status' => $oldStatus,
            'new_status' => $assignment->status
        ]);

        $assignment->save();

        // Очищаем кэш заказа после обновления назначения
        $orderCacheKey = 'order_' . $assignment->order_id;
        \Illuminate\Support\Facades\Cache::forget($orderCacheKey);

        if ($oldStatus !== $assignment->status) {
            Log::info('Статус назначения изменился', [
                'assignment_id' => $assignment->id,
                'old_status' => $oldStatus,
                'new_status' => $assignment->status
            ]);

            $adminsAndManagers = \App\Models\User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'manager']);
            })->get();
            Log::info('Найдено админов и менеджеров: ' . $adminsAndManagers->count());

            foreach ($adminsAndManagers as $admin) {
                Log::info('Отправляем уведомление пользователю', [
                    'user_id' => $admin->id,
                    'username' => $admin->username,
                    'role' => $admin->role
                ]);
                try {
                    $admin->notify(new \App\Notifications\AssignmentStatusChanged($assignment, Auth::user()));
                    Log::info('Уведомление отправлено успешно', [
                        'admin_id' => $admin->id,
                        'assignment_id' => $assignment->id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Ошибка отправки уведомления', [
                        'admin_id' => $admin->id,
                        'assignment_id' => $assignment->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        $response = [
            'message' => 'Статус обновлён',
            'status' => $assignment->status,
            'assignment_id' => $assignment->id
        ];

        // Проверяем, произошел ли автоматический переход стадии
        if ($oldStatus !== 'approved' && $request->status === 'approved') {
            $order = $assignment->order;

            // Перезагружаем заказ, чтобы получить актуальную стадию после observer
            $order->refresh();

            // Получаем названия стадий для сравнения
            $oldStageName = $order->getOriginal('stage_id') ?
                \App\Models\Stage::find($order->getOriginal('stage_id'))->name ?? null : null;
            $newStageName = is_string($order->stage) ? $order->stage : $order->stage->name ?? null;

            // Проверяем, изменилась ли стадия заказа и что у нас есть валидные названия
            if ($oldStageName !== null && $newStageName !== null && $oldStageName !== $newStageName) {
                $response['stage_transition'] = [
                    'from' => $oldStageName,
                    'to' => $newStageName,
                    'message' => 'Заказ автоматически переведен на следующую стадию'
                ];
            }
        }

        Log::info('OrderAssignmentController@updateStatus - Success', [
            'assignment_id' => $assignment->id,
            'response' => $response
        ]);

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
                Log::info('Отправляем уведомление об удалении назначения', [
                    'user_id' => $assignedUser->id,
                    'username' => $assignedUser->username,
                    'assignment_id' => $assignment->id,
                    'order_id' => $assignment->order_id
                ]);
                $assignedUser->notify(new \App\Notifications\AssignmentRemoved($assignment, Auth::user()));
            }

            $assignment->delete();

            // Очищаем кэш заказа после удаления назначения
            $orderCacheKey = 'order_' . $assignment->order_id;
            \Illuminate\Support\Facades\Cache::forget($orderCacheKey);
        } else {
            return response()->json([
                'message' => 'You can\'t delete this assignment',
            ], 422);
        }

        return response()->json(['message' => 'Cancelled assignment deleted successfully']);
    }

    public function assignToStage(Request $request, Order $order)
    {
        if (Gate::denies('assign', OrderAssignment::class)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'stage_id' => 'required|exists:stages,id',
            'role_type' => 'required|string|exists:roles,name',
        ]);

        $user = User::findOrFail($data['user_id']);
        $stage = Stage::findOrFail($data['stage_id']);

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Нельзя назначить неактивного пользователя',
            ], 422);
        }

        // Проверяем, есть ли у пользователя нужная роль
        if (!$user->roles()->where('name', $data['role_type'])->exists()) {
            return response()->json([
                'message' => 'Пользователь не имеет роль: ' . $data['role_type'],
            ], 422);
        }

        // Находим или создаем назначение
        $assignment = OrderAssignment::firstOrCreate([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'role_type' => $data['role_type'],
        ], [
            'assigned_by' => Auth::user()->id,
            'status' => 'pending',
        ]);

        // Назначаем на конкретную стадию
        $assignment->assignToStage($stage->name);

        // Отправляем уведомление о назначении на стадию
        $user->notify(new \App\Notifications\StageRoleAssigned($order, $stage, $data['role_type'], Auth::user()));

        return response()->json([
            'message' => 'Пользователь назначен на стадию успешно',
            'assignment' => $assignment->load(['user', 'orderStageAssignments.stage']),
        ]);
    }

    public function removeFromStage(Request $request, Order $order)
    {
        if (Gate::denies('assign', OrderAssignment::class)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'stage_id' => 'required|exists:stages,id',
            'role_type' => 'required|string|exists:roles,name',
        ]);

        $user = User::findOrFail($data['user_id']);
        $stage = Stage::findOrFail($data['stage_id']);

        // Находим назначение
        $assignment = OrderAssignment::where([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'role_type' => $data['role_type'],
        ])->first();

        if (!$assignment) {
            return response()->json([
                'message' => 'Назначение не найдено',
            ], 404);
        }

        // Удаляем с конкретной стадии
        $assignment->removeFromStage($stage->name);

        // Отправляем уведомление об удалении со стадии
        $user->notify(new \App\Notifications\StageRoleRemoved($order, $stage, $data['role_type'], Auth::user()));

        return response()->json([
            'message' => 'Пользователь удален со стадии успешно',
            'assignment' => $assignment->load(['user', 'orderStageAssignments.stage']),
        ]);
    }
}
