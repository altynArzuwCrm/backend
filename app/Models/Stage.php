<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    protected $fillable = ['name', 'funnel_id', 'order'];

    public function funnel()
    {
        return $this->hasMany(Funnel::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
