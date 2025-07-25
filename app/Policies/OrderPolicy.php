<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function before($user, $ability)
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }
    }

    public function viewAny(User $user)
    {
        return $user->assignedOrders()->exists();
    }

    public function view(User $user, Order $order)
    {
        return $order->assignments()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function updateStatus(User $user, Order $order)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function create(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function update(User $user, Order $order)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function delete(User $user, Order $order)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }
}
