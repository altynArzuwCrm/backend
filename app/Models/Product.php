<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'default_designer_id', 'is_workshop_required', 'workshop_type'];

    public function defaultDesigner()
    {
        return $this->belongsTo(User::class, 'default_designer_id');
    }
}
