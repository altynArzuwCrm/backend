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
        'color'
    ];

    protected $casts = [
        'order' => 'integer'
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'stage_roles')
            ->withTimestamps();
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'stage_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function getNextStage()
    {
        return static::where('order', '>', $this->order)
            ->ordered()
            ->first();
    }

    public function getPreviousStage()
    {
        return static::where('order', '<', $this->order)
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
            $targetStage->id === $prevStage?->id;
    }

    public static function getOrderedStages()
    {
        return static::ordered()->get();
    }
}
