<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\Fluent\Concerns\Has;

class Message extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'phone', 'message', 'send_time'];

    public function orders()
    {
        return $this->belongsTo(Order::class);
    }
}
