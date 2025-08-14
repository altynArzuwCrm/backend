<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'role_type',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getActiveAssignments($productId, $roleType)
    {
        return static::where('product_id', $productId)
            ->where('role_type', $roleType)
            ->where('is_active', true)
            ->with('user')
            ->get();
    }

    public static function getNextAvailableUser($productId, $roleType, $excludeUserIds = [])
    {
        return static::where('product_id', $productId)
            ->where('role_type', $roleType)
            ->where('is_active', true)
            ->whereNotIn('user_id', $excludeUserIds)
            ->with('user')
            ->first();
    }
}
