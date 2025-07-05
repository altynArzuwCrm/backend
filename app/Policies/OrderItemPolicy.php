<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderItemPolicy
{
    use HandlesAuthorization;


    public function viewAny(User $user)
    {
        if (in_array($user->role, ['admin', 'manager'])) {
            return true;
        }

        return $user->assignedOrderItems()->exists();
    }

    public function view(User $user, OrderItem $orderItem)
    {
        if (in_array($user->role, ['admin', 'manager'])) {
            return true;
        }

        return $orderItem->assignments()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function updateStatus(User $user, OrderItem $orderItem)
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function create(User $user)
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function update(User $user, OrderItem $orderItem)
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function delete(User $user, OrderItem $orderItem)
    {
        return in_array($user->role, ['admin', 'manager']);
    }
}
