<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClientPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Разрешаем доступ всем аутентифицированным пользователям
        // так как клиенты нужны для работы с заказами
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Client $client): bool
    {
        // Разрешаем доступ всем аутентифицированным пользователям
        // так как клиенты нужны для работы с заказами
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasElevatedPermissions();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Client $client): bool
    {
        return $user->hasElevatedPermissions();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Client $client): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Client $client): bool
    {
        return $user->isAdminOrManager();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Client $client): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can export clients.
     */
    public function exportClients(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view client analytics.
     */
    public function viewClientAnalytics(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can manage client categories.
     */
    public function manageClientCategories(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view client statistics.
     */
    public function viewClientStatistics(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can access client management features.
     */
    public function accessClientManagement(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view all clients (for dropdowns).
     */
    public function allClients(User $user): bool
    {
        // Разрешаем доступ всем аутентифицированным пользователям
        // так как клиенты нужны для работы с заказами
        return true;
    }

    /**
     * Determine whether the user can view client history.
     */
    public function viewClientHistory(User $user, Client $client): bool
    {
        // Admin can view all client history
        if ($user->hasRole('admin')) {
            return true;
        }

        // Manager can view all client history
        if ($user->hasRole('manager')) {
            return true;
        }

        // Power user can view all client history
        if ($user->hasRole('power_user')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage client contacts.
     */
    public function manageClientContacts(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view client contacts.
     */
    public function viewClientContacts(User $user, Client $client): bool
    {
        // Admin can view all client contacts
        if ($user->hasRole('admin')) {
            return true;
        }

        // Manager can view all client contacts
        if ($user->hasRole('manager')) {
            return true;
        }

        // Power user can view all client contacts
        if ($user->hasRole('power_user')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage client projects.
     */
    public function manageClientProjects(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view client projects.
     */
    public function viewClientProjects(User $user, Client $client): bool
    {
        // Admin can view all client projects
        if ($user->hasRole('admin')) {
            return true;
        }

        // Manager can view all client projects
        if ($user->hasRole('manager')) {
            return true;
        }

        // Power user can view all client projects
        if ($user->hasRole('power_user')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage client orders.
     */
    public function manageClientOrders(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view client orders.
     */
    public function viewClientOrders(User $user, Client $client): bool
    {
        // Admin can view all client orders
        if ($user->hasRole('admin')) {
            return true;
        }

        // Manager can view all client orders
        if ($user->hasRole('manager')) {
            return true;
        }

        // Power user can view all client orders
        if ($user->hasRole('power_user')) {
            return true;
        }

        return false;
    }
}
