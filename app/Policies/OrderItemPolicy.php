<?php

namespace App\Policies;

use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class OrderItemPolicy
{
    use HandlesAuthorization;

    protected function role(User $user)
    {
        return $user->role?->name;
    }

    public function viewAny(User $user)
    {
        return in_array($this->role($user), ['Админ', 'Менеджер']);
    }

    public function view(User $user, OrderItem $orderItem)
    {
        return match ($this->role($user)) {
            'Админ', 'Менеджер' => true,
            'Дизайнер' => $orderItem->designer_id === $user->id,
            'Оператор печати' => $orderItem->printer_id === $user->id,
            'Сотрудник цеха' => $orderItem->workshop_worker_id === $user->id,
            default => false,
        };
    }

    public function create(User $user)
    {
        return in_array($this->role($user), ['Админ', 'Менеджер']);
    }

    public function update(User $user, OrderItem $orderItem)
    {
        return match ($this->role($user)) {
            'Админ', 'Менеджер' => true,
            'Дизайнер' => $orderItem->designer_id === $user->id,
            'Оператор печати' => $orderItem->printer_id === $user->id,
            'Сотрудник цеха' => $orderItem->workshop_worker_id === $user->id,
            default => false,
        };
    }

    public function delete(User $user, OrderItem $orderItem)
    {
        return in_array($this->role($user), ['Админ', 'Менеджер']);
    }
}
