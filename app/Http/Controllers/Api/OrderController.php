<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        if (Gate::denies('viewAny', Order::class)) {
            abort(403, 'Доступ запрещён');
        }

        $orders = Order::with('items')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($orders);
    }

    public function show(Order $order)
    {
        if (Gate::denies('view', $order)) {
            abort(403, 'Доступ запрещён');
        }

        $order->load('items');

        return response()->json($order);
    }

    public function store(Request $request)
    {
        if (Gate::denies('create', Order::class)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'deadline' => 'nullable|date',
            'price' => 'nullable|numeric|min:0',
            'payment_amount' => 'nullable|numeric|min:0',
        ]);

        $data['stage'] = 'draft';
        $data['status'] = null;

        $order = Order::create($data);

        return response()->json($order, 201);
    }

    public function update(Request $request, Order $order)
    {
        if (Gate::denies('update', $order)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'client_id' => 'sometimes|exists:clients,id',
            'deadline' => 'nullable|date',
            'price' => 'nullable|numeric|min:0',
            'payment_amount' => 'nullable|numeric|min:0',
        ]);

        $order->update($data);

        return response()->json($order);
    }

    public function destroy(Order $order)
    {
        if (Gate::denies('delete', $order)) {
            abort(403, 'Доступ запрещён');
        }

        $order->delete();

        return response()->json(['message' => 'Заказ удалён']);
    }

    public function updateStatus(Request $request, Order $order)
    {
        if (Gate::denies('updateStatus', $order)) {
            abort(403, 'Доступ запрещён');
        }

        $request->validate([
            'status' => 'required|in:draft,design,print,workshop,final,archived',
        ]);

        $order->status = $request->status;
        $order->save();

        return response()->json([
            'message' => 'Статус обновлён',
            'status' => $order->status,
            'assignment_id' => $order->id
        ]);
    }

    public function markAsCancelled(Order $order)
    {
        if (Gate::denies('update', $order)) {
            abort(403, 'Доступ запрещён');
        }

        $this->authorize('update', $order);

        $order->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Статус заказа установлен как "cancelled"']);
    }

    public function markAsCompleted(Order $order)
    {
        if (Gate::denies('update', $order)) {
            abort(403, 'Доступ запрещён');
        }

        $this->authorize('update', $order);

        $order->update(['status' => 'completed']);

        return response()->json(['message' => 'Статус заказа установлен как "completed"']);
    }

    public function cancelOrder(Request $request, Order $order)
    {
        if (Gate::denies('update', $order)) {
            abort(403, 'Доступ запрещён');
        }
        $request->validate([
            'reasons' => 'array',
            'reasons.*.order_item_id' => 'required|exists:order_items,id',
            'reasons.*.reason_id' => 'required|exists:reasons,id',
        ]);

        $order->update(['status' => 'cancelled']);

        foreach ($request->input('reasons') as $reasonData) {
            $item = $order->items()->find($reasonData['order_item_id']);
            if ($item) {
                $item->update(['reason_id' => $reasonData['reason_id']]);
            }
        }

        return response()->json(['message' => 'Order cancelled with reasons']);
    }
}
