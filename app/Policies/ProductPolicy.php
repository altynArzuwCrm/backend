<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Админы, менеджеры и power_user видят все товары
        if ($user->hasElevatedPermissions()) {
            return true;
        }

        // Сотрудники видят только свои товары (проверяется в view)
        return $user->isStaff();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Product $product): bool
    {
        // Админы, менеджеры и power_user видят все товары
        if ($user->hasElevatedPermissions()) {
            return true;
        }

        // Сотрудники видят только товары, где они назначены
        if ($user->isStaff()) {
            return $product->assignments()
                ->where('user_id', $user->id)
                ->exists();
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
    public function update(User $user, Product $product): bool
    {
        // Админы, менеджеры и power_user могут обновлять все товары
        if ($user->hasElevatedPermissions()) {
            return true;
        }

        // Сотрудники могут обновлять только товары, где они назначены
        if ($user->isStaff()) {
            return $product->assignments()
                ->where('user_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Product $product): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Product $product): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can assign products.
     */
    public function assignProduct(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can bulk assign products.
     */
    public function bulkAssignProducts(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can manage product assignments.
     */
    public function manageProductAssignments(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view product assignments.
     */
    public function viewProductAssignments(User $user, Product $product): bool
    {
        // Admin can view all assignments
        if ($user->hasRole('admin')) {
            return true;
        }

        // Manager can view all assignments
        if ($user->hasRole('manager')) {
            return true;
        }

        // Power user can view all assignments
        if ($user->hasRole('power_user')) {
            return true;
        }

        // Employee can view assignments for their products
        if ($user->hasRole('employee')) {
            return $product->assignments()
                ->where('user_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can export products.
     */
    public function exportProducts(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view product analytics.
     */
    public function viewProductAnalytics(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can manage product categories.
     */
    public function manageProductCategories(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view product statistics.
     */
    public function viewProductStatistics(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can access product management features.
     */
    public function accessProductManagement(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user', 'employee']);
    }

    /**
     * Determine whether the user can view all products (for dropdowns).
     */
    public function allProducts(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can manage product stages.
     */
    public function manageProductStages(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view product history.
     */
    public function viewProductHistory(User $user, Product $product): bool
    {
        // Admin can view all product history
        if ($user->hasRole('admin')) {
            return true;
        }

        // Manager can view all product history
        if ($user->hasRole('manager')) {
            return true;
        }

        // Power user can view all product history
        if ($user->hasRole('power_user')) {
            return true;
        }

        // Employee can view history of their assigned products
        if ($user->hasRole('employee')) {
            return $product->assignments()
                ->where('user_id', $user->id)
                ->exists();
        }

        return false;
    }
}
