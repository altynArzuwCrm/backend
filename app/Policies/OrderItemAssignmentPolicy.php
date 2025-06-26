<?php

namespace App\Policies;

use App\Models\OrderItemAssignment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderItemAssignmentPolicy
{
    public function assign(User $user)
    {
        return $user->role === [1,2];
    }

    public function reassign(User $user, OrderItemAssignment $assignment)
    {
        return $user->role === [1,2];
    }

    public function updateStatus(User $user, OrderItemAssignment $assignment)
    {
        return $assignment->user_id === $user->id;
    }
}
