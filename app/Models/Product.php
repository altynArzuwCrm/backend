<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'designer_id',
        'print_operator_id',
        'workshop_worker_id',
        'has_design_stage',
        'has_print_stage',
        'has_engraving_stage',
        'has_workshop_stage',
    ];

    protected $casts = [
        'has_design_stage' => 'boolean',
        'has_print_stage' => 'boolean',
        'has_engraving_stage' => 'boolean',
        'has_workshop_stage' => 'boolean',
    ];

    public function designer()
    {
        return $this->belongsTo(User::class, 'designer_id');
    }
    public function printOperator()
    {
        return $this->belongsTo(User::class, 'print_operator_id');
    }
    public function workshopWorker()
    {
        return $this->belongsTo(User::class, 'workshop_worker_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Отношения для множественных назначений
    public function assignments()
    {
        return $this->hasMany(ProductAssignment::class);
    }

    public function designerAssignments()
    {
        return $this->hasMany(ProductAssignment::class)->where('role_type', 'designer');
    }

    public function printOperatorAssignments()
    {
        return $this->hasMany(ProductAssignment::class)->where('role_type', 'print_operator');
    }

    public function workshopWorkerAssignments()
    {
        return $this->hasMany(ProductAssignment::class)->where('role_type', 'workshop_worker');
    }

    public function engravingOperatorAssignments()
    {
        return $this->hasMany(ProductAssignment::class)->where('role_type', 'engraving_operator');
    }

    // Методы для получения назначенных пользователей
    public function getDesigners()
    {
        return $this->designerAssignments()
            ->where('is_active', true)
            ->with('user')
            ->get()
            ->pluck('user');
    }

    public function getPrintOperators()
    {
        return $this->printOperatorAssignments()
            ->where('is_active', true)
            ->with('user')
            ->get()
            ->pluck('user');
    }

    public function getWorkshopWorkers()
    {
        return $this->workshopWorkerAssignments()
            ->where('is_active', true)
            ->with('user')
            ->get()
            ->pluck('user');
    }

    public function getEngravingOperators()
    {
        return $this->engravingOperatorAssignments()
            ->where('is_active', true)
            ->with('user')
            ->get()
            ->pluck('user');
    }

    // Получить следующего доступного пользователя для роли
    public function getNextAvailableUser($roleType, $excludeUserIds = [])
    {
        return ProductAssignment::getNextAvailableUser($this->id, $roleType, $excludeUserIds);
    }
}
