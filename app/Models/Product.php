<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'designer_id',
        'is_workshop_required',
        'workshop_type',
    ];

    protected $casts = [
        'is_workshop_required' => 'boolean',
    ];

    public function designer()
    {
        return $this->belongsTo(User::class, 'designer_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
