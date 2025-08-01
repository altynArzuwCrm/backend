<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PowerUserPolicy
{
    use HandlesAuthorization;

    public function accessPowerUserFeatures(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    public function viewPowerUserDashboard(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    public function performBulkOperations(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    public function exportData(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    public function viewAnalytics(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    public function manageSystemSettings(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    public function viewAllUserData(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    public function manageAssignments(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    public function viewAuditLogs(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    public function performAdvancedSearches(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    public function accessReportingFeatures(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    public function manageDataImports(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    public function viewSystemStatistics(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    public function manageNotifications(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    public function accessApiDocumentation(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    public function performDataValidation(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager', 'power_user']);
    }

    public function manageDataBackups(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function accessSystemLogs(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }
}
