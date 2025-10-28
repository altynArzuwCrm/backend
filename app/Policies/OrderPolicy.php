<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class OrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Админы, менеджеры и power_user видят все заказы
        if ($user->hasElevatedPermissions()) {
            return true;
        }

        // Сотрудники видят только свои заказы (проверяется в view)
        return $user->isStaff();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order): bool
    {
        // Убрано подробное отладочное логирование политики доступа

        // Админы, менеджеры и power_user видят все заказы
        if ($user->hasElevatedPermissions()) {
            return true;
        }

        // Сотрудники видят только заказы, где они назначены
        if ($user->isStaff()) {
            $hasAssignment = $order->assignments()->where('user_id', $user->id)->exists();
            return $hasAssignment;
        }

        return false;
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
    public function update(User $user, Order $order): bool
    {
        // Админы, менеджеры и power_user могут обновлять все заказы
        if ($user->hasElevatedPermissions()) {
            return true;
        }

        // Сотрудники НЕ могут редактировать заказы
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Order $order): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Order $order): bool
    {
        return $user->isAdminOrManager();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        return $user->hasElevatedPermissions();
    }

    /**
     * Determine whether the user can assign orders.
     */
    public function assignOrder(User $user): bool
    {
        return $user->hasElevatedPermissions();
    }

    /**
     * Determine whether the user can bulk assign orders.
     */
    public function bulkAssignOrders(User $user): bool
    {
        return $user->hasElevatedPermissions();
    }

    /**
     * Determine whether the user can change order status.
     */
    public function changeOrderStatus(User $user, Order $order): bool
    {
        // Admin can change any order status
        if ($user->hasRole('admin')) {
            return true;
        }

        // Manager can change any order status
        if ($user->hasRole('manager')) {
            return true;
        }

        // Power user can change any order status
        if ($user->hasRole('power_user')) {
            return true;
        }

        // Сотрудники НЕ могут менять стадии заказов
        return false;
    }

    /**
     * Determine whether the user can view order history.
     */
    public function viewOrderHistory(User $user, Order $order): bool
    {
        // Admin can view all order history
        if ($user->hasRole('admin')) {
            return true;
        }

        // Manager can view all order history
        if ($user->hasRole('manager')) {
            return true;
        }

        // Power user can view all order history
        if ($user->hasRole('power_user')) {
            return true;
        }

        // Сотрудники могут видеть историю только заказов, где они назначены
        if ($user->isStaff()) {
            return $order->assignments()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can view order comments.
     */
    public function viewOrderComments(User $user, Order $order): bool
    {
        // Admin can view all order comments
        if ($user->hasRole('admin')) {
            return true;
        }

        // Manager can view all order comments
        if ($user->hasRole('manager')) {
            return true;
        }

        // Power user can view all order comments
        if ($user->hasRole('power_user')) {
            return true;
        }

        // Сотрудники могут видеть комментарии только заказов, где они назначены
        if ($user->isStaff()) {
            return $order->assignments()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can update assignment status.
     */
    public function updateAssignmentStatus(User $user, Order $order): bool
    {
        // Admin can update any assignment status
        if ($user->hasRole('admin')) {
            return true;
        }

        // Manager can update any assignment status
        if ($user->hasRole('manager')) {
            return true;
        }

        // Power user can update any assignment status
        if ($user->hasRole('power_user')) {
            return true;
        }

        // Сотрудники могут изменять статусы только своих назначений
        if ($user->isStaff()) {
            return $order->assignments()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can export orders.
     */
    public function exportOrders(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view order analytics.
     */
    public function viewOrderAnalytics(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can manage order priorities.
     */
    public function manageOrderPriorities(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view order statistics.
     */
    public function viewOrderStatistics(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can access order management features.
     */
    public function accessOrderManagement(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user', 'employee']);
    }
}
