<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;

class OrderPolicy
{
    protected function roleId(User $user)
    {
        return $user->role_id;
    }

    public function viewAny(User $user)
    {
        return in_array($this->roleId($user), [1, 2]);
    }

    public function view(User $user, Order $order)
    {
        $stageAccessMap = [
            'design'    => 3,
            'print'     => 4,
            'workshop'  => 5,
            'finalize'  => [1, 2],
            'cancelled' => [1, 2],
            'archived'  => [1, 2],
        ];

        $roleId = $user->role_id;
        $stage = $order->stage;

        if (!isset($stageAccessMap[$stage])) {
            return $roleId === 1;
        }

        $allowedRoles = (array) $stageAccessMap[$stage];

        return in_array($roleId, $allowedRoles);
    }

    public function updateStatus(User $user, OrderItem $orderItem)
    {
        return $orderItem->user_id->role === [1,2];
    }

    public function create(User $user)
    {
        return in_array($this->roleId($user), [1, 2]);
    }

    public function update(User $user, Order $order)
    {
        return in_array($this->roleId($user), [1, 2]);
    }

    public function delete(User $user, Order $order)
    {
        return $this->roleId($user) === [1,2];
    }

    public function cancel(User $user, Order $order)
    {
        return in_array($this->roleId($user), [1, 2]);
    }
}
