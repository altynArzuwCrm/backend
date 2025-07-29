<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStageAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_assignment_id',
        'stage_id',
        'is_assigned'
    ];

    protected $casts = [
        'is_assigned' => 'boolean'
    ];

    public function orderAssignment()
    {
        return $this->belongsTo(OrderAssignment::class);
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }
}
