<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderAssignment;
use App\DTOs\OrderDTO;
use App\DTOs\OrderAssignmentDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class OrderRepository
{
    public function getPaginatedOrders(Request $request, $user): LengthAwarePaginator
    {
        // Создаем ключ кэша на основе параметров запроса
        $cacheKey = 'orders_' . md5($request->fullUrl() . '_' . $user->id);

        // Проверяем, нужно ли принудительно обновить кэш
        $cacheTime = $request->has('force_refresh') ? 0 : 300;

        // Кэшируем результат на 5 минут (или обновляем принудительно)
        return Cache::remember($cacheKey, $cacheTime, function () use ($request, $user) {
            $query = Order::with(['project', 'product', 'client', 'stage']);

            // Фильтрация по правам доступа
            // Если есть флаг admin_view и пользователь админ/менеджер - показываем ВСЕ заказы
            if ($request->has('admin_view') && $user->hasAnyRole(['admin', 'manager'])) {
                // Не применяем никаких фильтров для админов с флагом admin_view
                \Log::info('Admin view requested', [
                    'user_id' => $user->id,
                    'user_roles' => $user->roles->pluck('name')->toArray(),
                    'admin_view' => true
                ]);
            } elseif (!$user->hasAnyRole(['admin', 'manager'])) {
                $assignedOrderIds = OrderAssignment::query()
                    ->where('user_id', $user->id)
                    ->pluck('order_id');
                $query->whereIn('id', $assignedOrderIds);

                \Log::info('Regular user view', [
                    'user_id' => $user->id,
                    'assigned_orders_count' => $assignedOrderIds->count()
                ]);
            } else {
                \Log::info('Admin/Manager view without admin_view flag', [
                    'user_id' => $user->id,
                    'user_roles' => $user->roles->pluck('name')->toArray()
                ]);
            }

            // Фильтры
            if ($request->project_id) {
                $query->where('project_id', $request->project_id);
            }

            if ($request->client_id) {
                $query->where('client_id', $request->client_id);
            }

            if ($request->filled('stage')) {
                $stage = \App\Models\Stage::where('name', $request->stage)->first();
                if ($stage) {
                    $query->where('stage_id', $stage->id);
                }
            }

            if ($request->filled('is_archived')) {
                $isArchived = $request->boolean('is_archived');
                $query->where('is_archived', $isArchived);
            }

            if ($request->filled('assignment_status')) {
                $assignmentStatus = $request->assignment_status;
                $query->whereHas('assignments', function ($q) use ($assignmentStatus) {
                    $q->where('status', $assignmentStatus);
                })->whereDoesntHave('assignments', function ($q) use ($assignmentStatus) {
                    $q->where('status', '!=', $assignmentStatus);
                });
            }

            // Поиск
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', '%' . $search . '%')
                        ->orWhereHas('product', function ($productQuery) use ($search) {
                            $productQuery->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('client', function ($clientQuery) use ($search) {
                            $clientQuery->where('name', 'like', '%' . $search . '%');
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
        $order = Order::with([
            'project',
            'product',
            'client.contacts',
            'stage',
            'assignments.user.roles',
            'assignments.assignedBy',
            'assignments.assignedStages'
        ])->find($id);

        if (!$order) {
            return null;
        }

        return OrderDTO::fromModel($order);
    }

    public function createOrder(array $data): OrderDTO
    {
        $order = Order::create($data);
        return OrderDTO::fromModel($order);
    }

    public function updateOrder(Order $order, array $data): OrderDTO
    {
        $order->update($data);
        return OrderDTO::fromModel($order);
    }

    public function deleteOrder(Order $order): bool
    {
        return $order->delete();
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
        $assignedOrderIds = OrderAssignment::query()
            ->where('user_id', $userId)
            ->pluck('order_id');

        $orders = Order::with(['project', 'product', 'client', 'stage'])
            ->whereIn('id', $assignedOrderIds)
            ->get();
        return array_map([OrderDTO::class, 'fromModel'], $orders->toArray());
    }
}
