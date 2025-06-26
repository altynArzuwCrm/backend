<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderItemController extends Controller
{
    public function index(Request $request)
    {
        if (Gate::denies('viewAny', OrderItem::class)) {
            abort(403, 'Доступ запрещён');
        }

        $query = OrderItem::with(['order', 'product', 'manager', 'reason']);

        if ($request->order_id) {
            $query->where('order_id', $request->order_id);
        }

        return response()->json($query->paginate(20));
    }

    public function show(OrderItem $orderItem)
    {
        if (Gate::denies('view', $orderItem)) {
            abort(403, 'Доступ запрещён');
        }

        $orderItem->load(['order', 'product', 'manager', 'reason']);

        return response()->json($orderItem);
    }

    public function store(Request $request)
    {
        if (Gate::denies('create', OrderItem::class)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'deadline' => ['nullable', 'date'],
            'reason_id' => ['nullable', 'exists:reasons,id'],
        ]);

        $orderItem = OrderItem::create($data);

        return response()->json($orderItem, 201);
    }

    public function update(Request $request, OrderItem $orderItem)
    {
        if (Gate::denies('update', $orderItem)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'deadline' => ['nullable', 'date'],
            'reason_id' => ['nullable', 'exists:reasons,id'],
        ]);

        $orderItem->update($data);

        return response()->json($orderItem);
    }


    public function updateStatus(Request $request, OrderItem $orderItem)
    {
        if (Gate::denies('updateStatus', $orderItem)) {
            abort(403, 'Доступ запрещён');
        }
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled,under_review',
        ]);

        $orderItem->status = $request->status;
        $orderItem->save();

        return response()->json([
            'message' => 'Статус обновлён',
            'status' => $orderItem->status,
            'assignment_id' => $orderItem->id
            ]);
    }


    public function destroy(OrderItem $orderItem)
    {
        if (Gate::denies('delete', $orderItem)) {
            abort(403, 'Доступ запрещён');
        }

        $orderItem->delete();

        return response()->json(['message' => 'Позиция заказа удалена']);
    }
}
