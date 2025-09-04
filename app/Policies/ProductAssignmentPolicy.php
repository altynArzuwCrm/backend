<?php

namespace App\Policies;

use App\Models\ProductAssignment;
use App\Models\User;

class OrderItemAssignmentPolicy
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
        return $user->hasRole('admin');
    }

    public function viewAny(User $user)
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }

        return false;
    }

    public function view(User $user, ProductAssignment $assignment)
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }

        return $assignment->user_id === $user->id;
    }

    public function updateStatus(User $user, ProductAssignment $assignment)
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }

        // Назначенный сотрудник может менять статус своего назначения
        return $user->id === $assignment->user_id;
    }
}
