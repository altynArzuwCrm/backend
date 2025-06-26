<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\OrderItemAssignment;
use App\Services\AssignmentService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderItemAssignmentController extends Controller
{
    protected  AssignmentService $service;

    public function __construct(AssignmentService $service)
    {
        $this->service = $service;
    }

    public function assign(Request $request, OrderItem $orderItem)
    {
        if (Gate::denies('assign', OrderItemAssignment::class)) {
            return response()->json([
                'message' => 'Not Authorized'
            ], 403);
        }

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $assignedBy = $request->user();

        $assignment = $this->service->assignUser($orderItem, $user, $assignedBy);

        return response()->json([
           'message' => 'User assigned successfully',
           'assignment' => $assignment,
        ]);
    }

    public function updateStatus(Request $request, OrderItemAssignment $assignment)
    {
        if (Gate::denies('updateStatus', $assignment)) {
            return response()->json([
                'message' => 'Not Authorized'
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

    public function reassign(Request $request,OrderItemAssignment $assignment)
    {
        if (Gate::denies('reassign', $assignment)) {
            return response()->json([
                'message' => 'Not Authorized'
            ], 403);
        }
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $newUser = User::findOrFail($validated['user_id']);
        $assignedBy = $request->user();

        $newAssignment = $this->service->reassignUser($assignment, $newUser, $assignedBy);

        return response()->json([
           'message' => 'User reassigned successfully',
           'assignment' => $newAssignment,
        ]);
    }
}
