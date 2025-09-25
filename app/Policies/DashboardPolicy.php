<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DashboardPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can access the dashboard.
     */
    public function accessDashboard(User $user): bool
    {
        // Все пользователи с ролями могут получить доступ к дашборду
        return $user->roles()->exists();
    }

    /**
     * Determine whether the user can view dashboard statistics.
     */
    public function viewDashboardStatistics(User $user): bool
    {
        return $user->hasElevatedPermissions();
    }

    /**
     * Determine whether the user can view all user statistics.
     */
    public function viewAllUserStatistics(User $user): bool
    {
        return $user->hasElevatedPermissions();
    }

    /**
     * Determine whether the user can view their own statistics.
     */
    public function viewOwnStatistics(User $user): bool
    {
        // Все пользователи с ролями могут видеть свою статистику
        return $user->roles()->exists();
    }

    /**
     * Determine whether the user can view order statistics.
     */
    public function viewOrderStatistics(User $user): bool
    {
        return $user->hasElevatedPermissions();
    }

    /**
     * Determine whether the user can view product statistics.
     */
    public function viewProductStatistics(User $user): bool
    {
        return $user->hasElevatedPermissions();
    }

    /**
     * Determine whether the user can view project statistics.
     */
    public function viewProjectStatistics(User $user): bool
    {
        return $user->hasElevatedPermissions();
    }

    /**
     * Determine whether the user can view client statistics.
     */
    public function viewClientStatistics(User $user): bool
    {
        return $user->hasElevatedPermissions();
    }

    /**
     * Determine whether the user can view audit log statistics.
     */
    public function viewAuditLogStatistics(User $user): bool
    {
        return $user->isAdminOrManager();
    }

    /**
     * Determine whether the user can view recent activity.
     */
    public function viewRecentActivity(User $user): bool
    {
        // Все пользователи с ролями могут видеть недавнюю активность
        return $user->roles()->exists();
    }

    /**
     * Determine whether the user can view all recent activity.
     */
    public function viewAllRecentActivity(User $user): bool
    {
        return $user->hasElevatedPermissions();
    }

    /**
     * Determine whether the user can view their own recent activity.
     */
    public function viewOwnRecentActivity(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user', 'employee']);
    }

    /**
     * Determine whether the user can view quick actions.
     */
    public function viewQuickActions(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can perform quick actions.
     */
    public function performQuickActions(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view dashboard analytics.
     */
    public function viewDashboardAnalytics(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can export dashboard data.
     */
    public function exportDashboardData(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view dashboard reports.
     */
    public function viewDashboardReports(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can customize dashboard.
     */
    public function customizeDashboard(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }


    /**
     * Determine whether the user can view performance metrics.
     */
    public function viewPerformanceMetrics(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view user activity charts.
     */
    public function viewUserActivityCharts(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view order progress charts.
     */
    public function viewOrderProgressCharts(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view project progress charts.
     */
    public function viewProjectProgressCharts(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view product distribution charts.
     */
    public function viewProductDistributionCharts(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view client distribution charts.
     */
    public function viewClientDistributionCharts(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view stage distribution charts.
     */
    public function viewStageDistributionCharts(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view role distribution charts.
     */
    public function viewRoleDistributionCharts(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }
}
