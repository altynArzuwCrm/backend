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
        $cacheKey = 'orders_' . $user->id . '_' . md5($request->fullUrl());
        $cacheTime = $request->has('force_refresh') ? 0 : 900; // 15 минут

        // Проверяем кэш, но преобразуем модели в массивы перед кэшированием
        $cached = Cache::get($cacheKey);
        if ($cached !== null && $cacheTime > 0) {
            // Восстанавливаем пагинатор из кэшированных данных
            return $this->restorePaginatorFromCache($cached, $request);
        }

        // Оптимизация: выбираем только необходимые поля для уменьшения размера данных
        $query = Order::select('id', 'client_id', 'project_id', 'product_id', 'stage_id', 
                               'quantity', 'deadline', 'price', 'payment_amount', 'payment_type', 'is_archived', 'created_at', 'updated_at')
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

        $orders = $query->paginate($perPage);

        // Кэшируем только массив данных, а не модели
        if ($cacheTime > 0) {
            $cacheData = [
                'items' => $orders->getCollection()->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'client_id' => $order->client_id,
                        'project_id' => $order->project_id,
                        'product_id' => $order->product_id,
                        'stage_id' => $order->stage_id,
                        'quantity' => $order->quantity,
                        'deadline' => $order->deadline?->toDateTimeString(),
                        'price' => $order->price,
                        'payment_amount' => $order->payment_amount,
                        'payment_type' => $order->payment_type,
                        'is_archived' => $order->is_archived,
                        'created_at' => $order->created_at?->toDateTimeString(),
                        'updated_at' => $order->updated_at?->toDateTimeString(),
                        'project' => $order->project ? [
                            'id' => $order->project->id,
                            'title' => $order->project->title,
                        ] : null,
                        'product' => $order->product ? [
                            'id' => $order->product->id,
                            'name' => $order->product->name,
                        ] : null,
                        'client' => $order->client ? [
                            'id' => $order->client->id,
                            'name' => $order->client->name,
                            'company_name' => $order->client->company_name,
                        ] : null,
                        'stage' => $order->stage ? [
                            'id' => $order->stage->id,
                            'name' => $order->stage->name,
                            'display_name' => $order->stage->display_name,
                            'color' => $order->stage->color,
                        ] : null,
                    ];
                })->toArray(),
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'path' => $orders->path(),
                'query' => $orders->getOptions(),
            ];
            Cache::put($cacheKey, $cacheData, $cacheTime);
            
            // Отслеживаем ключ для инвалидации
            $trackingKey = 'cache_keys_' . CacheService::TAG_ORDERS;
            $keys = Cache::get($trackingKey, []);
            if (!in_array($cacheKey, $keys)) {
                $keys[] = $cacheKey;
                Cache::put($trackingKey, $keys, 86400);
            }
        }

        return $orders;
    }

    public function getOrderById(int $id): ?OrderDTO
    {
        // Кэшируем отдельные заказы на 30 минут
        $cacheKey = 'order_' . $id;
        return Cache::remember($cacheKey, 1800, function () use ($id) {
            // Оптимизация: загружаем только необходимые поля
            $order = Order::select('id', 'client_id', 'project_id', 'product_id', 'stage_id', 
                                  'quantity', 'deadline', 'price', 'payment_amount', 'payment_type', 'is_archived', 'reason', 'reason_status', 
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

    /**
     * Восстанавливает пагинатор из кэшированных данных
     */
    private function restorePaginatorFromCache(array $cacheData, Request $request): LengthAwarePaginator
    {
        // Восстанавливаем модели из массива
        $items = collect($cacheData['items'])->map(function ($item) {
            $order = new Order();
            $order->id = $item['id'];
            $order->client_id = $item['client_id'];
            $order->project_id = $item['project_id'];
            $order->product_id = $item['product_id'];
            $order->stage_id = $item['stage_id'];
            $order->quantity = $item['quantity'];
            $order->deadline = $item['deadline'] ? \Carbon\Carbon::parse($item['deadline']) : null;
            $order->price = $item['price'];
            $order->payment_amount = $item['payment_amount'] ?? null;
            $order->payment_type = $item['payment_type'] ?? null;
            $order->is_archived = $item['is_archived'];
            $order->created_at = $item['created_at'] ? \Carbon\Carbon::parse($item['created_at']) : null;
            $order->updated_at = $item['updated_at'] ? \Carbon\Carbon::parse($item['updated_at']) : null;
            
            // Восстанавливаем отношения
            if ($item['project']) {
                $project = new \App\Models\Project();
                $project->id = $item['project']['id'];
                $project->title = $item['project']['title'];
                $order->setRelation('project', $project);
            }
            
            if ($item['product']) {
                $product = new \App\Models\Product();
                $product->id = $item['product']['id'];
                $product->name = $item['product']['name'];
                $order->setRelation('product', $product);
            }
            
            if ($item['client']) {
                $client = new \App\Models\Client();
                $client->id = $item['client']['id'];
                $client->name = $item['client']['name'];
                $client->company_name = $item['client']['company_name'];
                $order->setRelation('client', $client);
            }
            
            if ($item['stage']) {
                $stage = new \App\Models\Stage();
                $stage->id = $item['stage']['id'];
                $stage->name = $item['stage']['name'];
                $stage->display_name = $item['stage']['display_name'];
                $stage->color = $item['stage']['color'];
                $order->setRelation('stage', $stage);
            }
            
            return $order;
        });

        return new LengthAwarePaginator(
            $items,
            $cacheData['total'],
            $cacheData['per_page'],
            $cacheData['current_page'],
            array_merge($cacheData['query'], ['path' => $cacheData['path']])
        );
    }
}
