<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Funnel extends Model
{
    protected $fillable = ['name', 'description'];

    public function orders()
    {
        $this->hasMany(Order::class);
    }

    public function stages()
    {
        $this->hasMany(Stage::class);
    }
}
