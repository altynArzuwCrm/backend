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
        'designer_id',
        'printer_id',
        'workshop_worker_id',
        'manager_id',
        'individual_deadline',
        'status',
        'assigned_at',
        'started_at',
        'completed_at',
    ];


    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function designer()
    {
        return $this->belongsTo(User::class, 'designer_id');
    }

    public function printer()
    {
        return $this->belongsTo(User::class, 'printer_id');
    }

    public function workshopWorker()
    {
        return $this->belongsTo(User::class, 'workshop_worker_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function isWaiting()
    {
        return $this->status === 'ожидание';
    }

    public function isInProgress()
    {
        return $this->status === 'в_работе';
    }

    public function isCompleted()
    {
        return $this->status === 'завершено';
    }

    public function isCanceled()
    {
        return $this->status === 'отменено';
    }
}
