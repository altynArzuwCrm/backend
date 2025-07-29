<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'deadline',
        'total_price',
        'payment_amount'
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'total_price' => 'decimal:2',
        'payment_amount' => 'decimal:2'
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    
    public function recalculateTotalPrice()
    {
        $total = $this->orders()->sum('price');
        $this->total_price = $total;
        $this->save();
    }
}
