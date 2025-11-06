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

        // Кэширование обрабатывается в ProductRepository
        // чтобы избежать сериализации полных Eloquent моделей
        $result = $this->productRepository->getPaginatedProducts($request);

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
            'categories' => 'sometimes|array',
            'categories.*' => 'exists:categories,id',
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
            // Оптимизация: выбираем только необходимые поля и используем массовую вставку
            $allStages = \App\Models\Stage::select('id', 'name')->get();
            $stageData = [];
            foreach ($allStages as $stage) {
                $stageData[] = [
                    'product_id' => $product->id,
                    'stage_id' => $stage->id,
                    'is_available' => true,
                    'is_default' => $stage->name === 'draft',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            // Используем insertOrIgnore для массовой вставки (быстрее чем цикл с updateOrCreate)
            if (!empty($stageData)) {
                \App\Models\ProductStage::insertOrIgnore($stageData);
            }
        }

        // Assign categories if provided
        if (isset($data['categories'])) {
            $product->categories()->sync($data['categories']);
        }

        // Оптимизация: убираем загрузку orders.assignments - это может быть очень тяжело
        // Загружаем только необходимые relationships с select() для оптимизации
        $product = Product::select('id', 'name', 'created_at', 'updated_at')
            ->with([
                'assignments' => function ($q) {
                    $q->select('id', 'product_id', 'user_id', 'role_type', 'is_active');
                },
                'assignments.user' => function ($q) {
                    $q->select('id', 'name', 'username');
                },
                'availableStages' => function ($q) {
                    $q->select('stages.id', 'stages.name', 'stages.display_name', 'stages.order');
                },
                'availableStages.roles' => function ($q) {
                    $q->select('roles.id', 'roles.name', 'roles.display_name');
                },
                'productStages' => function ($q) {
                    $q->select('id', 'product_id', 'stage_id');
                },
                'productStages.stage' => function ($q) {
                    $q->select('stages.id', 'stages.name', 'stages.display_name', 'stages.order');
                },
                'productStages.stage.roles' => function ($q) {
                    $q->select('roles.id', 'roles.name', 'roles.display_name');
                },
                'categories' => function ($q) {
                    $q->select('categories.id', 'categories.name');
                }
            ])
            ->find($product->id);

        // Очищаем кэш продуктов после создания
        CacheService::invalidateProductCaches();

        return response()->json(['data' => new ProductResource($product)], 201);
    }

    public function allProducts()
    {
        if (Gate::denies('allProducts', Product::class)) {
            abort(403, 'Доступ запрещён');
        }

        // Оптимизация: добавляем лимит для предотвращения загрузки всех продуктов
        $limit = min((int) request()->get('limit', 500), 5000); // Максимум 5000
        $cacheKey = 'all_products_limit_' . $limit;
        
        // Проверяем кэш
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            // Восстанавливаем коллекцию из массива
            $products = collect($cached)->map(function ($item) {
                $product = new Product();
                $product->id = $item['id'];
                $product->name = $item['name'];
                $product->created_at = $item['created_at'] ? \Carbon\Carbon::parse($item['created_at']) : null;
                return $product;
            });
            return response()->json($products);
        }

        // Загружаем и кэшируем как массив
        $products = Product::select('id', 'name', 'created_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();
        
        // Кэшируем как массив
        $cacheData = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'created_at' => $product->created_at?->toDateTimeString(),
            ];
        })->toArray();
        
        Cache::put($cacheKey, $cacheData, 1800);
        
        // Отслеживаем ключ для инвалидации
        $trackingKey = 'cache_keys_' . CacheService::TAG_PRODUCTS;
        $keys = Cache::get($trackingKey, []);
        if (!in_array($cacheKey, $keys)) {
            $keys[] = $cacheKey;
            Cache::put($trackingKey, $keys, 86400);
        }
        
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
            'categories' => 'sometimes|array',
            'categories.*' => 'exists:categories,id',
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
            // Оптимизация: выбираем только необходимые поля и используем массовое обновление
            $allStages = \App\Models\Stage::select('id', 'name')->get();
            $stageData = [];
            foreach ($allStages as $stage) {
                $stageData[] = [
                    'product_id' => $product->id,
                    'stage_id' => $stage->id,
                    'is_available' => true,
                    'is_default' => $stage->name === 'draft',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            // Используем upsert для массового обновления/вставки (быстрее чем цикл с updateOrCreate)
            if (!empty($stageData)) {
                \App\Models\ProductStage::upsert(
                    $stageData,
                    ['product_id', 'stage_id'],
                    ['is_available', 'is_default', 'updated_at']
                );
            }
        }

        // Update categories if provided
        if (isset($data['categories'])) {
            $product->categories()->sync($data['categories']);
        }

        // Используем with() вместо load() для предотвращения N+1 проблемы
        $product = Product::with(['assignments.user', 'orders.assignments', 'availableStages.roles', 'productStages.stage.roles', 'categories'])->find($product->id);

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
