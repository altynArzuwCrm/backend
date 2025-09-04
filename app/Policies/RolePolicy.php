<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdminOrManager() || $user->isStaff(); // Администраторы, менеджеры и сотрудники могут просматривать роли
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Role $role): bool
    {
        // Admin can view all roles
        if ($user->hasRole('admin')) {
            return true;
        }

        // Manager can view all roles except admin role
        if ($user->hasRole('manager')) {
            return $role->name !== 'admin';
        }

        // Power user can view all roles except admin and manager roles
        if ($user->hasRole('power_user')) {
            return !in_array($role->name, ['admin', 'manager']);
        }

        // Сотрудники могут видеть роли для работы с заказами
        if ($user->isStaff()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin'); // Только администраторы могут создавать роли
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Role $role): bool
    {
        // Only admin can update roles
        if ($user->hasRole('admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Role $role): bool
    {
        // Only admin can delete roles
        if ($user->hasRole('admin')) {
            return $role->name !== 'admin';
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Role $role): bool
    {
        return $user->hasRole('admin'); // Только администраторы могут восстанавливать роли
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Role $role): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can assign roles.
     */
    public function assignRoles(User $user): bool
    {
        return $user->hasRole('admin'); // Только администраторы могут назначать роли
    }

    /**
     * Determine whether the user can manage role permissions.
     */
    public function manageRolePermissions(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Determine whether the user can view role statistics.
     */
    public function viewRoleStatistics(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can export roles.
     */
    public function exportRoles(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view role analytics.
     */
    public function viewRoleAnalytics(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can manage role colors.
     */
    public function manageRoleColors(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Determine whether the user can access role management features.
     */
    public function accessRoleManagement(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view role history.
     */
    public function viewRoleHistory(User $user, Role $role): bool
    {
        // Admin can view all role history
        if ($user->hasRole('admin')) {
            return true;
        }

        // Manager can view role history except admin role
        if ($user->hasRole('manager')) {
            return $role->name !== 'admin';
        }

        // Power user can view role history except admin and manager roles
        if ($user->hasRole('power_user')) {
            return !in_array($role->name, ['admin', 'manager']);
        }

        return false;
    }

    /**
     * Determine whether the user can manage role users.
     */
    public function manageRoleUsers(User $user, Role $role): bool
    {
        // Admin can manage users for all roles
        if ($user->hasRole('admin')) {
            return true;
        }

        // Manager can manage users for roles except admin role
        if ($user->hasRole('manager')) {
            return $role->name !== 'admin';
        }

        return false;
    }

    /**
     * Determine whether the user can view role users.
     */
    public function viewRoleUsers(User $user, Role $role): bool
    {
        // Admin can view users for all roles
        if ($user->hasRole('admin')) {
            return true;
        }

        // Manager can view users for roles except admin role
        if ($user->hasRole('manager')) {
            return $role->name !== 'admin';
        }

        // Power user can view users for roles except admin and manager roles
        if ($user->hasRole('power_user')) {
            return !in_array($role->name, ['admin', 'manager']);
        }

        return false;
    }

    /**
     * Determine whether the user can manage role hierarchy.
     */
    public function manageRoleHierarchy(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Determine whether the user can manage role inheritance.
     */
    public function manageRoleInheritance(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }
}
