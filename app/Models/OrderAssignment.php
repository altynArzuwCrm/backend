<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderAssignment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'user_id',
        'status',
        'assigned_by',
        'cancelled_at',
        'approved_at',
        'started_at',
        'assigned_at',
        'role_type'
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
        'approved_at' => 'datetime',
        'started_at' => 'datetime',
        'assigned_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function orderStageAssignments()
    {
        return $this->hasMany(OrderStageAssignment::class);
    }

    public function assignedStages()
    {
        return $this->belongsToMany(Stage::class, 'order_stage_assignments')
            ->wherePivot('is_assigned', true)
            ->withTimestamps();
    }

    public function isAssignedToStage($stageName)
    {
        return $this->assignedStages()->where('name', $stageName)->exists();
    }

    public function assignToStage($stageName)
    {
        $stage = Stage::where('name', $stageName)->first();
        if ($stage) {
            OrderStageAssignment::updateOrCreate([
                'order_assignment_id' => $this->id,
                'stage_id' => $stage->id,
            ], [
                'is_assigned' => true
            ]);
        }
    }

    public function removeFromStage($stageName)
    {
        $stage = Stage::where('name', $stageName)->first();
        if ($stage) {
            OrderStageAssignment::where('order_assignment_id', $this->id)
                ->where('stage_id', $stage->id)
                ->delete();
        }
    }

    protected static function booted()
    {
        static::updating(function ($assignment) {
            if ($assignment->isDirty('status')) {
                $newStatus = $assignment->status;

                switch ($newStatus) {
                    case 'approved':
                        $assignment->approved_at = now();
                        break;
                    case 'cancelled':
                        $assignment->cancelled_at = now();
                        break;
                    case 'in_progress':
                        if (!$assignment->started_at) {
                            $assignment->started_at = now();
                        }
                        break;
                }
            }
        });

        static::saved(function ($assignment) {
            if ($assignment->wasChanged('status') && $assignment->status === 'approved') {
                $order = $assignment->order;
                if ($order && $order->isCurrentStageApproved()) {
                    $order->moveToNextStage();
                }
            }
        });
    }
}
