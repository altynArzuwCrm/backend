<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id', 
        'auditable_type', 
        'auditable_id', 
        'action',
        'old_values', 
        'new_values',
        'field_name',
        'old_value_text',
        'new_value_text',
        'project_id',
        'order_id',
        'change_type',
        'created_at'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // Метод для получения названия действия на русском языке
    public function getActionNameAttribute()
    {
        $actions = [
            'created' => 'Создан',
            'updated' => 'Обновлен',
            'deleted' => 'Удален',
            'restored' => 'Восстановлен',
            'assigned' => 'Назначен',
            'unassigned' => 'Снят с назначения',
            'status_changed' => 'Изменен статус',
        ];

        return $actions[$this->action] ?? $this->action;
    }

    // Метод для получения названия модели на русском языке
    public function getModelNameAttribute()
    {
        $models = [
            'App\Models\Order' => 'Заказ',
            'App\Models\Product' => 'Продукт',
            'App\Models\Project' => 'Проект',
            'App\Models\User' => 'Пользователь',
            'App\Models\Client' => 'Клиент',
            'App\Models\ClientContact' => 'Контакт клиента',
            'App\Models\Comment' => 'Комментарий',
            'App\Models\OrderAssignment' => 'Назначение заказа',
        ];

        return $models[$this->auditable_type] ?? class_basename($this->auditable_type);
    }
}
