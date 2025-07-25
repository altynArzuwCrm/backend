<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AuditLogPolicy
{
    /**
     * Determine whether the user can view any models.
     * Только администраторы могут просматривать список аудит-логов
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the model.
     * Только администраторы могут просматривать отдельные записи аудит-логов
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create models.
     * Создание аудит-логов происходит автоматически через Observer
     */
    public function create(User $user): bool
    {
        return false; // Запрещено создание вручную
    }

    /**
     * Determine whether the user can update the model.
     * Аудит-логи нельзя редактировать
     */
    public function update(User $user, AuditLog $auditLog): bool
    {
        return false; // Запрещено редактирование
    }

    /**
     * Determine whether the user can delete the model.
     * Удаление аудит-логов разрешено только администраторам
     */
    public function delete(User $user, AuditLog $auditLog): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     * Восстановление аудит-логов разрешено только администраторам
     */
    public function restore(User $user, AuditLog $auditLog): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Полное удаление аудит-логов разрешено только администраторам
     */
    public function forceDelete(User $user, AuditLog $auditLog): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can export audit logs.
     * Экспорт аудит-логов разрешен только администраторам
     */
    public function export(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view audit statistics.
     * Просмотр статистики аудит-логов разрешен только администраторам
     */
    public function viewStats(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can cleanup old audit logs.
     * Очистка старых аудит-логов разрешена только администраторам
     */
    public function cleanup(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function before($user, $ability)
    {
        if ($user->hasRole('admin')) {
            return true;
        }
    }
}
