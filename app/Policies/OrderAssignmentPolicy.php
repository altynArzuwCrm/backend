<?php

namespace App\Policies;

use App\Models\OrderAssignment;
use App\Models\User;

class OrderAssignmentPolicy
{
    public function before($user, $ability)
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }
    }

    public function assign(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function delete(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function viewAny(User $user)
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }

        return $user->assignedOrders()->exists();
    }

    public function view(User $user, OrderAssignment $assignment)
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }

        return $assignment->user_id === $user->id;
    }

    public function updateStatus(User $user, OrderAssignment $assignment)
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            // Админ и менеджер могут менять статус
            return true;
        }

        // Назначенный сотрудник может менять статус, но НЕ на "approved"
        if ($user->id === $assignment->user_id) {
            // Проверяем, что статус не "approved" (это проверяется в контроллере)
            return true;
        }

        return false;
    }
}
