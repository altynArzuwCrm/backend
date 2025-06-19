<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'surname', 'phone'];

    public $timestamps = true;

    public function orders()
    {
        $this->hasMany(Order::class);
    }
}
