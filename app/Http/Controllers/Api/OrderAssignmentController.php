<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderAssignmentController extends Controller
{
    public function index(Request $request)
    {
        if (Gate::denies('viewAny', OrderAssignment::class)) {
            abort(403,'Доступ запрещён');
        }

        $user = auth()->user();
        $query = OrderAssignment::query()->with(['user', 'order', 'assignedBy']);

        if(!in_array($user->role, ['admin', 'manager'])) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
           $query->paginate(10),
        ]);
    }

    public function show(OrderAssignment $assignment)
    {
        if (Gate::denies('view', $assignment)) {
            abort(403, 'Доступ запрещён');
        }

        return response()->json($assignment->load(['user', 'order', 'assignedBy']));
    }

    public function assign(Request $request, Order $order)
    {
        if (Gate::denies('assign', OrderAssignment::class)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::findOrFail($data['user_id']);

        $allowedRoles = ['designer', 'print_operator', 'workshop_worker'];
        if (!in_array($user->role, $allowedRoles)) {
            return response()->json([
                'message' => 'User must have one of the following roles: ' . implode(', ', $allowedRoles),
            ], 422);
        }

        $assignedBy = auth()->user()->id;

        $assignment = OrderAssignment::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'assigned_by' => $assignedBy,
        ]);

        return response()->json([
            'message' => 'User assigned successfully',
            'assignment' => $assignment,
        ]);
    }

    public function updateStatus(Request $request, OrderAssignment $assignment)
    {
        if (Gate::denies('updateStatus', $assignment)) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled,under_review,approved',
        ]);

        $assignment->status = $request->status;
        $assignment->save();

        return response()->json([
            'message' => 'Статус обновлён',
            'status' => $assignment->status,
            'assignment_id' => $assignment->id
        ]);
    }

    public function destroy(OrderAssignment $assignment) {
        if (Gate::denies('delete', $assignment)) {
            return response()->json([
               'message' => 'Forbidden',
            ], 403);
        }
        if ($assignment->status == 'cancelled') {
            $assignment->delete();
        } else {
            return response()->json([
                'message' => 'You can\'t delete this assignment',
            ], 422);
        }

        return response()->json(['message' => 'Cancelled assignment deleted successfully']);
    }
} 