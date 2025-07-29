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
        'workshop_worker_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($product) {
            // Auto-assign default stages when product is created
            $defaultStages = Stage::active()->whereIn('name', ['draft', 'design', 'print', 'workshop', 'completed'])->get();

            foreach ($defaultStages as $stage) {
                ProductStage::create([
                    'product_id' => $product->id,
                    'stage_id' => $stage->id,
                    'is_available' => true,
                    'is_default' => $stage->name === 'draft',
                ]);
            }
        });
    }

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

    public function productStages()
    {
        return $this->hasMany(ProductStage::class);
    }

    public function availableStages()
    {
        return $this->belongsToMany(Stage::class, 'product_stages')
            ->wherePivot('is_available', true)
            ->withPivot('is_default')
            ->withTimestamps();
    }

    public function hasStage($stageName)
    {
        return ProductStage::isStageAvailableForProduct($this->id, $stageName);
    }

    public function getAvailableStages()
    {
        return ProductStage::getAvailableStagesForProduct($this->id);
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

    public function getNextAvailableUser($roleType, $excludeUserIds = [])
    {
        return ProductAssignment::getNextAvailableUser($this->id, $roleType, $excludeUserIds);
    }
}
