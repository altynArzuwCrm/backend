<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasElevatedPermissions() || $user->isStaff();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Админы видят всех пользователей
        if ($user->hasRole('admin')) {
            return true;
        }

        // Менеджеры видят всех пользователей кроме админов
        if ($user->hasRole('manager')) {
            return !$model->hasRole('admin');
        }

        // Power user видят всех пользователей кроме админов и менеджеров
        if ($user->hasRole('power_user')) {
            return !$model->hasAnyRole(['admin', 'manager']);
        }

        // Сотрудники могут видеть пользователей для работы с заказами
        if ($user->isStaff()) {
            return true;
        }

        // Обычные пользователи видят только себя
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Only admin can update users
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Only admin can delete users
        if ($user->hasRole('admin')) {
            return $user->id !== $model->id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can manage roles.
     */
    public function manageRoles(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Determine whether the user can assign roles.
     */
    public function assignRoles(User $user, User $model): bool
    {
        // Admin can assign any role to anyone
        if ($user->hasRole('admin')) {
            return true;
        }

        // Manager can assign roles except admin role
        if ($user->hasRole('manager')) {
            return !$model->hasRole('admin');
        }

        return false;
    }

    /**
     * Determine whether the user can view user statistics.
     */
    public function viewUserStatistics(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can export user data.
     */
    public function exportUserData(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can bulk update users.
     */
    public function bulkUpdateUsers(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view user activity.
     */
    public function viewUserActivity(User $user, User $model): bool
    {
        // Admin can view all user activity
        if ($user->hasRole('admin')) {
            return true;
        }

        // Manager can view activity of non-admin users
        if ($user->hasRole('manager')) {
            return !$model->hasRole('admin');
        }

        // Power user can view activity of regular users
        if ($user->hasRole('power_user')) {
            return !$model->hasAnyRole(['admin', 'manager']);
        }

        // Regular users can only view their own activity
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can manage user permissions.
     */
    public function manageUserPermissions(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Determine whether the user can access user management features.
     */
    public function accessUserManagement(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }
}
