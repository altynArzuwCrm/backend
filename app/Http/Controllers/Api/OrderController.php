<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\OrderStatusLog;
use App\Models\Stage;
use App\Repositories\OrderRepository;
use App\DTOs\OrderDTO;
use App\Services\CacheService;
use App\Notifications\BulkOrdersStageChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

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

        // Кэширование обрабатывается в OrderRepository
        // чтобы избежать сериализации полных Eloquent моделей
        $result = $this->orderRepository->getPaginatedOrders($request, $user);

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
            'assignments.*.user_id' => 'required_with:assignments|exists:users,id',
            'assignments.*.role_type' => 'required_with:assignments|string',
            'assignments.*.stage_id' => 'sometimes|exists:stages,id',
            'stages' => 'sometimes|array',
            'stages.*' => 'exists:stages,id',
            'is_bulk' => 'sometimes|boolean',
        ]);

        // Нормализуем пустые значения
        if (isset($data['project_id']) && ($data['project_id'] === '' || $data['project_id'] === 0)) {
            $data['project_id'] = null;
        }
        // product_id не нормализуем в null, так как он обязателен в БД
        // Если product_id пустой, валидация должна отклонить запрос
        if (isset($data['assignments']) && is_array($data['assignments']) && empty($data['assignments'])) {
            unset($data['assignments']); // Удаляем пустой массив назначений
        }
        if (isset($data['stages']) && is_array($data['stages']) && empty($data['stages'])) {
            unset($data['stages']); // Удаляем пустой массив стадий (но заказ все равно создастся)
        }

        $product = null;
        if (isset($data['product_id'])) {
            // Оптимизация: загружаем только необходимые поля
            $product = \App\Models\Product::select('id', 'name')->find($data['product_id']);
        }
        // Get the first available stage for this product
        // Оптимизация: используем кэшированные стадии
        $availableStages = $product ? $product->getAvailableStages() : \App\Models\Stage::ordered()->select('id', 'name', 'order')->get();

        // Определяем начальную стадию на основе назначений
        $initialStage = null;

        // Если есть назначения в запросе, определяем стадию по ним
        if (isset($data['assignments']) && is_array($data['assignments']) && !empty($data['assignments'])) {
            // Получаем все стадии в правильном порядке
            $orderedStages = $availableStages->sortBy('order');

            // Предзагружаем все стадии по ID для избежания N+1
            // Оптимизация: загружаем только нужные поля
            $assignmentStageIds = collect($data['assignments'])
                ->pluck('stage_id')
                ->filter()
                ->unique()
                ->values();
            $assignmentStagesById = \App\Models\Stage::select('id', 'name', 'order')
                ->whereIn('id', $assignmentStageIds)
                ->get()
                ->keyBy('id');

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
        if (!$initialStage && isset($data['product_id']) && !empty($data['product_id'])) {
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
            $stage = \App\Models\Stage::findByName($data['stage']);
            if ($stage) {
                $data['stage_id'] = $stage->id;
            } else {
                Log::error('Stage not found', ['stage_name' => $data['stage']]);
                // Если стадия не найдена, пытаемся найти draft как fallback
                $draftStage = \App\Models\Stage::findByName('draft');
                if ($draftStage) {
                    $data['stage_id'] = $draftStage->id;
                    $data['stage'] = 'draft';
                }
            }
        }
        
        // Если stage_id все еще не установлен, пытаемся найти draft
        if (!isset($data['stage_id']) || empty($data['stage_id'])) {
            $draftStage = \App\Models\Stage::findByName('draft');
            if ($draftStage) {
                $data['stage_id'] = $draftStage->id;
                $data['stage'] = 'draft';
            } else {
                Log::error('Draft stage not found in database');
            }
        }

        // Проверяем, что для массового заказа указан проект
        if (isset($data['is_bulk']) && $data['is_bulk']) {
            if (!isset($data['project_id']) && !isset($data['project_title'])) {
                return response()->json([
                    'message' => 'Проект обязателен для массового заказа'
                ], 422);
            }
        }

        // Используем транзакцию для обеспечения целостности данных
        try {
            $order = DB::transaction(function () use ($data, $request, $initialStage) {
                // Если указано название проекта, создаем новый проект в транзакции
                if (isset($data['is_bulk']) && $data['is_bulk'] && isset($data['project_title']) && !isset($data['project_id'])) {
                    $project = \App\Models\Project::create([
                        'title' => $data['project_title'],
                        'client_id' => $data['client_id'],
                    ]);
                    $data['project_id'] = $project->id;
                }

                // Проверяем обязательные поля перед созданием
                if (!isset($data['client_id'])) {
                    throw new \Exception('Клиент обязателен для создания заказа');
                }

                // Проверяем product_id (в БД он обязателен для всех заказов)
                if (!isset($data['product_id']) || empty($data['product_id'])) {
                    throw new \Exception('Продукт обязателен для создания заказа');
                }

                // Проверяем quantity (должно быть минимум 1)
                if (!isset($data['quantity']) || $data['quantity'] < 1) {
                    $data['quantity'] = 1; // Устанавливаем значение по умолчанию
                }

                // Проверяем, что stage_id установлен
                if (!isset($data['stage_id'])) {
                    Log::warning('Stage ID not set before order creation', [
                        'data' => $data,
                        'initial_stage' => $initialStage?->name ?? 'not found'
                    ]);
                    // Пытаемся найти стадию draft как fallback
                    $draftStage = \App\Models\Stage::findByName('draft');
                    if ($draftStage) {
                        $data['stage_id'] = $draftStage->id;
                    } else {
                        throw new \Exception('Не удалось определить начальную стадию заказа');
                    }
                }

                // Создаем заказ
                $order = Order::create($data);

                // Обрабатываем назначения, если они есть
                if (isset($data['assignments']) && is_array($data['assignments']) && !empty($data['assignments'])) {
                    // Получаем список выбранных стадий
                    $selectedStages = isset($data['stages']) ? $data['stages'] : [];

                    // Предзагружаем пользователей и стадии для избежания N+1
                    $userIds = collect($data['assignments'])->pluck('user_id')->unique()->values();
                    $stageIds = collect($data['assignments'])->pluck('stage_id')->filter()->unique()->values();
                    
                    // Проверяем, что все пользователи существуют
                    $existingUserIds = \App\Models\User::whereIn('id', $userIds)->pluck('id')->toArray();
                    $missingUserIds = array_diff($userIds->toArray(), $existingUserIds);
                    if (!empty($missingUserIds)) {
                        throw new \Exception('Один или несколько пользователей не найдены: ' . implode(', ', $missingUserIds));
                    }

                    $usersById = \App\Models\User::select('id', 'name', 'username', 'fcm_token')
                        ->whereIn('id', $userIds)
                        ->get()
                        ->keyBy('id');
                    
                    // Проверяем, что все стадии существуют (если указаны)
                    if ($stageIds->isNotEmpty()) {
                        $existingStageIds = \App\Models\Stage::whereIn('id', $stageIds)->pluck('id')->toArray();
                        $missingStageIds = array_diff($stageIds->toArray(), $existingStageIds);
                        if (!empty($missingStageIds)) {
                            throw new \Exception('Одна или несколько стадий не найдены: ' . implode(', ', $missingStageIds));
                        }
                    }

                    $stagesById = \App\Models\Stage::select('id', 'name')
                        ->whereIn('id', $stageIds)
                        ->get()
                        ->keyBy('id');

                    // Проверяем на дубликаты назначений (один пользователь на одну роль на одну стадию)
                    $assignmentKeys = [];
                    foreach ($data['assignments'] as $assignmentData) {
                        // Проверяем, что назначение создается только для выбранных стадий
                        if (isset($assignmentData['stage_id']) && !empty($selectedStages)) {
                            if (!in_array($assignmentData['stage_id'], $selectedStages)) {
                                continue;
                            }
                        }

                        // Проверяем обязательные поля назначения
                        if (!isset($assignmentData['user_id']) || !isset($assignmentData['role_type'])) {
                            Log::warning('Assignment missing required fields', ['assignment' => $assignmentData]);
                            continue;
                        }

                        // Проверяем на дубликаты
                        $key = $assignmentData['user_id'] . '_' . $assignmentData['role_type'] . '_' . ($assignmentData['stage_id'] ?? 'null');
                        if (in_array($key, $assignmentKeys)) {
                            Log::warning('Duplicate assignment detected', ['assignment' => $assignmentData]);
                            continue; // Пропускаем дубликаты
                        }
                        $assignmentKeys[] = $key;

                        // Проверяем, что пользователь существует
                        if (!$usersById->has($assignmentData['user_id'])) {
                            Log::warning('User not found for assignment', ['user_id' => $assignmentData['user_id']]);
                            continue;
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
                    }
                }

                return $order;
            });

            // Оптимизация: точечная инвалидация кэша вместо полной очистки
            CacheService::invalidateOrderCaches($order->id);

            // Отправка уведомлений изолирована от создания заказа
            // Если уведомления не отправятся, заказ все равно будет создан
            if (isset($data['assignments']) && is_array($data['assignments']) && !empty($data['assignments'])) {
                try {
                    $userIds = collect($data['assignments'])->pluck('user_id')->unique()->values();
                    $usersById = \App\Models\User::select('id', 'name', 'username', 'fcm_token')
                        ->whereIn('id', $userIds)
                        ->get()
                        ->keyBy('id');

                    foreach ($data['assignments'] as $assignmentData) {
                        if (!isset($assignmentData['user_id'])) {
                            continue;
                        }

                        $assignedUser = $usersById->get($assignmentData['user_id']);
                        if ($assignedUser) {
                            try {
                                $assignedUser->notify(new \App\Notifications\OrderAssigned(
                                    $order,
                                    $request->user(),
                                    $assignmentData['role_type'] ?? 'unknown',
                                    $order->stage ? $order->stage->name : null
                                ));
                            } catch (\Exception $e) {
                                Log::warning('Failed to send OrderAssigned notification', [
                                    'user_id' => $assignedUser->id,
                                    'order_id' => $order->id,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error sending notifications for order', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Валидационные ошибки возвращаем как есть
            return response()->json([
                'message' => 'Ошибка валидации данных',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating order', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            return response()->json([
                'message' => 'Ошибка при создании заказа: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }

        // Проверяем, что заказ был создан
        if (!$order || !isset($order->id)) {
            Log::error('Order was not created successfully', ['data' => $data]);
            return response()->json([
                'message' => 'Ошибка: заказ не был создан'
            ], 500);
        }

        $orderId = $order->id;

        // Оптимизация: загружаем только необходимые поля для ответа
        $order = Order::select('id', 'client_id', 'project_id', 'product_id', 'stage_id', 
                              'quantity', 'deadline', 'price', 'is_archived', 'created_at', 'updated_at')
            ->with([
                'assignments' => function ($q) {
                    $q->select('id', 'order_id', 'user_id', 'role_type', 'status');
                },
                'assignments.user' => function ($q) {
                    $q->select('id', 'name', 'username');
                },
                'project' => function ($q) {
                    $q->select('id', 'title');
                },
                'product' => function ($q) {
                    $q->select('id', 'name');
                },
                'client' => function ($q) {
                    $q->select('id', 'name', 'company_name');
                },
                'stage' => function ($q) {
                    $q->select('id', 'name', 'display_name', 'color');
                }
            ])
            ->find($orderId);
            
        if (!$order) {
            Log::error('Order not found after creation', ['order_id' => $orderId]);
            return response()->json([
                'message' => 'Ошибка: заказ не найден после создания'
            ], 500);
        }
            
        return response()->json($order, 201);
    }

    public function update(Request $request, Order $order)
    {
        if (Gate::denies('update', $order)) {
            abort(403, 'Доступ запрещён');
        }

        // Специальная обработка для project_id: null
        // Если project_id явно установлен в null, обрабатываем это отдельно
        $projectIdValue = $request->input('project_id');
        if ($request->has('project_id') && ($projectIdValue === null || $projectIdValue === '' || $projectIdValue === 'null')) {
            // Запоминаем старый проект для пересчета цены
            $oldProjectId = $order->project_id;
            
            $order->project_id = null;
            $order->save();
            
            // Пересчитываем цену старого проекта (если был)
            if ($oldProjectId) {
                try {
                    $oldProject = \App\Models\Project::find($oldProjectId);
                    if ($oldProject) {
                        $oldProject->recalculateTotalPrice();
                        // Инвалидируем кэш проекта
                        CacheService::invalidateByTags([CacheService::TAG_PROJECTS]);
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to recalculate old project price after detach", [
                        'project_id' => $oldProjectId,
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Оптимизация: точечная инвалидация кэша вместо полной очистки
            CacheService::invalidateOrderCaches($order->id);
            
            // Загружаем заказ с отношениями
            $order = Order::with('assignments.user')->find($order->id);
            
            return response()->json($order);
        }

        $data = $request->validate([
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'deadline' => ['nullable', 'date'],
            'price' => 'nullable|numeric|min:0',
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
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

            // Находим стадию по имени (используем кэшированный поиск)
            $stage = \App\Models\Stage::findByName('cancelled');
            if ($stage) {
                $data['stage_id'] = $stage->id;
            }
        } elseif (isset($data['stage']) && in_array($data['stage'], ['completed', 'cancelled'])) {
            // Для других завершающих стадий
            $data['is_archived'] = true;
            $data['archived_at'] = now();

            // Находим стадию по имени (используем кэшированный поиск)
            $stage = \App\Models\Stage::findByName($data['stage']);
            if ($stage) {
                $data['stage_id'] = $stage->id;
            }
        } elseif (isset($data['stage'])) {
            // Для обычных стадий
            $data['is_archived'] = false;
            $data['archived_at'] = null;
            $data['reason'] = null;
            $data['reason_status'] = null;

            // Находим стадию по имени (используем кэшированный поиск)
            $stage = \App\Models\Stage::findByName($data['stage']);
            if ($stage) {
                $data['stage_id'] = $stage->id;
            }
        }

        // Запоминаем старый project_id для пересчета цены старого проекта
        $oldProjectId = $order->project_id;
        $projectChanged = isset($data['project_id']) && $order->project_id != $data['project_id'];
        
        try {
            $order->update($data);
            
            // Если проект изменился, пересчитываем цены обоих проектов
            // (новый проект пересчитается через Order Observer, но старый нужно пересчитать вручную)
            if ($projectChanged && $oldProjectId) {
                try {
                    $oldProject = \App\Models\Project::find($oldProjectId);
                    if ($oldProject) {
                        $oldProject->recalculateTotalPrice();
                        CacheService::invalidateByTags([CacheService::TAG_PROJECTS]);
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to recalculate old project price after attach", [
                        'project_id' => $oldProjectId,
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error updating order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            return response()->json([
                'message' => 'Ошибка при обновлении заказа',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }

        // Оптимизация: точечная инвалидация кэша вместо полной очистки
        CacheService::invalidateOrderCaches($order->id);

        // Обрабатываем назначения, если они есть
        if (isset($data['assignments']) && is_array($data['assignments'])) {
            // Удаляем существующие назначения
            $order->assignments()->delete();

            // Получаем список выбранных стадий
            $selectedStages = isset($data['stages']) ? $data['stages'] : [];

            // Предзагружаем пользователей и стадии для избежания N+1
            // Оптимизация: загружаем только нужные поля
            $userIds = collect($data['assignments'])->pluck('user_id')->unique()->values();
            $stageIds = collect($data['assignments'])->pluck('stage_id')->filter()->unique()->values();
            
            $usersById = \App\Models\User::select('id', 'name', 'username', 'fcm_token')
                ->whereIn('id', $userIds)
                ->get()
                ->keyBy('id');
            $stagesById = \App\Models\Stage::select('id', 'name')
                ->whereIn('id', $stageIds)
                ->get()
                ->keyBy('id');

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

        // Оптимизация: загружаем только необходимые поля для ответа
        $order = Order::select('id', 'client_id', 'project_id', 'product_id', 'stage_id', 
                              'quantity', 'deadline', 'price', 'is_archived', 'created_at', 'updated_at')
            ->with([
                'assignments' => function ($q) {
                    $q->select('id', 'order_id', 'user_id', 'role_type', 'status');
                },
                'assignments.user' => function ($q) {
                    $q->select('id', 'name', 'username');
                },
                'project' => function ($q) {
                    $q->select('id', 'title');
                },
                'product' => function ($q) {
                    $q->select('id', 'name');
                },
                'client' => function ($q) {
                    $q->select('id', 'name', 'company_name');
                },
                'stage' => function ($q) {
                    $q->select('id', 'name', 'display_name', 'color');
                }
            ])
            ->find($order->id);
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

        // Исправление N+1: загружаем отношения заранее
        $order->load(['stage', 'product']);

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

        // Находим стадию по имени (используем кэшированный поиск)
        $stage = \App\Models\Stage::findByName($newStageName);
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

        try {
            $order->stage_id = $stage->id;
            $order->save();
            $order->refresh();
        } catch (\Exception $e) {
            Log::error('Error updating order stage', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Ошибка при обновлении стадии заказа',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }

        // Оптимизация: точечная инвалидация кэша вместо полной очистки
        CacheService::invalidateOrderCaches($order->id);

        // Создаем запись в логе изменений статуса
        \App\Models\OrderStatusLog::create([
            'order_id' => $order->id,
            'from_status' => $oldStage ? $oldStage->name : '',
            'to_status' => $order->stage->name,
            'user_id' => $request->user()->id,
            'changed_at' => now(),
        ]);

        // Отправляем уведомления администраторам и менеджерам о переходе на новую стадию
        // Оптимизация: используем whereExists вместо whereHas
        $adminsAndManagers = \App\Models\User::whereExists(function ($subquery) {
            $subquery->select(DB::raw(1))
                ->from('user_roles')
                ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                ->whereColumn('user_roles.user_id', 'users.id')
                ->whereIn('roles.name', ['admin', 'manager']);
        })->select('id', 'name', 'username', 'fcm_token')->get();

        foreach ($adminsAndManagers as $admin) {
            $admin->notify(new \App\Notifications\OrderStageChanged($order, $oldStage, $order->stage, $request->user()));
        }

        // Отправляем уведомления пользователям, назначенным на эту стадию
        // Оптимизация: используем whereExists вместо whereHas
        $stageAssignments = $order->assignments()
            ->whereExists(function ($subquery) use ($stage) {
                $subquery->select(DB::raw(1))
                    ->from('order_stage_assignments')
                    ->whereColumn('order_stage_assignments.order_assignment_id', 'order_assignments.id')
                    ->where('order_stage_assignments.stage_id', $stage->id);
            })
            ->select('id', 'order_id', 'user_id', 'role_type', 'status')
            ->with([
                'user' => function ($q) {
                    $q->select('id', 'name', 'username', 'fcm_token');
                },
                'orderStageAssignments' => function ($q) {
                    $q->select('id', 'order_assignment_id', 'stage_id', 'is_assigned');
                }
            ])
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

        // Оптимизация: загружаем только необходимые поля для ответа
        $order = Order::select('id', 'client_id', 'project_id', 'product_id', 'stage_id', 
                              'quantity', 'deadline', 'price', 'is_archived', 'created_at', 'updated_at')
            ->with([
                'project' => function ($q) {
                    $q->select('id', 'title');
                },
                'product' => function ($q) {
                    $q->select('id', 'name');
                },
                'client' => function ($q) {
                    $q->select('id', 'name', 'company_name');
                },
                'stage' => function ($q) {
                    $q->select('id', 'name', 'display_name', 'color');
                },
                'assignments' => function ($q) {
                    $q->select('id', 'order_id', 'user_id', 'role_type', 'status');
                },
                'assignments.user' => function ($q) {
                    $q->select('id', 'name', 'username');
                }
            ])
            ->find($order->id);
        
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

        try {
            $orderId = $order->id;
            $order->delete();

            // Оптимизация: точечная инвалидация кэша вместо полной очистки
            CacheService::invalidateOrderCaches($orderId);

            return response()->json(['message' => 'Заказ удалён']);
        } catch (\Exception $e) {
            Log::error('Error deleting order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Ошибка при удалении заказа',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
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

        // Оптимизация: используем whereExists вместо whereHas для лучшей производительности
        $orderAssignments = $order->assignments()
            ->whereExists(function ($subquery) use ($roleType) {
                $subquery->select(DB::raw(1))
                    ->from('user_roles')
                    ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                    ->whereColumn('user_roles.user_id', 'order_assignments.user_id')
                    ->where('roles.name', $roleType);
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
     * Массовое обновление статуса заказов
     */
    public function bulkUpdateStatus(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer',
            'stage' => 'required|string',
            'reason' => 'sometimes|nullable|string',
            'reason_status' => 'sometimes|nullable|string',
        ]);

        $orderIds = $data['ids'];
        $newStageName = $data['stage'];
        $reason = $data['reason'] ?? null;
        $reasonStatus = $data['reason_status'] ?? null;

        // Проверяем существование стадии (используем кэшированный поиск)
        $stage = Stage::findByName($newStageName);
        if (!$stage) {
            return response()->json([
                'message' => 'Стадия не найдена'
            ], 422);
        }

        $updated = 0;
        $errors = [];
        $user = $request->user();

        // Предзагружаем список администраторов и менеджеров один раз
        $adminsAndManagers = \App\Models\User::whereExists(function ($subquery) {
            $subquery->select(DB::raw(1))
                ->from('user_roles')
                ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                ->whereColumn('user_roles.user_id', 'users.id')
                ->whereIn('roles.name', ['admin', 'manager']);
        })->get();

        $notifiedUsers = [];
        $changedOrders = [];

        foreach ($orderIds as $orderId) {
            try {
                $order = Order::with('stage')->find($orderId);
                
                if (!$order) {
                    $errors[] = "Заказ ID $orderId не найден";
                    continue;
                }

                // Проверка прав
                if (Gate::denies('changeOrderStatus', $order)) {
                    $errors[] = "Заказ ID $orderId: нет прав для смены статуса";
                    continue;
                }

                $oldStage = $order->stage;

                // Для completed проверяем неодобренные назначения
                if ($newStageName === 'completed') {
                    $pendingAssignments = $order->assignments()
                        ->where('status', '!=', 'approved')
                        ->get();

                    if ($pendingAssignments->isNotEmpty()) {
                        $errors[] = "Заказ ID $orderId: нельзя завершить, пока есть неодобренные назначения";
                        continue;
                    }
                }

                DB::transaction(function () use ($order, $newStageName, $stage, $reason, $reasonStatus, $user, $orderId, &$updated) {
                    $oldStage = $order->stage;

                    // Обновляем стадию
                    if ($newStageName == 'cancelled') {
                        $order->reason = $reason ?? 'Изменено через массовое обновление';
                        $order->reason_status = $reasonStatus ?? 'refused';
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

                    // Обновляем модель, чтобы подтянуть свежие данные
                    $order->refresh();
                    $order->load('stage');

                    // Создаем запись в логе изменений статуса
                    OrderStatusLog::create([
                        'order_id' => $order->id,
                        'from_status' => $oldStage ? $oldStage->name : '',
                        'to_status' => $newStageName,
                        'user_id' => $user->id,
                        'changed_at' => now(),
                    ]);

                    $updated++;

                    Log::info("Bulk status update: Order $orderId updated to $newStageName", [
                        'order_id' => $orderId,
                        'stage' => $newStageName,
                        'user_id' => $user->id
                    ]);
                });

                $order->loadMissing(['stage', 'project']);

                $newStage = $order->stage;
                $stageChanged = !$oldStage || !$newStage || $oldStage->id !== $newStage->id;

                if ($stageChanged) {
                    $changedOrders[] = [
                        'id' => $order->id,
                        'title' => $order->project?->title ?? 'Заказ #' . $order->id,
                        'old_stage' => $oldStage ? ($oldStage->display_name ?? $oldStage->name ?? '') : '',
                        'new_stage' => $newStage ? ($newStage->display_name ?? $newStage->name ?? $newStageName) : ($stage->display_name ?? $newStageName),
                    ];

                    foreach ($adminsAndManagers as $admin) {
                        $notifiedUsers[$admin->id] = $admin;
                    }

                    $stageAssignments = $order->assignments()
                        ->whereExists(function ($subquery) use ($stage) {
                            $subquery->select(DB::raw(1))
                                ->from('order_stage_assignments')
                                ->whereColumn('order_stage_assignments.order_assignment_id', 'order_assignments.id')
                                ->where('order_stage_assignments.stage_id', $stage->id);
                        })
                        ->select('id', 'order_id', 'user_id', 'role_type', 'status')
                        ->with([
                            'user' => function ($q) {
                                $q->select('id', 'name', 'username', 'display_name', 'fcm_token');
                            }
                        ])
                        ->get();

                    foreach ($stageAssignments as $assignment) {
                        $assignedUser = $assignment->user;
                        if ($assignedUser && $assignedUser->id !== $user->id) {
                            $notifiedUsers[$assignedUser->id] = $assignedUser;
                        }
                    }
                }

            } catch (\Exception $e) {
                $errors[] = "Заказ ID $orderId: {$e->getMessage()}";
                Log::error("Bulk status update error for Order $orderId", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Оптимизация: инвалидируем кэш только для затронутых заказов
        foreach ($orderIds as $orderId) {
            CacheService::invalidateOrderCaches($orderId);
        }

        $response = [
            'message' => "Обновлено заказов: $updated",
            'updated' => $updated,
            'total_requested' => count($orderIds)
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        if (!empty($changedOrders) && !empty($notifiedUsers)) {
            $notification = new \App\Notifications\BulkOrdersStageChanged(
                $changedOrders,
                $stage->name,
                $stage->display_name ?? $stage->name,
                $user
            );

            foreach ($notifiedUsers as $notifiedUser) {
                try {
                    $notifiedUser->notify($notification);
                } catch (\Exception $e) {
                    Log::error('Failed to send bulk stage change notification', [
                        'user_id' => $notifiedUser->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        }

        return response()->json($response, $updated > 0 ? 200 : 422);
    }

    /**
     * Массовая отвязка заказов от проекта
     */
    public function bulkDetachFromProject(Request $request)
    {
        $data = $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'required|integer|exists:orders,id',
        ]);

        $orderIds = $data['order_ids'];
        $user = $request->user();
        $detached = 0;
        $errors = [];
        $projectIds = [];

        foreach ($orderIds as $orderId) {
            try {
                $order = Order::find($orderId);
                
                if (!$order) {
                    $errors[] = "Заказ ID $orderId не найден";
                    continue;
                }

                // Проверка прав
                if (Gate::denies('update', $order)) {
                    $errors[] = "Заказ ID $orderId: нет прав для обновления";
                    continue;
                }

                // Сохраняем ID проекта для пересчета цены
                if ($order->project_id && !in_array($order->project_id, $projectIds)) {
                    $projectIds[] = $order->project_id;
                }

                // Отвязываем заказ от проекта
                $order->project_id = null;
                $order->save();

                // Инвалидируем кэш
                CacheService::invalidateOrderCaches($order->id);

                $detached++;
            } catch (\Exception $e) {
                $errors[] = "Заказ ID $orderId: {$e->getMessage()}";
                Log::error("Bulk detach error for Order $orderId", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Пересчитываем цены проектов (только уникальные проекты)
        $uniqueProjectIds = array_unique($projectIds);
        foreach ($uniqueProjectIds as $projectId) {
            try {
                $project = \App\Models\Project::find($projectId);
                if ($project) {
                    $project->recalculateTotalPrice();
                    // Инвалидируем кэш проекта
                    CacheService::invalidateByTags([CacheService::TAG_PROJECTS]);
                }
            } catch (\Exception $e) {
                Log::warning("Failed to recalculate project price", [
                    'project_id' => $projectId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $response = [
            'message' => "Отвязано заказов: $detached",
            'detached' => $detached,
            'total_requested' => count($orderIds)
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $detached > 0 ? 200 : 422);
    }

    /**
     * Очищает кэш заказов для всех пользователей
     * @deprecated Используйте CacheService::invalidateOrderCaches() напрямую
     */
    private function clearOrdersCache()
    {
        try {
            // Очищаем кэш заказов (используется только для совместимости)
            CacheService::invalidateOrderCaches();
        } catch (\Exception $e) {
            // Игнорируем ошибки очистки кэша
            Log::warning('Error clearing orders cache', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
