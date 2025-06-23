<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::with('items')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($orders);
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $order->load('items');

        return response()->json($order);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Order::class);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'client_name' => 'nullable|string|max:255',
            'client_phone' => 'nullable|string|max:255',
            'deadline' => 'nullable|date',
            'price' => 'nullable|numeric|min:0',
            'payment_amount' => 'nullable|numeric|min:0',
        ]);

        $data['status'] = 'draft';

        $order = Order::create($data);

        return response()->json($order, 201);
    }

    public function update(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'client_name' => 'nullable|string|max:255',
            'client_phone' => 'nullable|string|max:255',
            'deadline' => 'nullable|date',
            'price' => 'nullable|numeric|min:0',
            'payment_amount' => 'nullable|numeric|min:0',
            'is_completed' => 'boolean',
            'status' => 'in:draft,design,print,workshop,final,archived',
        ]);

        if (isset($data['status']) && $data['status'] !== $order->status) {
            $this->handleStatusTransition($order, $data['status']);
        }

        $order->update($data);

        return response()->json($order);
    }

    protected function handleStatusTransition(Order $order, string $newStatus)
    {
        $validTransitions = [
            'draft' => ['design'],
            'design' => ['print', 'workshop'],
            'print' => ['workshop', 'final'],
            'workshop' => ['final'],
            'final' => ['archived'],
        ];

        $current = $order->status;

        if (isset($validTransitions[$current]) && in_array($newStatus, $validTransitions[$current])) {
            if ($newStatus === 'final') {
                if (!$order->is_completed || $order->price > $order->payment_amount) {
                    abort(422, 'Заказ не может быть завершён: проверьте выполнение и оплату.');
                }
                $order->finalized_at = now();
            }

            if ($newStatus === 'archived') {
                $order->finalized_at = now();
            }
        } else {
            abort(422, 'Недопустимый переход между статусами.');
        }
    }

    public function destroy(Order $order)
    {
        $this->authorize('delete', $order);

        $order->delete();

        return response()->json(['message' => 'Заказ удалён']);
    }
}
