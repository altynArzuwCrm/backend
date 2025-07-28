<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogObserver
{
    /**
     * Handle the model "created" event.
     */
    public function created($model): void
    {
        $this->log('created', $model, [], $model->toArray());
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated($model): void
    {
        $changes = $model->getChanges();
        $original = $model->getOriginal();
        
        // Фильтруем только измененные поля
        $oldValues = array_intersect_key($original, $changes);
        $newValues = $changes;
        
        $this->log('updated', $model, $oldValues, $newValues);
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted($model): void
    {
        $this->log('deleted', $model, $model->toArray(), []);
    }

    /**
     * Handle the model "restored" event.
     */
    public function restored($model): void
    {
        $this->log('restored', $model, [], $model->toArray());
    }

    /**
     * Handle the model "force deleted" event.
     */
    public function forceDeleted($model): void
    {
        $this->log('force_deleted', $model, $model->toArray(), []);
    }

    /**
     * Создает запись в аудит-логе
     */
    protected function log(string $action, $model, array $oldValues, array $newValues): void
    {
        // Исключаем поля, которые не нужно логировать
        $excludedFields = ['updated_at', 'created_at', 'remember_token'];
        
        $oldValues = array_diff_key($oldValues, array_flip($excludedFields));
        $newValues = array_diff_key($newValues, array_flip($excludedFields));

        // Получаем ID пользователя, если он аутентифицирован
        $userId = Auth::id();
        
        // Если пользователь не аутентифицирован (например, в консольных командах),
        // используем ID первого пользователя или null
        if (!$userId) {
            $firstUser = \App\Models\User::first();
            $userId = $firstUser ? $firstUser->id : null;
        }

        AuditLog::create([
            'user_id' => $userId,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'action' => $action,
            'change_type' => $action, // Заполняем поле change_type
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'created_at' => now(),
        ]);
    }
} 