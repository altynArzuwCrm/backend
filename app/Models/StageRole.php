<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StageRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'stage_id',
        'role_id',
        'is_required',
        'auto_assign'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'auto_assign' => 'boolean'
    ];

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
