<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Services\CacheService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        if (Gate::denies('viewAny', Project::class)) {
            abort(403, 'Доступ запрещён');
        }

        $user = $request->user();

        // Оптимизация: выбираем только необходимые поля для уменьшения размера данных
        $query = Project::select('id', 'title', 'deadline', 'total_price', 'payment_amount', 'created_at')
            ->withCount('orders'); // Используем withCount вместо загрузки всех заказов

        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'asc');

        $allowedSorts = ['id', 'title', 'deadline', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%')
                    ->orWhereHas('orders.client', function ($clientQuery) use ($search) {
                        $clientQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        if (!$user->hasAnyRole(['admin', 'manager'])) {
            $assignedOrderIds = OrderAssignment::query()
                ->where('user_id', $user->id)
                ->pluck('order_id');

            $projectIds = Order::whereIn('id', $assignedOrderIds)
                ->pluck('project_id');

            $query->whereIn('id', $projectIds);
        }

        $allowedPerPage = [10, 20, 50, 100, 200, 500];
        $perPage = (int) $request->get('per_page', 30);
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 30;
        }

        // Кэшируем результаты поиска на 15 минут для быстрых ответов
        $cacheKey = 'projects_' . $user->id . '_' . md5($request->fullUrl());
        $projects = CacheService::rememberWithTags($cacheKey, 900, function () use ($query, $perPage) {
            return $query->paginate($perPage);
        }, [CacheService::TAG_PROJECTS]);

        return response()->json($projects);
    }

    public function show(Request $request, Project $project)
    {
        try {
            $user = $request->user();

            if (Gate::denies('view', $project)) {
                abort(403, 'Доступ запрещён');
            }

            $project->load(['orders.product', 'orders.client']);

            return response()->json($project);
        } catch (\Exception $e) {
            Log::error('Error in ProjectController@show: ' . $e->getMessage());
            return response()->json(['error' => 'Ошибка загрузки проекта'], 500);
        }
    }

    public function store(Request $request)
    {
        if (Gate::denies('create', Project::class)) {
            abort(403, 'Доступ запрещён');
        }

        if ($request->has('orders') && is_array($request->orders) && count($request->orders) > 1) {
            $request->validate([
                'title' => 'required|string|max:255',
                'deadline' => 'nullable|date',
                'total_price' => 'nullable|numeric|min:0',
                'payment_amount' => 'nullable|numeric|min:0',
                'orders' => 'required|array|min:2',
                'orders.*.product_id' => 'required|exists:products,id',
                'orders.*.quantity' => 'sometimes|integer|min:1',
                'orders.*.deadline' => 'nullable|date',
                'orders.*.price' => 'nullable|numeric',
                'orders.*.client_id' => 'required|exists:clients,id',
                'orders.*.stages' => 'sometimes|array',
                'orders.*.stages.*' => 'string|exists:stages,name',
                'orders.*.assignments' => 'sometimes|array',
                'orders.*.assignments.*.user_id' => 'required|exists:users,id',
                'orders.*.assignments.*.role_type' => 'required|string',
            ]);

            $project = Project::create([
                'title' => $request->title,
                'deadline' => $request->deadline,
                'total_price' => $request->total_price,
                'payment_amount' => $request->payment_amount ?? 0,
            ]);



            // Предзагружаем всех пользователей для избежания N+1
            $allUserIds = collect($request->orders)
                ->flatMap(function ($orderData) {
                    return isset($orderData['assignments']) ? collect($orderData['assignments'])->pluck('user_id') : [];
                })
                ->unique()
                ->values();
            $usersById = \App\Models\User::whereIn('id', $allUserIds)->get()->keyBy('id');

            $orders = [];
            foreach ($request->orders as $orderData) {
                $order = Order::create([
                    'client_id' => $orderData['client_id'],
                    'project_id' => $project->id,
                    'product_id' => $orderData['product_id'],
                    'quantity' => $orderData['quantity'] ?? 1,
                    'deadline' => $orderData['deadline'] ?? null,
                    'price' => $orderData['price'] ?? null,
                    // Stages will be assigned automatically based on product configuration
                ]);

                // Обрабатываем назначения для заказа
                if (isset($orderData['assignments']) && is_array($orderData['assignments'])) {
                    foreach ($orderData['assignments'] as $assignmentData) {
                        $assignment = \App\Models\OrderAssignment::create([
                            'order_id' => $order->id,
                            'user_id' => $assignmentData['user_id'],
                            'assigned_by' => auth()->user()->id,
                            'role_type' => $assignmentData['role_type'],
                        ]);

                        // Отправляем уведомление о назначении
                        $user = $usersById->get($assignmentData['user_id']);
                        if ($user) {
                            $user->notify(new \App\Notifications\OrderAssigned($order, auth()->user()));
                        }
                    }
                }

                $orders[] = $order;
            }

            return response()->json($project->load('orders'), 201);
        } else {
            $request->validate([
                'title' => 'required|string|max:255',
                'deadline' => 'nullable|date',
                'total_price' => 'nullable|numeric|min:0',
                'payment_amount' => 'nullable|numeric|min:0',
            ]);

            $project = Project::create([
                'title' => $request->title,
                'deadline' => $request->deadline,
                'total_price' => $request->total_price,
                'payment_amount' => $request->payment_amount ?? 0,
            ]);

            return response()->json($project, 201);
        }
    }

    public function allProjects()
    {
        if (Gate::denies('allProjects', Project::class)) {
            abort(403, 'Доступ запрещён');
        }

        $projects = CacheService::rememberWithTags('all_projects', 1800, function () {
            // Оптимизация: выбираем только необходимые поля для уменьшения размера данных
            return Project::select('id', 'title', 'deadline', 'created_at')
                ->orderBy('id')
                ->get();
        }, [CacheService::TAG_PROJECTS]);
        return $projects;
    }

    public function update(Request $request, Project $project)
    {
        if (Gate::denies('update', $project)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'deadline' => 'nullable|date',
            'total_price' => 'nullable|numeric|min:0',
            'payment_amount' => 'nullable|numeric|min:0',
        ]);

        $project->update($data);

        return response()->json($project);
    }

    public function destroy(Project $project)
    {
        if (Gate::denies('delete', $project)) {
            abort(403, 'Доступ запрещён');
        }

        // Проверяем активные заказы в проекте
        $activeOrdersCount = $project->orders()->where('is_archived', false)->count();

        if ($activeOrdersCount > 0) {
            return response()->json([
                'message' => "Невозможно удалить проект, в котором есть {$activeOrdersCount} активных заказов"
            ], 422);
        }

        $project->delete();

        return response()->json(['message' => 'Проект удалён']);
    }
}
