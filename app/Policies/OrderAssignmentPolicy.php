<?php

namespace App\Policies;

use App\Models\OrderAssignment;
use App\Models\User;

class OrderAssignmentPolicy
{
    public function assign(User $user)
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function delete(User $user)
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function viewAny(User $user)
    {
        if(in_array($user->role, ['admin', 'manager']))
        {
            return true;
        }

        return $user->assignedOrders()->exists();
    }

    public function view(User $user, OrderAssignment $assignment)
    {
        if (in_array($user->role, ['admin', 'manager'])) {
            return true;
        }

        return $assignment->user_id === $user->id;
    }

    public function updateStatus(User $user, OrderAssignment $assignment)
    {
        if (in_array($user->role, ['admin', 'manager'])) {
            return true;
        }

        return $user->id === $assignment->user_id;
    }
}
