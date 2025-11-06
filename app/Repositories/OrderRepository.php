<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderAssignment;
use App\DTOs\OrderDTO;
use App\DTOs\OrderAssignmentDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Services\CacheService;

class OrderRepository
{
    public function getPaginatedOrders(Request $request, $user): LengthAwarePaginator
    {
        // Создаем ключ кэша на основе параметров запроса
        $cacheKey = 'orders_' . md5($request->fullUrl() . '_' . $user->id);

        // Проверяем, нужно ли принудительно обновить кэш
        $cacheTime = $request->has('force_refresh') ? 0 : 900;

        // Кэшируем результат на 15 минут (или обновляем принудительно)
        return Cache::remember($cacheKey, $cacheTime, function () use ($request, $user) {
            // Оптимизация: выбираем только необходимые поля для уменьшения размера данных
            $query = Order::select('id', 'client_id', 'project_id', 'product_id', 'stage_id', 
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
                    }
                ]);

            // Фильтрация по правам доступа
            // Если есть флаг admin_view и пользователь админ/менеджер - показываем ВСЕ заказы
            if ($request->has('admin_view') && $user->hasAnyRole(['admin', 'manager'])) {
                // Не применяем никаких фильтров для админов с флагом admin_view
            } elseif (!$user->hasAnyRole(['admin', 'manager'])) {
                // Оптимизация: используем whereExists вместо pluck + whereIn
                $query->whereExists(function ($subquery) use ($user) {
                    $subquery->select(DB::raw(1))
                        ->from('order_assignments')
                        ->whereColumn('order_assignments.order_id', 'orders.id')
                        ->where('order_assignments.user_id', $user->id);
                });

                // Обычный пользователь — показываем только назначенные заказы
            } else {
                // Админ/менеджер без admin_view — полный доступ
            }

            // Фильтры
            if ($request->project_id) {
                $query->where('project_id', $request->project_id);
            }

            if ($request->client_id) {
                $query->where('client_id', $request->client_id);
            }

            // Определяем, нужно ли применять фильтр по архиву
            $shouldApplyArchiveFilter = true;
            if ($request->filled('stage')) {
                // Используем кэшированный поиск стадии
                $stage = \App\Models\Stage::findByName($request->stage);
                if ($stage) {
                    $query->where('stage_id', $stage->id);
                    
                    // Для завершенных и отмененных заказов не применяем фильтр is_archived
                    if (in_array($stage->name, ['completed', 'cancelled'])) {
                        $shouldApplyArchiveFilter = false;
                    }
                }
            }

            if ($request->filled('is_archived') && $shouldApplyArchiveFilter) {
                $isArchived = $request->boolean('is_archived');
                $query->where('is_archived', $isArchived);
            }

            if ($request->filled('assignment_status')) {
                $assignmentStatus = $request->assignment_status;
                // Оптимизация: используем whereExists вместо whereHas для лучшей производительности
                // Фильтруем заказы, которые имеют назначения с указанным статусом
                // и не имеют назначений с другим статусом
                $query->whereExists(function ($subquery) use ($assignmentStatus) {
                    $subquery->select(DB::raw(1))
                        ->from('order_assignments')
                        ->whereColumn('order_assignments.order_id', 'orders.id')
                        ->where('order_assignments.status', $assignmentStatus);
                })->whereNotExists(function ($subquery) use ($assignmentStatus) {
                    $subquery->select(DB::raw(1))
                        ->from('order_assignments')
                        ->whereColumn('order_assignments.order_id', 'orders.id')
                        ->where('order_assignments.status', '!=', $assignmentStatus);
                });
            }

            // Поиск - оптимизирован через join вместо whereHas
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('orders.id', 'like', '%' . $search . '%')
                        ->orWhereExists(function ($subquery) use ($search) {
                            $subquery->select(DB::raw(1))
                                ->from('products')
                                ->whereColumn('products.id', 'orders.product_id')
                                ->where('products.name', 'like', '%' . $search . '%');
                        })
                        ->orWhereExists(function ($subquery) use ($search) {
                            $subquery->select(DB::raw(1))
                                ->from('clients')
                                ->whereColumn('clients.id', 'orders.client_id')
                                ->where(function ($clientQuery) use ($search) {
                                    $clientQuery->where('clients.name', 'like', '%' . $search . '%')
                                        ->orWhere('clients.company_name', 'like', '%' . $search . '%');
                                });
                        });
                });
            }

            // Сортировка
            $sortBy = $request->get('sort_by', 'id');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Пагинация
            $allowedPerPage = [10, 20, 50, 100, 200, 500, 1000, 10000];
            $perPage = (int) $request->input('per_page', 30);
            if (!in_array($perPage, $allowedPerPage)) {
                $perPage = 30;
            }

            return $query->paginate($perPage);
        });
    }

    public function getOrderById(int $id): ?OrderDTO
    {
        // Кэшируем отдельные заказы на 30 минут
        $cacheKey = 'order_' . $id;
        return Cache::remember($cacheKey, 1800, function () use ($id) {
            // Оптимизация: загружаем только необходимые поля
            $order = Order::select('id', 'client_id', 'project_id', 'product_id', 'stage_id', 
                                  'quantity', 'deadline', 'price', 'is_archived', 'reason', 'reason_status', 
                                  'archived_at', 'created_at', 'updated_at')
                ->with([
                    'project' => function ($q) {
                        $q->select('id', 'title', 'deadline', 'total_price');
                    },
                    'product' => function ($q) {
                        $q->select('id', 'name');
                    },
                    'client' => function ($q) {
                        $q->select('id', 'name', 'company_name');
                    },
                    'client.contacts' => function ($q) {
                        $q->select('id', 'client_id', 'type', 'value');
                    },
                    'stage' => function ($q) {
                        $q->select('id', 'name', 'display_name', 'color', 'order');
                    },
                    'assignments' => function ($q) {
                        $q->select('id', 'order_id', 'user_id', 'role_type', 'status', 'assigned_by', 'assigned_at', 'started_at', 'completed_at');
                    },
                    'assignments.user' => function ($q) {
                        $q->select('id', 'name', 'username');
                    },
                    'assignments.user.roles' => function ($q) {
                        $q->select('roles.id', 'roles.name', 'roles.display_name');
                    },
                    'assignments.assignedBy' => function ($q) {
                        $q->select('id', 'name', 'username');
                    },
                    'assignments.assignedStages' => function ($q) {
                        $q->select('stages.id', 'stages.name', 'stages.display_name');
                    }
                ])
                ->find($id);

            if (!$order) {
                return null;
            }

            return OrderDTO::fromModel($order);
        });
    }

    public function createOrder(array $data): OrderDTO
    {
        $order = Order::create($data);
        
        // Инвалидируем кэш заказов
        CacheService::invalidateOrderCaches();
        
        return OrderDTO::fromModel($order);
    }

    public function updateOrder(Order $order, array $data): OrderDTO
    {
        $order->update($data);
        
        // Инвалидируем кэш заказов
        CacheService::invalidateOrderCaches($order->id);
        Cache::forget('order_' . $order->id);
        
        return OrderDTO::fromModel($order);
    }

    public function deleteOrder(Order $order): bool
    {
        $orderId = $order->id;
        $result = $order->delete();
        
        // Инвалидируем кэш заказов
        CacheService::invalidateOrderCaches($orderId);
        Cache::forget('order_' . $orderId);
        
        return $result;
    }

    public function getAllOrders(): array
    {
        $orders = Order::with(['project', 'product', 'client', 'stage'])->get();
        return array_map([OrderDTO::class, 'fromModel'], $orders->toArray());
    }

    public function getOrdersByStage(string $stage): array
    {
        $orders = Order::with(['project', 'product', 'client', 'stage'])
            ->where('stage', $stage)
            ->get();
        return array_map([OrderDTO::class, 'fromModel'], $orders->toArray());
    }

    public function getOrdersByUser(int $userId): array
    {
        // Оптимизация: используем whereExists вместо pluck + whereIn
        $orders = Order::with(['project', 'product', 'client', 'stage'])
            ->whereExists(function ($subquery) use ($userId) {
                $subquery->select(DB::raw(1))
                    ->from('order_assignments')
                    ->whereColumn('order_assignments.order_id', 'orders.id')
                    ->where('order_assignments.user_id', $userId);
            })
            ->get();
        return array_map([OrderDTO::class, 'fromModel'], $orders->toArray());
    }
}
