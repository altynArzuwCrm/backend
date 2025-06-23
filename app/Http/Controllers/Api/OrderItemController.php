<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', OrderItem::class);

        $query = OrderItem::with(['order', 'product', 'designer', 'printer', 'workshopWorker', 'manager']);

        if ($request->order_id) {
            $query->where('order_id', $request->order_id);
        }

        return response()->json($query->paginate(20));
    }

    public function show(OrderItem $orderItem)
    {
        $this->authorize('view', $orderItem);

        $orderItem->load(['order', 'product', 'designer', 'manager']);

        return response()->json($orderItem);
    }

    public function store(Request $request)
    {
        $this->authorize('create', OrderItem::class);

        $data = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'designer_id' => 'nullable|exists:users,id',
            'printer_id' => 'nullable|exists:users,id',
            'workshop_worker_id' => 'nullable|exists:users,id',
            'manager_id' => 'nullable|exists:users,id',
            'individual_deadline' => 'nullable|date',
            'status' => 'required|in:ожидание,в_работе,завершено,отменено',
        ]);

        $orderItem = OrderItem::create($data);

        return response()->json($orderItem, 201);
    }

    public function update(Request $request, OrderItem $orderItem)
    {
        $this->authorize('update', $orderItem);

        $user = $request->user();

        $data = $request->validate([
            'designer_id' => 'nullable|exists:users,id',
            'printer_id' => 'nullable|exists:users,id',
            'workshop_worker_id' => 'nullable|exists:users,id',
            'individual_deadline' => 'nullable|date|before_or_equal:' . $orderItem->order->deadline,
            'status' => 'nullable|in:ожидание,в_работе,завершено,отменено',
            'quantity' => 'nullable|integer|min:1',
        ]);

        if (isset($data['designer_id'])) {
            $designer = User::find($data['designer_id']);
            if ($designer->role !== 'designer') {
                return response()->json(['error' => 'Назначенный пользователь не является дизайнером'], 422);
            }
            $orderItem->designer_id = $data['designer_id'];
            $orderItem->assigned_at = now();
        }
        if (isset($data['printer_id'])) {
            $printer = User::find($data['printer_id']);
            if ($printer->role !== 'printer') {
                return response()->json(['error' => 'Назначенный пользователь не является оператором печати'], 422);
            }
            $orderItem->printer_id = $data['printer_id'];
            $orderItem->assigned_at = now();
        }
        if (isset($data['workshop_worker_id'])) {
            $worker = User::find($data['workshop_worker_id']);
            if ($worker->role !== 'worker') {
                return response()->json(['error' => 'Назначенный пользователь не является сотрудником цеха'], 422);
            }
            $orderItem->workshop_worker_id = $data['workshop_worker_id'];
            $orderItem->assigned_at = now();
        }

        if (isset($data['status']) && $data['status'] !== $orderItem->status) {
            $orderItem->status = $data['status'];
            if ($data['status'] === 'в_работе') {
                $orderItem->started_at = now();
            } elseif ($data['status'] === 'завершено') {
                $orderItem->completed_at = now();
            }
        }

        if (isset($data['individual_deadline'])) {
            $orderItem->individual_deadline = $data['individual_deadline'];
        }

        if (isset($data['quantity'])) {
            $orderItem->quantity = $data['quantity'];
        }

        $orderItem->save();

        $this->checkAndUpdateOrderStatus($orderItem->order);

        return response()->json($orderItem);
    }

    protected function checkAndUpdateOrderStatus(Order $order)
    {
        $items = $order->items;

        if ($items->every(fn($item) => $item->status === 'завершено')) {
            $order->status = 'завершено';
            $order->completed_at = now();
            $order->save();
            return;
        }

        if ($items->contains(fn($item) => $item->status === 'в_работе')) {
            $order->status = 'в_работе';
            $order->save();
            return;
        }

        if ($items->every(fn($item) => $item->status === 'ожидание')) {
            $order->status = 'ожидание';
            $order->save();
            return;
        }

        if ($items->contains(fn($item) => $item->status === 'отменено') && !$items->contains(fn($item) => $item->status === 'в_работе')) {
            $order->status = 'отменено';
            $order->save();
            return;
        }
    }

    public function destroy(OrderItem $orderItem)
    {
        $this->authorize('delete', $orderItem);

        $orderItem->delete();
        return response()->json(['message' => 'Позиция заказа удалена']);
    }
}
