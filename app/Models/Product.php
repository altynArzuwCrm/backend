<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description'
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
            // This will only run if no stages are explicitly provided
            if (!$product->wasRecentlyCreated || $product->productStages()->count() === 0) {
                $defaultStages = Stage::ordered()->get();

                foreach ($defaultStages as $stage) {
                    ProductStage::create([
                        'product_id' => $product->id,
                        'stage_id' => $stage->id,
                        'is_available' => true,
                        'is_default' => $stage->name === 'draft',
                    ]);
                }
            }
        });
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

    public function getNextAvailableUser($roleType, $excludeUserIds = [])
    {
        return ProductAssignment::getNextAvailableUser($this->id, $roleType, $excludeUserIds);
    }
}
