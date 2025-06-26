<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\OrderItemAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class AssignmentService
{
    public function assignUser(OrderItem $item, User $user, User $assignedBy)
    {
        return DB::transaction(function () use($item, $user, $assignedBy) {
            $assignment = OrderItemAssignment::create([
                'order_item_id' => $item->id,
                'user_id' => $user->id,
                'assigned_by' => $assignedBy->id,
                'status' => 'pending',
                'assigned_at' => now(),
            ]);
            return $assignment;
        });
    }

    public function updateStatus(OrderItemAssignment $assignment, string $newStatus, ?User $actionBy = null)
    {
        DB::transaction(function () use ($assignment, $newStatus, $actionBy) {
          $assignment->status = $newStatus;
          $assignment->save();
        });
    }

    public function reassignUser(OrderItemAssignment $oldAssignment, User $newUser, User $assignedBy) {
        $this->updateStatus($oldAssignment, 'cancelled');

        return $this->assignUser(
          $oldAssignment->orderItem,
          $newUser,
          $assignedBy
        );
    }
}
