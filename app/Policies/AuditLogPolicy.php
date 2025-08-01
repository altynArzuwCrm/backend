<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuditLogPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasElevatedPermissions();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->hasElevatedPermissions();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only system can create audit logs
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AuditLog $auditLog): bool
    {
        // Audit logs should not be updated
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AuditLog $auditLog): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AuditLog $auditLog): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AuditLog $auditLog): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can export audit logs.
     */
    public function exportAuditLogs(User $user): bool
    {
        return $user->isAdminOrManager();
    }

    /**
     * Determine whether the user can view audit log analytics.
     */
    public function viewAuditLogAnalytics(User $user): bool
    {
        return $user->isAdminOrManager();
    }

    /**
     * Determine whether the user can manage audit log settings.
     */
    public function manageAuditLogSettings(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view audit log statistics.
     */
    public function viewAuditLogStatistics(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Determine whether the user can access audit log management features.
     */
    public function accessAuditLogManagement(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view audit log history.
     */
    public function viewAuditLogHistory(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can filter audit logs.
     */
    public function filterAuditLogs(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can search audit logs.
     */
    public function searchAuditLogs(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    /**
     * Determine whether the user can view audit log details.
     */
    public function viewAuditLogDetails(User $user, AuditLog $auditLog): bool
    {
        // Admin can view all audit log details
        if ($user->hasRole('admin')) {
            return true;
        }

        // Manager can view all audit log details
        if ($user->hasRole('manager')) {
            return true;
        }

        // Power user can view audit log details
        if ($user->hasRole('power_user')) {
            return true;
        }

        // Employees cannot view audit log details
        return false;
    }

    /**
     * Determine whether the user can manage audit log retention.
     */
    public function manageAuditLogRetention(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view audit log reports.
     */
    public function viewAuditLogReports(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }
}
