<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        if (Gate::denies('viewAny', Order::class)) {abort(403);}

        $user = Auth::user();

        if (in_array($user->role, ['admin', 'manager'])) {
            $orders = Order::with(['client', 'stage', 'manager', 'executor'])->get();
        } elseif ($user->role === 'executor') {
            $orders = Order::with(['client', 'stage', 'manager', 'executor'])
                ->where('executor_id', $user->id)
                ->get();
        }

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        if (Gate::denies('create', Order::class)) {abort(403);}

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'stage_id' => 'required|exists:stages,id',
            'manager_id' => 'required|exists:users,id',
            'executor_id' => 'required|exists:users,id',
            'status' => 'nullable|in:cannot_be_done,needs_revision,cancelled,done',
        ]);

        $data['status'] = $request->input('status', 'needs_revision');

        $order = Order::create($data);

        return response()->json($order, 201);
    }


    public function show($id)
    {
        $order = Order::with(['client', 'stage', 'manager', 'executor'])->findOrFail($id);

        if (Gate::denies('view', $order)) {abort(403);}

        return response()->json($order);
    }

    public function update(Request $request, Order $order)
    {
        $user = auth()->user();

        if (in_array($user->role, ['admin', 'manager'])) {
            if (Gate::denies('update', $order)) {abort(403);}

            $data = $request->validate([
                'title' => 'required|string|max:255',
                'client_id' => 'required|exists:clients,id',
                'stage_id' => 'required|exists:stages,id',
                'manager_id' => 'required|exists:users,id',
                'executor_id' => 'required|exists:users,id',
                'status' => 'required|in:cannot_be_done,needs_revision,cancelled,done',
            ]);

            $order->update($data);

        } elseif ($user->role == 'executor') {
            if (Gate::denies('updateStatus', $order)) {abort(403);}

            $data = $request->validate([
                'status' => 'required|in:cannot_be_done,needs_revision,cancelled,done',
            ]);

            $order->status = $data['status'];
            $order->save();
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($order);
    }


    public function destroy($id)
    {
        $order = Order::findOrFail($id);

        if (Gate::denies('delete', $order)) {abort(403);}

        $order->delete();

        return response()->json(['message' => 'Order deleted']);
    }
}
