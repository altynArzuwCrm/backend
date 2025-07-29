<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'order',
        'is_active',
        'is_initial',
        'is_final',
        'color'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_initial' => 'boolean',
        'is_final' => 'boolean',
        'order' => 'integer'
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'stage_roles')
            ->withTimestamps();
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'stage', 'name');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function getNextStage()
    {
        return static::active()
            ->where('order', '>', $this->order)
            ->ordered()
            ->first();
    }

    public function getPreviousStage()
    {
        return static::active()
            ->where('order', '<', $this->order)
            ->ordered()
            ->orderBy('order', 'desc')
            ->first();
    }

    public function canTransitionTo(Stage $targetStage)
    {
        // Basic validation - can only move to next stage or previous stage
        $nextStage = $this->getNextStage();
        $prevStage = $this->getPreviousStage();

        return $targetStage->id === $nextStage?->id ||
            $targetStage->id === $prevStage?->id ||
            $targetStage->is_final; // Can always go to final stages (cancelled, completed)
    }

    public static function getInitialStage()
    {
        return static::active()->where('is_initial', true)->first();
    }

    public static function getOrderedStages()
    {
        return static::active()->ordered()->get();
    }
}
