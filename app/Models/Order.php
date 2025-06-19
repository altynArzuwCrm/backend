<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'client_id', 'status', 'manager_id', 'executor_id', 'stage_id'];

    public function funnel()
    {
       return $this->belongsTo(Funnel::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class)->where('role', 'manager');
    }

    public function executor() {
        return $this->belongsTo(User::class)->where('role', 'executor');
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

    public function client()
    {
       return $this->belongsTo(Client::class);
    }
}
