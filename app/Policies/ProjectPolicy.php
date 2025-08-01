<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Админы, менеджеры и power_user видят все проекты
        if ($user->hasElevatedPermissions()) {
            return true;
        }
        
        // Сотрудники видят только свои проекты (проверяется в view)
        return $user->isStaff();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool
    {
        // Админы, менеджеры и power_user видят все проекты
        if ($user->hasElevatedPermissions()) {
            return true;
        }

        // Сотрудники видят проекты, где они назначены на заказы
        if ($user->isStaff()) {
            // Проверяем, есть ли у пользователя назначения на заказы этого проекта
            $assignedOrderIds = \App\Models\OrderAssignment::query()
                ->where('user_id', $user->id)
                ->pluck('order_id');

            return $project->orders()
                ->whereIn('id', $assignedOrderIds)
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
    public function update(User $user, Project $project): bool
    {
        // Админы, менеджеры и power_user могут обновлять все проекты
        if ($user->hasElevatedPermissions()) {
            return true;
        }

        // Сотрудники могут обновлять проекты, где они назначены на заказы
        if ($user->isStaff()) {
            // Проверяем, есть ли у пользователя назначения на заказы этого проекта
            $assignedOrderIds = \App\Models\OrderAssignment::query()
                ->where('user_id', $user->id)
                ->pluck('order_id');

            return $project->orders()
                ->whereIn('id', $assignedOrderIds)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->isAdminOrManager();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can assign projects.
     */
    public function assignProject(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can bulk assign projects.
     */
    public function bulkAssignProjects(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can manage project assignments.
     */
    public function manageProjectAssignments(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view project assignments.
     */
    public function viewProjectAssignments(User $user, Project $project): bool
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

        // Employee can view assignments for their projects
        if ($user->hasRole('employee')) {
            return $project->assignments()
                ->where('user_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can export projects.
     */
    public function exportProjects(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view project analytics.
     */
    public function viewProjectAnalytics(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can manage project categories.
     */
    public function manageProjectCategories(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view project statistics.
     */
    public function viewProjectStatistics(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can access project management features.
     */
    public function accessProjectManagement(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user', 'employee']);
    }

    /**
     * Determine whether the user can view all projects (for dropdowns).
     */
    public function allProjects(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can manage project stages.
     */
    public function manageProjectStages(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view project history.
     */
    public function viewProjectHistory(User $user, Project $project): bool
    {
        // Admin can view all project history
        if ($user->hasRole('admin')) {
            return true;
        }

        // Manager can view all project history
        if ($user->hasRole('manager')) {
            return true;
        }

        // Power user can view all project history
        if ($user->hasRole('power_user')) {
            return true;
        }

        // Employee can view history of their assigned projects
        if ($user->hasRole('employee')) {
            return $project->assignments()
                ->where('user_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can manage project budgets.
     */
    public function manageProjectBudgets(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view project timelines.
     */
    public function viewProjectTimelines(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }
}
