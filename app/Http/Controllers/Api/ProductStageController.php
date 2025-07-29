<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Stage;
use App\Models\ProductStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductStageController extends Controller
{
    public function index(Product $product)
    {
        if (Gate::denies('view', $product)) {
            abort(403, 'Доступ запрещён');
        }

        $productStages = $product->productStages()->with('stage')->get();
        $availableStages = Stage::active()->get();

        return response()->json([
            'product_stages' => $productStages,
            'available_stages' => $availableStages,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        if (Gate::denies('update', $product)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'stages' => 'required|array',
            'stages.*.stage_id' => 'required|exists:stages,id',
            'stages.*.is_available' => 'boolean',
            'stages.*.is_default' => 'boolean',
        ]);

        // Remove existing stage assignments
        $product->productStages()->delete();

        // Ensure only one default stage
        $defaultStages = collect($data['stages'])->where('is_default', true);
        if ($defaultStages->count() > 1) {
            return response()->json([
                'message' => 'Только одна стадия может быть установлена как стадия по умолчанию'
            ], 422);
        }

        // Add new stage assignments
        foreach ($data['stages'] as $stageData) {
            ProductStage::create([
                'product_id' => $product->id,
                'stage_id' => $stageData['stage_id'],
                'is_available' => $stageData['is_available'] ?? true,
                'is_default' => $stageData['is_default'] ?? false,
            ]);
        }

        return response()->json([
            'message' => 'Стадии продукта успешно обновлены',
            'product_stages' => $product->productStages()->with('stage')->get(),
        ]);
    }

    public function addStage(Request $request, Product $product)
    {
        if (Gate::denies('update', $product)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'stage_id' => 'required|exists:stages,id',
            'is_available' => 'boolean',
            'is_default' => 'boolean',
        ]);

        // Check if stage already assigned
        $exists = ProductStage::where('product_id', $product->id)
            ->where('stage_id', $data['stage_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Стадия уже назначена на этот продукт'
            ], 422);
        }

        // If setting as default, remove default from other stages
        if ($data['is_default'] ?? false) {
            ProductStage::where('product_id', $product->id)
                ->update(['is_default' => false]);
        }

        $productStage = ProductStage::create([
            'product_id' => $product->id,
            'stage_id' => $data['stage_id'],
            'is_available' => $data['is_available'] ?? true,
            'is_default' => $data['is_default'] ?? false,
        ]);

        return response()->json($productStage->load('stage'), 201);
    }

    public function removeStage(Product $product, Stage $stage)
    {
        if (Gate::denies('update', $product)) {
            abort(403, 'Доступ запрещён');
        }

        $productStage = ProductStage::where('product_id', $product->id)
            ->where('stage_id', $stage->id)
            ->first();

        if (!$productStage) {
            return response()->json([
                'message' => 'Стадия не назначена на этот продукт'
            ], 404);
        }

        // Check if stage is being used in orders
        $ordersUsingStage = $product->orders()->where('stage', $stage->name)->count();
        if ($ordersUsingStage > 0) {
            return response()->json([
                'message' => "Невозможно удалить стадию. Она используется в {$ordersUsingStage} заказах."
            ], 422);
        }

        $productStage->delete();

        return response()->json([
            'message' => 'Стадия успешно удалена из продукта'
        ]);
    }
}
