<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'manager_id',
        'deadline',
        'status',
    ];


    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function assignments()
    {
        return $this->hasMany(OrderItemAssignment::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function reason()
    {
        return $this->belongsTo(Reason::class);
    }

    public function designers()
    {
        return $this->belongsToMany(User::class, 'order_item_designer', 'order_item_id');
    }

    public function printers()
    {
        return $this->belongsToMany(User::class, 'order_item_printer', 'order_item_id');
    }

    public function workshopWorkers()
    {
        return $this->belongsToMany(User::class, 'order_item_workshop_worker', 'order_item_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function refreshStatusFromAssignments()
    {
        $assignmnets = $this->assignments;

        if ($assignmnets->isEmpty()) {
            $this->status = 'pending';
        } elseif ($assignmnets->contains(fn($a) => $a->status === 'in_progress')) {
            $this->status = 'in_progress';
        } elseif ($assignmnets->contains(fn($a) => $a->status === 'under_review')) {
            $this->status = 'under_review';
        } elseif ($assignmnets->every(fn($a) => in_array($a->status, ['approved', 'completed']))) {
            $this->status = 'completed';
        } elseif ($assignmnets->contains(fn($a) => $a->status === 'cancelled')) {
            $this->status = 'cancelled';
        } else {
            $this->status = 'pending';
        }

        $this->save();

        $this->order->refreshStage();
    }
}
