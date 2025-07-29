<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'stage_id',
        'is_available',
        'is_default'
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'is_default' => 'boolean'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

    public static function isStageAvailableForProduct($productId, $stageName)
    {
        $stage = Stage::where('name', $stageName)->first();
        if (!$stage) {
            return false;
        }

        return static::where('product_id', $productId)
            ->where('stage_id', $stage->id)
            ->where('is_available', true)
            ->exists();
    }

    public static function getAvailableStagesForProduct($productId)
    {
        return static::where('product_id', $productId)
            ->where('is_available', true)
            ->with('stage')
            ->get()
            ->pluck('stage');
    }
}
