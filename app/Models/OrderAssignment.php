<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAssignment extends Model
{
    use HasFactory;
    
    protected $fillable = ['order_id', 'user_id', 'status', 'assigned_by', 'cancelled_at', 'approved_at', 'started_at', 'completed_at', 'assigned_at'];

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
                    case 'completed':
                        $assignment->completed_at = now();
                        $assignment->status = 'under_review';
                        break;
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
            // $assignment->order->refreshStatusFromAssignments();
        });
    }
} 