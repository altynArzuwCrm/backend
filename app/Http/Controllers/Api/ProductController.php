<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\DTOs\ProductDTO;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\ProductResource;

class ProductController extends Controller
{
    protected ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function index(Request $request)
    {
        if (Gate::denies('viewAny', Product::class)) {
            return response()->json([
                'message' => 'Not Authorized'
            ], 403);
        }

        // Кэшируем результаты поиска на 5 минут для быстрых ответов
        $cacheKey = 'products_' . md5($request->fullUrl());
        $result = CacheService::rememberWithTags($cacheKey, 300, function () use ($request) {
            return $this->productRepository->getPaginatedProducts($request);
        }, [CacheService::TAG_PRODUCTS]);

        return response()->json($result);
    }

    public function show(Product $product)
    {
        if (Gate::denies('view', $product)) {
            return response()->json([
                'message' => 'Not Authorized'
            ], 403);
        }

        $productDTO = $this->productRepository->getProductById($product->id);

        if (!$productDTO) {
            abort(404, 'Продукт не найден');
        }

        return response()->json($productDTO->toArray());
    }

    public function store(Request $request)
    {
        if (Gate::denies('create', Product::class)) {
            return response()->json([
                'message' => 'Not Authorized'
            ], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'stages' => 'sometimes|array',
            'stages.*.stage_id' => 'required|exists:stages,id',
            'stages.*.is_available' => 'boolean',
            'stages.*.is_default' => 'boolean',
        ]);

        $product = Product::create($data);

        // Assign custom stages if provided (auto-assignment happens in model boot)
        if (isset($data['stages'])) {
            // Remove auto-assigned stages first
            $product->productStages()->delete();

            // Assign custom stages
            foreach ($data['stages'] as $stageData) {
                \App\Models\ProductStage::create([
                    'product_id' => $product->id,
                    'stage_id' => $stageData['stage_id'],
                    'is_available' => $stageData['is_available'] ?? true,
                    'is_default' => $stageData['is_default'] ?? false,
                ]);
            }
        } else {
            // If no stages provided, ensure all stages are available (for backward compatibility)
            $allStages = \App\Models\Stage::all();
            foreach ($allStages as $stage) {
                \App\Models\ProductStage::updateOrCreate(
                    ['product_id' => $product->id, 'stage_id' => $stage->id],
                    ['is_available' => true, 'is_default' => $stage->name === 'draft']
                );
            }
        }

        // Используем with() вместо load() для предотвращения N+1 проблемы
        $product = Product::with(['assignments.user', 'orders.assignments', 'availableStages.roles', 'productStages.stage.roles'])->find($product->id);

        // Очищаем кэш продуктов после создания
        CacheService::invalidateProductCaches();

        return response()->json(['data' => new ProductResource($product)], 201);
    }

    public function allProducts()
    {
        if (Gate::denies('allProducts', Product::class)) {
            abort(403, 'Доступ запрещён');
        }

        $products = CacheService::rememberWithTags('all_products', 60, function () {
            return Product::select('id', 'name', 'created_at')->orderBy('id')->get();
        }, [CacheService::TAG_PRODUCTS]);
        return response()->json($products);
    }



    public function update(Request $request, Product $product)
    {
        if (Gate::denies('update', $product)) {
            return response()->json([
                'message' => 'Not Authorized'
            ], 403);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'stages' => 'sometimes|array',
            'stages.*.stage_id' => 'required|exists:stages,id',
            'stages.*.is_available' => 'boolean',
            'stages.*.is_default' => 'boolean',
        ]);

        $product->update($data);

        // Update stages if provided
        if (isset($data['stages'])) {
            // Remove existing stage assignments
            $product->productStages()->delete();

            // Assign new stages
            foreach ($data['stages'] as $stageData) {
                \App\Models\ProductStage::create([
                    'product_id' => $product->id,
                    'stage_id' => $stageData['stage_id'],
                    'is_available' => $stageData['is_available'] ?? true,
                    'is_default' => $stageData['is_default'] ?? false,
                ]);
            }
        } else {
            // If no stages provided, ensure all stages are available (for backward compatibility)
            $allStages = \App\Models\Stage::all();
            foreach ($allStages as $stage) {
                \App\Models\ProductStage::updateOrCreate(
                    ['product_id' => $product->id, 'stage_id' => $stage->id],
                    ['is_available' => true, 'is_default' => $stage->name === 'draft']
                );
            }
        }

        // Используем with() вместо load() для предотвращения N+1 проблемы
        $product = Product::with(['assignments.user', 'orders.assignments', 'availableStages.roles', 'productStages.stage.roles'])->find($product->id);

        // Очищаем кэш продуктов после обновления
        CacheService::invalidateProductCaches($product->id);

        return new ProductResource($product);
    }

    public function destroy(Product $product)
    {
        if (Gate::denies('delete', $product)) {
            return response()->json([
                'message' => 'Not Authorized'
            ], 403);
        }

        // Проверяем все заказы, связанные с товаром
        $ordersCount = $product->orders()->count();

        if ($ordersCount > 0) {
            return response()->json([
                'message' => "Невозможно удалить товар, который используется в {$ordersCount} заказах"
            ], 422);
        }

        $product->delete();

        // Очищаем кэш продуктов после удаления
        CacheService::invalidateProductCaches($product->id);

        return response()->json(['message' => 'Товар удалён']);
    }
}
