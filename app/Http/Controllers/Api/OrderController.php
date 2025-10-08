<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\OrderStatusLog;
use App\Repositories\OrderRepository;
use App\DTOs\OrderDTO;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class OrderController extends Controller
{
    protected OrderRepository $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function index(Request $request)
    {
        if (Gate::denies('viewAny', Order::class)) {
            abort(403, 'Доступ запрещён');
        }

        $user = request()->user();

        $cacheKey = 'orders_' . $user->id . '_' . md5($request->fullUrl());
        $result = CacheService::rememberWithTags($cacheKey, 900, function () use ($request, $user) {
            return $this->orderRepository->getPaginatedOrders($request, $user);
        }, [CacheService::TAG_ORDERS]);

        return response()->json($result);
    }

    public function show(Order $order)
    {
        // Убрано подробное отладочное логирование запроса

        $user = request()->user();

        if (Gate::denies('view', $order)) {
            abort(403, 'Доступ запрещён');
        }

        try {
            $orderDTO = $this->orderRepository->getOrderById($order->id);

            if (!$orderDTO) {
                abort(404, 'Заказ не найден');
            }

            return response()->json($orderDTO->toArray());
        } catch (\Exception $e) {
            Log::error('Error in OrderController::show', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function store(Request $request)
    {
        if (Gate::denies('create', Order::class)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'project_title' => 'nullable|string|max:255',
            'product_id' => 'sometimes|exists:products,id',
            'quantity' => 'sometimes|integer|min:1',
            'deadline' => 'nullable|date',
            'price' => 'nullable|numeric',
            'assignments' => 'sometimes|array',
            'assignments.*.user_id' => 'required|exists:users,id',
            'assignments.*.role_type' => 'required|string',
            'assignments.*.stage_id' => 'sometimes|exists:stages,id',
            'stages' => 'sometimes|array',
            'stages.*' => 'exists:stages,id',
            'is_bulk' => 'sometimes|boolean',
        ]);

        $product = null;
        if (isset($data['product_id'])) {
            $product = \App\Models\Product::find($data['product_id']);
        }
        // Get the first available stage for this product
        $availableStages = $product ? $product->getAvailableStages() : \App\Models\Stage::ordered()->get();

        // Определяем начальную стадию на основе назначений
        $initialStage = null;

        // Если есть назначения в запросе, определяем стадию по ним
        if (isset($data['assignments']) && is_array($data['assignments']) && !empty($data['assignments'])) {
            // Получаем все стадии в правильном порядке
            $orderedStages = $availableStages->sortBy('order');

            // Предзагружаем все стадии по ID для избежания N+1
            $assignmentStageIds = collect($data['assignments'])
                ->pluck('stage_id')
                ->filter()
                ->unique()
                ->values();
            $assignmentStagesById = \App\Models\Stage::whereIn('id', $assignmentStageIds)->get()->keyBy('id');

            // Ищем первую стадию с назначениями
            foreach ($orderedStages as $stage) {
                // Пропускаем служебные стадии
                if (in_array($stage->name, ['draft', 'completed', 'cancelled'])) {
                    continue;
                }

                // Проверяем, есть ли назначения для этой стадии
                $hasAssignmentsForStage = false;
                foreach ($data['assignments'] as $assignment) {
                    if (isset($assignment['stage_id'])) {
                        $assignmentStage = $assignmentStagesById->get($assignment['stage_id']);
                        if ($assignmentStage && $assignmentStage->name === $stage->name) {
                            $hasAssignmentsForStage = true;
                            break;
                        }
                    }
                }

                if ($hasAssignmentsForStage) {
                    $initialStage = $stage;
                    break;
                }
            }
        }

        // Если не нашли стадию с назначениями, используем историческую логику
        if (!$initialStage) {
            // Берем первую стадию с order_stage_assignments по порядку
            $stagesWithAssignments = DB::table('order_stage_assignments')
                ->join('order_assignments', 'order_stage_assignments.order_assignment_id', '=', 'order_assignments.id')
                ->join('orders', 'order_assignments.order_id', '=', 'orders.id')
                ->join('stages', 'order_stage_assignments.stage_id', '=', 'stages.id')
                ->where('orders.product_id', $data['product_id'])
                ->where('order_stage_assignments.is_assigned', true)
                ->select('stages.*')
                ->distinct()
                ->orderBy('stages.order')
                ->get();



            $initialStage = $stagesWithAssignments->first();
        }

        // Если нет стадий с назначениями, берем первую рабочую стадию
        if (!$initialStage) {
            $initialStage = $availableStages->filter(function ($stage) {
                return $stage && $stage->roles && $stage->roles->count() > 0;
            })->first();
        }

        // Если все еще нет стадии, берем первую стадию после draft
        if (!$initialStage) {
            $initialStage = $availableStages->filter(function ($stage) {
                return $stage && $stage->name !== 'draft' && $stage->name !== 'completed' && $stage->name !== 'cancelled';
            })->first();
        }

        $data['stage'] = $initialStage ? $initialStage->name : 'draft';



        // Преобразуем название стадии в stage_id
        if (isset($data['stage'])) {
            $stage = \App\Models\Stage::where('name', $data['stage'])->first();
            if ($stage) {
                $data['stage_id'] = $stage->id;
            } else {
                Log::error('Stage not found', ['stage_name' => $data['stage']]);
            }
        }

        // Проверяем, что для массового заказа указан проект
        if (isset($data['is_bulk']) && $data['is_bulk']) {
            if (!isset($data['project_id']) && !isset($data['project_title'])) {
                return response()->json([
                    'message' => 'Проект обязателен для массового заказа'
                ], 422);
            }

            // Если указано название проекта, создаем новый проект
            if (isset($data['project_title']) && !isset($data['project_id'])) {
                $project = \App\Models\Project::create([
                    'title' => $data['project_title'],
                    'client_id' => $data['client_id'],
                ]);
                $data['project_id'] = $project->id;
            }
        }

        $order = Order::create($data);

        // Очищаем кэш заказов после создания
        $this->clearOrdersCache();

        // Обрабатываем назначения, если они есть
        if (isset($data['assignments']) && is_array($data['assignments'])) {
            // Получаем список выбранных стадий
            $selectedStages = isset($data['stages']) ? $data['stages'] : [];

            // Предзагружаем пользователей и стадии для избежания N+1
            $userIds = collect($data['assignments'])->pluck('user_id')->unique()->values();
            $stageIds = collect($data['assignments'])->pluck('stage_id')->filter()->unique()->values();
            
            $usersById = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');
            $stagesById = \App\Models\Stage::whereIn('id', $stageIds)->get()->keyBy('id');

            foreach ($data['assignments'] as $assignmentData) {
                // Проверяем, что назначение создается только для выбранных стадий
                if (isset($assignmentData['stage_id']) && !empty($selectedStages)) {
                    if (!in_array($assignmentData['stage_id'], $selectedStages)) {
                        // Пропускаем назначения для невыбранных стадий
                        continue; // Пропускаем назначения для невыбранных стадий
                    }
                }

                $assignment = \App\Models\OrderAssignment::create([
                    'order_id' => $order->id,
                    'user_id' => $assignmentData['user_id'],
                    'role_type' => $assignmentData['role_type'],
                    'assigned_by' => $request->user()->id,
                    'status' => 'pending',
                ]);

                // Если указан stage_id, назначаем на конкретную стадию
                if (isset($assignmentData['stage_id'])) {
                    $stage = $stagesById->get($assignmentData['stage_id']);
                    if ($stage) {
                        $assignment->assignToStage($stage->name);
                    }
                }

                // Отправляем уведомление назначенному пользователю
                $assignedUser = $usersById->get($assignmentData['user_id']);
                if ($assignedUser) {
                    try {
                        $assignedUser->notify(new \App\Notifications\OrderAssigned(
                            $order,
                            $request->user(),
                            $assignmentData['role_type'],
                            $order->stage ? $order->stage->name : null
                        ));
                        // Уведомление отправлено успешно
                    } catch (\Exception $e) {
                        \Log::error('Failed to send OrderAssigned notification', [
                            'user_id' => $assignedUser->id,
                            'order_id' => $order->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        // Используем with() вместо load() для предотвращения N+1 проблемы
        $order = Order::with('assignments.user')->find($order->id);
        return response()->json($order, 201);
    }

    public function update(Request $request, Order $order)
    {
        if (Gate::denies('update', $order)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'deadline' => ['nullable', 'date'],
            'price' => 'nullable|numeric|min:0',
            'project_title' => 'nullable|string|max:255',
            'stage' => 'sometimes|string',
            'reason' => 'sometimes|string',
            'reason_status' => 'sometimes|string',
            'assignments' => 'sometimes|array',
            'assignments.*.user_id' => 'required|exists:users,id',
            'assignments.*.role_type' => 'required|string',
            'assignments.*.stage_id' => 'sometimes|exists:stages,id',
            'stages' => 'sometimes|array',
            'stages.*' => 'exists:stages,id',
            'is_bulk' => 'sometimes|boolean',
        ]);

        // Проверяем, что для массового заказа указан проект
        if (isset($data['is_bulk']) && $data['is_bulk']) {
            if (!isset($data['project_id']) && !isset($data['project_title'])) {
                return response()->json([
                    'message' => 'Проект обязателен для массового заказа'
                ], 422);
            }

            // Если указано название проекта, создаем новый проект
            if (isset($data['project_title']) && !isset($data['project_id'])) {
                $project = \App\Models\Project::create([
                    'title' => $data['project_title'],
                    'client_id' => $data['client_id'],
                ]);
                $data['project_id'] = $project->id;
            }
        }

        // Обрабатываем смену стадии на cancelled
        if (isset($data['stage']) && $data['stage'] === 'cancelled') {
            // Если reason и reason_status не переданы, используем дефолтные значения
            $data['reason'] = $request->input('reason', 'Отменено через kanban');
            $data['reason_status'] = $request->input('reason_status', 'refused');
            $data['is_archived'] = true;
            $data['archived_at'] = now();

            // Находим стадию по имени
            $stage = \App\Models\Stage::where('name', 'cancelled')->first();
            if ($stage) {
                $data['stage_id'] = $stage->id;
            }
        } elseif (isset($data['stage']) && in_array($data['stage'], ['completed', 'cancelled'])) {
            // Для других завершающих стадий
            $data['is_archived'] = true;
            $data['archived_at'] = now();

            // Находим стадию по имени
            $stage = \App\Models\Stage::where('name', $data['stage'])->first();
            if ($stage) {
                $data['stage_id'] = $stage->id;
            }
        } elseif (isset($data['stage'])) {
            // Для обычных стадий
            $data['is_archived'] = false;
            $data['archived_at'] = null;
            $data['reason'] = null;
            $data['reason_status'] = null;

            // Находим стадию по имени
            $stage = \App\Models\Stage::where('name', $data['stage'])->first();
            if ($stage) {
                $data['stage_id'] = $stage->id;
            }
        }

        $order->update($data);

        // Очищаем кэш заказов после обновления
        $this->clearOrdersCache();

        // Обрабатываем назначения, если они есть
        if (isset($data['assignments']) && is_array($data['assignments'])) {
            // Удаляем существующие назначения
            $order->assignments()->delete();

            // Получаем список выбранных стадий
            $selectedStages = isset($data['stages']) ? $data['stages'] : [];

            // Предзагружаем пользователей и стадии для избежания N+1
            $userIds = collect($data['assignments'])->pluck('user_id')->unique()->values();
            $stageIds = collect($data['assignments'])->pluck('stage_id')->filter()->unique()->values();
            
            $usersById = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');
            $stagesById = \App\Models\Stage::whereIn('id', $stageIds)->get()->keyBy('id');

            // Создаем новые назначения
            foreach ($data['assignments'] as $assignmentData) {
                // Проверяем, что назначение создается только для выбранных стадий
                if (isset($assignmentData['stage_id']) && !empty($selectedStages)) {
                    if (!in_array($assignmentData['stage_id'], $selectedStages)) {
                        // Пропуск назначения для невыбранной стадии
                        continue; // Пропускаем назначения для невыбранных стадий
                    }
                }

                $assignment = \App\Models\OrderAssignment::create([
                    'order_id' => $order->id,
                    'user_id' => $assignmentData['user_id'],
                    'role_type' => $assignmentData['role_type'],
                    'assigned_by' => $request->user()->id,
                    'status' => 'pending',
                ]);

                // Если указан stage_id, назначаем на конкретную стадию
                if (isset($assignmentData['stage_id'])) {
                    $stage = $stagesById->get($assignmentData['stage_id']);
                    if ($stage) {
                        $assignment->assignToStage($stage->name);
                    }
                }

                // Отправляем уведомление назначенному пользователю
                $assignedUser = $usersById->get($assignmentData['user_id']);
                if ($assignedUser) {
                    try {
                        $assignedUser->notify(new \App\Notifications\OrderAssigned(
                            $order,
                            $request->user(),
                            $assignmentData['role_type'],
                            $order->stage ? $order->stage->name : null
                        ));
                        // Уведомление отправлено успешно
                    } catch (\Exception $e) {
                        \Log::error('Failed to send OrderAssigned notification', [
                            'user_id' => $assignedUser->id,
                            'order_id' => $order->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        // Используем with() вместо load() для предотвращения N+1 проблемы
        $order = Order::with('assignments.user')->find($order->id);
        return response()->json($order);
    }

    public function updateStage(Request $request, Order $order)
    {
        if (Gate::denies('changeOrderStatus', $order)) {
            abort(403, 'Доступ запрещён');
        }

        $request->validate([
            'stage' => 'required|string',
        ]);

        $oldStage = $order->stage;
        $newStageName = $request->stage;

        // Проверяем, можно ли перевести на completed
        if ($newStageName === 'completed') {
            // Проверяем, есть ли неодобренные назначения
            $pendingAssignments = $order->assignments()
                ->where('status', '!=', 'approved')
                ->get();

            if ($pendingAssignments->isNotEmpty()) {
                return response()->json([
                    'message' => 'Нельзя завершить заказ, пока есть неодобренные назначения',
                    'pending_assignments_count' => $pendingAssignments->count()
                ], 422);
            }
        }


        $product = $order->product;

        // Находим стадию по имени
        $stage = \App\Models\Stage::where('name', $newStageName)->first();
        if (!$stage) {
            abort(422, 'Стадия не найдена');
        }

        if ($newStageName == 'cancelled') {
            // Если reason и reason_status не переданы, используем дефолтные значения
            $order->reason = $request->input('reason', 'Отменено через kanban');
            $order->reason_status = $request->input('reason_status', 'refused');
        } else {
            $order->reason = null;
            $order->reason_status = null;
        }

        if ($newStageName === 'completed' || $newStageName === 'cancelled') {
            $order->is_archived = true;
            $order->archived_at = now();
        } else {
            $order->is_archived = false;
            $order->archived_at = null;
        }

        $order->stage_id = $stage->id;
        $order->save();
        $order->refresh();

        // Очищаем кэш заказов для всех пользователей после обновления стадии
        $this->clearOrdersCache();

        // Создаем запись в логе изменений статуса
        \App\Models\OrderStatusLog::create([
            'order_id' => $order->id,
            'from_status' => $oldStage ? $oldStage->name : null,
            'to_status' => $order->stage->name,
            'user_id' => $request->user()->id,
            'changed_at' => now(),
        ]);

        // Отправляем уведомления администраторам и менеджерам о переходе на новую стадию
        $adminsAndManagers = \App\Models\User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'manager']);
        })->get();

        foreach ($adminsAndManagers as $admin) {
            $admin->notify(new \App\Notifications\OrderStageChanged($order, $oldStage, $order->stage, $request->user()));
        }

        // Отправляем уведомления пользователям, назначенным на эту стадию
        $stageAssignments = $order->assignments()
            ->whereHas('orderStageAssignments', function ($q) use ($stage) {
                $q->where('stage_id', $stage->id);
            })
            ->with(['user', 'orderStageAssignments'])
            ->get();

        foreach ($stageAssignments as $assignment) {
            $assignedUser = $assignment->user;
            if ($assignedUser && $assignedUser->id !== $request->user()->id) {
                try {
                    $notification = new \App\Notifications\OrderStageChanged(
                        $order,
                        $oldStage,
                        $order->stage,
                        $request->user(),
                        $assignment->role_type
                    );

                    $assignedUser->notify($notification);
                } catch (\Exception $e) {
                    \Log::error('Failed to send stage change notification to assigned user', [
                        'user_id' => $assignedUser->id,
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        }

        // Используем with() вместо load() для предотвращения N+1 проблемы
        $order = Order::with(['project', 'product', 'client', 'assignments.user', 'stage'])->find($order->id);
        return response()->json([
            'message' => 'Статус обновлён',
            'stage' => $order->stage->name,
            'order_id' => $order->id,
            'order' => $order
        ]);
    }

    public function destroy(Order $order)
    {
        if (Gate::denies('delete', $order)) {
            abort(403, 'Доступ запрещён');
        }

        $order->delete();

        // Очищаем кэш заказов после удаления
        $this->clearOrdersCache();

        return response()->json(['message' => 'Заказ удалён']);
    }

    public function statusLogs(Order $order)
    {
        $user = request()->user();

        // Убрано подробное отладочное логирование проверки доступа

        if (Gate::denies('view', $order)) {
            abort(403, 'Доступ запрещён');
        }

        $logs = $order->statusLogs()->with('user')->orderBy('changed_at', 'desc')->get();

        return response()->json($logs);
    }

    private function assignDefaultUsersToOrder($order, $product, $roleType, $assignedBy)
    {
        $productAssignments = $product->assignments()
            ->where('role_type', $roleType)
            ->where('is_active', true)
            ->with('user')
            ->get();

        $orderAssignments = $order->assignments()
            ->whereHas('user.roles', function ($q) use ($roleType) {
                $q->where('name', $roleType);
            })
            ->with('user')
            ->get();

        $usersToAssign = collect();
        foreach ($productAssignments as $pa) {
            if ($pa->user) $usersToAssign[$pa->user->id] = $pa->user;
        }
        foreach ($orderAssignments as $oa) {
            if ($oa->user) $usersToAssign[$oa->user->id] = $oa->user;
        }

        if ($usersToAssign->isEmpty()) {
            return false;
        }

        $assignedUsers = [];
        foreach ($usersToAssign as $user) {
            $existingAssignment = $order->assignments()
                ->where('user_id', $user->id)
                ->where('status', '!=', 'cancelled')
                ->first();

            if (!$existingAssignment) {
                $assignment = $order->assignments()->create([
                    'user_id' => $user->id,
                    'status' => 'pending',
                    'assigned_by' => $assignedBy ? $assignedBy->id : null,
                    'assigned_at' => now(),
                ]);
                $user->notify(new \App\Notifications\OrderAssigned($order, $assignedBy, $roleType, $order->stage ? $order->stage->name : null));
                $assignedUsers[] = $user;
            }
        }

        return !empty($assignedUsers);
    }

    /**
     * Очищает кэш заказов для всех пользователей
     */
    private function clearOrdersCache()
    {
        try {
            // Очищаем кэш заказов
            CacheService::invalidateOrderCaches();
        } catch (\Exception $e) {
            // Игнорируем ошибки очистки кэша
        }
    }
}
