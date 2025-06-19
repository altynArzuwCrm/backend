<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    public function view(User $user, Order $order)
    {
        return in_array($user->role, ['admin', 'manager']) ||
            ($user->role === 'executor' && $order->executor_id === $user->id);
    }

    public function viewAny(User $user)
    {
        return in_array($user->role, ['admin', 'manager', 'executor']);
    }

    public function create(User $user)
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function update(User $user, Order $order)
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function updateStatus(User $user, Order $order)
    {
        return $user->role === 'executor' && $order->executor_id === $user->id;
    }

    public function delete(User $user, Order $order)
    {
        return in_array($user->role, ['admin', 'manager']);
    }

}
