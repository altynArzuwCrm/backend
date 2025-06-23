<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['Админ', 'Менеджер']);
    }

    public function view(User $user, Order $order): bool
    {
        return $user->role === 'Админ'
            || $user->role === 'Менеджер'
            || $order->items()->where('designer_id', $user->id)->exists()
            || $order->items()->where('printer_id', $user->id)->exists()
            || $order->items()->where('workshop_worker_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['Админ', 'Менеджер']);
    }

    public function update(User $user, Order $order): bool
    {
        return in_array($user->role, ['Админ', 'Менеджер']);
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->role === 'Админ';
    }

    public function moveToNextStage(User $user, Order $order): bool
    {
        return in_array($user->role, ['Админ', 'Менеджер']);
    }

    public function finalize(User $user, Order $order): bool
    {
        return in_array($user->role, ['Админ', 'Менеджер']);
    }
}
