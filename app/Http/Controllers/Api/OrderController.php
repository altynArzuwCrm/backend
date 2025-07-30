<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\OrderStatusLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        if (Gate::denies('viewAny', Order::class)) {
            abort(403, 'Доступ запрещён');
        }
        $user = request()->user();

        $query = Order::with(['project', 'product', 'client']);

        if (!$user->hasAnyRole(['admin', 'manager'])) {
            $assignedOrderIds = OrderAssignment::query()
                ->where('user_id', $user->id)
                ->pluck('order_id');

            $query->whereIn('id', $assignedOrderIds);
        }

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

        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $allowedPerPage = [10, 20, 50, 100, 200, 500];
        $perPage = (int) $request->input('per_page', 30);
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 30;
        }
        return response()->json($query->paginate($perPage));
    }

    public function show(Order $order)
    {
        if (Gate::denies('view', $order)) {
            abort(403, 'Доступ запрещён');
        }

        $order->load(['project', 'product', 'client', 'stage', 'assignments.user.roles', 'assignments.assignedStages']);

        return response()->json($order);
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
        if ($product && $product->hasStage('design')) {
            $data['stage'] = 'design';
        } else {
            // Get the first available stage for this product
            $availableStages = $product ? $product->getAvailableStages() : \App\Models\Stage::active()->ordered()->get();
            $initialStage = $availableStages->where('is_initial', true)->first();
            $data['stage'] = $initialStage ? $initialStage->name : 'draft';
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

        // Обрабатываем назначения, если они есть
        if (isset($data['assignments']) && is_array($data['assignments'])) {
            // Получаем список выбранных стадий
            $selectedStages = isset($data['stages']) ? $data['stages'] : [];

            foreach ($data['assignments'] as $assignmentData) {
                // Проверяем, что назначение создается только для выбранных стадий
                if (isset($assignmentData['stage_id']) && !empty($selectedStages)) {
                    if (!in_array($assignmentData['stage_id'], $selectedStages)) {
                        \Log::warning("Attempted to create assignment for non-selected stage: {$assignmentData['stage_id']}");
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
                    $stage = \App\Models\Stage::find($assignmentData['stage_id']);
                    if ($stage) {
                        $assignment->assignToStage($stage->name);
                    }
                }
            }
        }

        return response()->json($order->load('assignments.user'), 201);
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

        $order->update($data);

        // Обрабатываем назначения, если они есть
        if (isset($data['assignments']) && is_array($data['assignments'])) {
            // Удаляем существующие назначения
            $order->assignments()->delete();

            // Получаем список выбранных стадий
            $selectedStages = isset($data['stages']) ? $data['stages'] : [];

            // Создаем новые назначения
            foreach ($data['assignments'] as $assignmentData) {
                // Проверяем, что назначение создается только для выбранных стадий
                if (isset($assignmentData['stage_id']) && !empty($selectedStages)) {
                    if (!in_array($assignmentData['stage_id'], $selectedStages)) {
                        Log::warning("Attempted to create assignment for non-selected stage: {$assignmentData['stage_id']}");
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
                    $stage = \App\Models\Stage::find($assignmentData['stage_id']);
                    if ($stage) {
                        $assignment->assignToStage($stage->name);
                    }
                }
            }
        }

        return response()->json($order->load('assignments.user'));
    }

    public function updateStage(Request $request, Order $order)
    {
        if (Gate::denies('updateStatus', $order)) {
            abort(403, 'Доступ запрещён');
        }

        $request->validate([
            'stage' => 'required|string',
        ]);

        $oldStage = $order->stage;
        $newStageName = $request->stage;

        // Логируем для отладки
        \Log::info('Обновление стадии заказа', [
            'order_id' => $order->id,
            'old_stage' => $oldStage ? $oldStage->name : 'null',
            'new_stage' => $newStageName
        ]);
        $product = $order->product;

        // Находим стадию по имени
        $stage = \App\Models\Stage::with('roles')->where('name', $newStageName)->first();
        if (!$stage) {
            abort(422, 'Стадия не найдена');
        }

        if ($stage && $product) {
            $rolesForStage = $stage->roles()->wherePivot('auto_assign', true)->get();

            foreach ($rolesForStage as $role) {
                $roleType = $role->name;

                $existingAssignments = $order->assignments()
                    ->whereHas('user.roles', function ($q) use ($roleType) {
                        $q->where('name', $roleType);
                    })
                    ->get();

                if ($existingAssignments->isEmpty()) {
                    $this->assignDefaultUsersToOrder($order, $product, $roleType, $request->user());
                }
            }
        }

        if ($newStageName == 'cancelled') {
            $request->validate([
                'reason' => 'required|string',
                'reason_status' => 'required|in:refused,not_responding,defective_product'
            ]);
            $order->reason = $request->reason;
            $order->reason_status = $request->reason_status;
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

        // Создаем запись в логе изменений статуса
        \App\Models\OrderStatusLog::create([
            'order_id' => $order->id,
            'from_status' => $oldStage ? $oldStage->name : null,
            'to_status' => $order->stage->name,
            'user_id' => $request->user()->id,
            'changed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Статус обновлён',
            'stage' => $order->stage->name,
            'order_id' => $order->id,
            'order' => $order->load(['project', 'product', 'client', 'assignments.user', 'stage'])
        ]);
    }

    public function destroy(Order $order)
    {
        if (Gate::denies('delete', $order)) {
            abort(403, 'Доступ запрещён');
        }

        $order->delete();

        return response()->json(['message' => 'Заказ удалён']);
    }

    public function statusLogs(Order $order)
    {
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
}
