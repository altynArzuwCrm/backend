<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderItemPolicy
{
    use HandlesAuthorization;

    protected function roleId(User $user): int|null
    {
        return $user->role_id;
    }

    public function viewAny(User $user): bool
    {
        return in_array($this->roleId($user), [1, 2]);
    }

    public function view(User $user, OrderItem $orderItem): bool
    {
        $roleId = $this->roleId($user);

        if (in_array($roleId, [1, 2])) {
            return true;
        }

        return $orderItem->assignments()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function updateStatus(User $user, OrderItem $orderItem)
    {
        return $orderItem->user_id->role === [1,2];
    }

    public function create(User $user): bool
    {
        return in_array($this->roleId($user), [1, 2]);
    }

    public function update(User $user, OrderItem $orderItem): bool
    {
        return in_array($this->roleId($user), [1, 2]);
    }

    public function delete(User $user, OrderItem $orderItem): bool
    {
        return in_array($this->roleId($user), [1, 2]);
    }
}
