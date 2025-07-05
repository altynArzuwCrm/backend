<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\OrderStatusLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        if (Gate::denies('viewAny', Order::class)) {
            abort(403, 'Доступ запрещён');
        }
        $user = auth()->user();

        $query = Order::with(['project', 'product', 'manager']);

        if (!in_array($user->role, ['admin', 'manager'])) {
            $assignedOrderIds = OrderAssignment::query()
                ->where('user_id', $user->id)
                ->pluck('order_id');

            $query->whereIn('id', $assignedOrderIds);
        }

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('stage')) {
            $query->where('stage', $request->stage);
        }

        return response()->json($query->paginate(20));
    }

    public function show(Order $order)
    {
        if (Gate::denies('view', $order)) {
            abort(403, 'Доступ запрещён');
        }

        $order->load(['project', 'product', 'manager']);

        return response()->json($order);
    }

    public function store(Request $request)
    {
        if (Gate::denies('create', Order::class)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'deadline' => ['nullable', 'date'],
            'price' => 'nullable|numeric|min:0',
        ]);

        $order = Order::create($data);

        return response()->json($order, 201);
    }

    public function update(Request $request, Order $order)
    {
        if (Gate::denies('update', $order)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'deadline' => ['nullable', 'date'],
            'price' => 'nullable|numeric|min:0',
        ]);

        $order->update($data);

        return response()->json($order);
    }

    public function updateStage(Request $request, Order $order)
    {
        if (Gate::denies('updateStatus', $order)) {
            abort(403, 'Доступ запрещён');
        }

        $request->validate([
            'stage' => 'required|in:draft,design,print,workshop,final,archived,completed,cancelled'
        ]);

        $oldStatus = $order->stage;
        $order->stage = $request->stage;

        if ($request->stage == 'cancelled') {
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

        $order->save();

        OrderStatusLog::create([
            'order_id' => $order->id,
            'from_status' => $oldStatus,
            'to_status' => $order->stage,
            'user_id' => auth()->id(),
            'changed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Статус обновлён',
            'stage' => $order->stage,
            'order_id' => $order->id
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
}
