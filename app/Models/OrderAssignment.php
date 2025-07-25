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
        'has_design_stage',
        'has_print_stage',
        'has_engraving_stage',
        'has_workshop_stage',
        'role_type'
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
