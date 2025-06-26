<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'client_name',
        'client_phone',
        'status',
        'deadline',
        'is_completed',
        'price',
        'payment_amount',
        'finalized_at',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'finalized_at' => 'datetime',
        'is_completed' => 'boolean',
        'price' => 'decimal:2',
        'payment_amount' => 'decimal:2',
    ];

    public function isFullyPaid(): bool
    {
        return $this->price !== null && $this->payment_amount >= $this->price;
    }

    public function isOverdue(): bool
    {
        return $this->deadline && $this->deadline->isPast() && !$this->is_completed;
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function refreshStage()
    {
        $stages = ['draft', 'design', 'print', 'workshop', 'finalize', 'archived'];

        $currentStageIndex = array_search($this->stage, $stages);

        if ($currentStageIndex === false) {
            $this->stage = 'draft';
            $this->save();
            return;
        }

        $allCompleted = $this->items()
            ->get()
            ->every(fn($item) => $item->status === 'completed');

        if ($allCompleted && $currentStageIndex < count($stages) - 1) {
            $this->stage = $stages[$currentStageIndex + 1];
            $this->save();
        }
    }
}
