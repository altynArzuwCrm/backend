<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogObserver
{
    public function created($model): void
    {
        $this->log('created', $model, [], $model->toArray());
    }

    public function updated($model): void
    {
        $changes = $model->getChanges();
        $original = $model->getOriginal();
        
        $oldValues = array_intersect_key($original, $changes);
        $newValues = $changes;
        
        $this->log('updated', $model, $oldValues, $newValues);
    }

    public function deleted($model): void
    {
        $this->log('deleted', $model, $model->toArray(), []);
    }

    public function restored($model): void
    {
        $this->log('restored', $model, [], $model->toArray());
    }

    public function forceDeleted($model): void
    {
        $this->log('force_deleted', $model, $model->toArray(), []);
    }

    protected function log(string $action, $model, array $oldValues, array $newValues): void
    {
        $excludedFields = ['updated_at', 'created_at', 'remember_token'];
        
        $oldValues = array_diff_key($oldValues, array_flip($excludedFields));
        $newValues = array_diff_key($newValues, array_flip($excludedFields));

        $userId = Auth::id();
        
        if (!$userId) {
            $firstUser = \App\Models\User::first();
            $userId = $firstUser ? $firstUser->id : null;
        }

        AuditLog::create([
            'user_id' => $userId,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'action' => $action,
            'change_type' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'created_at' => now(),
        ]);
    }
} 