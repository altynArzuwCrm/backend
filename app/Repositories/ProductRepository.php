<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\Category;
use App\DTOs\ProductDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Services\CacheService;
use Illuminate\Support\Facades\DB;

class ProductRepository
{
    public function getPaginatedProducts(Request $request): LengthAwarePaginator
    {
        // Создаем ключ кэша на основе параметров запроса
        $cacheKey = 'products_' . md5($request->fullUrl());
        $cacheTime = $request->has('force_refresh') ? 0 : 900; // 15 минут

        // Проверяем кэш, но преобразуем модели в массивы перед кэшированием
        $cached = Cache::get($cacheKey);
        if ($cached !== null && $cacheTime > 0) {
            // Восстанавливаем пагинатор из кэшированных данных
            return $this->restorePaginatorFromCache($cached, $request);
        }

        // Оптимизация: загружаем только необходимые relationships для списка
        $query = Product::with([
            'categories' => function ($q) {
                $q->select('categories.id', 'categories.name');
            }
        ]);

        // Поиск
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%');
            });
        }

        // Фильтрация по категории - оптимизировано через whereExists
        if ($request->has('category_id') && $request->category_id) {
            $query->whereExists(function ($subquery) use ($request) {
                $subquery->select(DB::raw(1))
                    ->from('product_categories')
                    ->whereColumn('product_categories.product_id', 'products.id')
                    ->where('product_categories.category_id', $request->category_id);
            });
        }

        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');

        // Специальная сортировка по имени (требует загрузки в память для кириллической сортировки)
        if ($sortBy === 'name') {
            // Оптимизация: ограничиваем количество перед сортировкой для больших списков
            // Используем более разумный лимит - только то, что нужно для текущей страницы
            $perPage = $request->get('per_page', 30);
            $page = $request->get('page', 1);
            $maxLimit = min($perPage * ($page + 2), 500); // Берем максимум 3 страницы вперед или 500
            
            $products = $query->limit($maxLimit)->get();
            $products = $products->sort(function ($a, $b) use ($sortOrder) {
                $isCyrA = preg_match('/^[А-Яа-яЁё]/u', $a->name);
                $isCyrB = preg_match('/^[А-Яа-яЁё]/u', $b->name);
                if ($isCyrA && !$isCyrB) return $sortOrder === 'asc' ? -1 : 1;
                if (!$isCyrA && $isCyrB) return $sortOrder === 'asc' ? 1 : -1;
                return $sortOrder === 'asc'
                    ? mb_strtolower($a->name) <=> mb_strtolower($b->name)
                    : mb_strtolower($b->name) <=> mb_strtolower($a->name);
            });

            // Получаем общее количество для корректной пагинации
            $totalCount = Product::when($request->has('search') && $request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('id', 'like', '%' . $search . '%');
                });
            })->when($request->has('category_id') && $request->category_id, function ($q) use ($request) {
                $q->whereExists(function ($subquery) use ($request) {
                    $subquery->select(DB::raw(1))
                        ->from('product_categories')
                        ->whereColumn('product_categories.product_id', 'products.id')
                        ->where('product_categories.category_id', $request->category_id);
                });
            })->count();

            // Фильтруем по правам доступа
            $filteredProducts = $products->filter(function ($product) {
                return \Illuminate\Support\Facades\Gate::allows('view', $product);
            });

            // Ручная пагинация
            $paged = new \Illuminate\Pagination\LengthAwarePaginator(
                $filteredProducts->forPage($page, $perPage)->values(),
                $totalCount,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            // Кэшируем только массив данных, а не модели
            if ($cacheTime > 0) {
                $cacheData = [
                    'items' => $paged->getCollection()->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'created_at' => $product->created_at?->toDateTimeString(),
                            'updated_at' => $product->updated_at?->toDateTimeString(),
                            'categories' => $product->categories->map(function ($category) {
                                return [
                                    'id' => $category->id,
                                    'name' => $category->name,
                                ];
                            })->toArray(),
                        ];
                    })->toArray(),
                    'total' => $paged->total(),
                    'per_page' => $paged->perPage(),
                    'current_page' => $paged->currentPage(),
                    'last_page' => $paged->lastPage(),
                    'path' => $paged->path(),
                    'query' => $paged->getOptions(),
                ];
                Cache::put($cacheKey, $cacheData, $cacheTime);
                // Отслеживаем ключ для инвалидации
                $trackingKey = 'cache_keys_' . CacheService::TAG_PRODUCTS;
                $keys = Cache::get($trackingKey, []);
                if (!in_array($cacheKey, $keys)) {
                    $keys[] = $cacheKey;
                    Cache::put($trackingKey, $keys, 86400);
                }
            }

            return $paged;
        } else {
            $query->orderBy($sortBy, $sortOrder);
            $products = $query->paginate($request->get('per_page', 30));
            $products->getCollection()->transform(function ($product) {
                return \Illuminate\Support\Facades\Gate::allows('view', $product) ? $product : null;
            });
            $products->setCollection($products->getCollection()->filter()->values());

            // Кэшируем только массив данных, а не модели
            if ($cacheTime > 0) {
                $cacheData = [
                    'items' => $products->getCollection()->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'created_at' => $product->created_at?->toDateTimeString(),
                            'updated_at' => $product->updated_at?->toDateTimeString(),
                            'categories' => $product->categories->map(function ($category) {
                                return [
                                    'id' => $category->id,
                                    'name' => $category->name,
                                ];
                            })->toArray(),
                        ];
                    })->toArray(),
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'path' => $products->path(),
                    'query' => $products->getOptions(),
                ];
                Cache::put($cacheKey, $cacheData, $cacheTime);
                // Отслеживаем ключ для инвалидации
                $trackingKey = 'cache_keys_' . CacheService::TAG_PRODUCTS;
                $keys = Cache::get($trackingKey, []);
                if (!in_array($cacheKey, $keys)) {
                    $keys[] = $cacheKey;
                    Cache::put($trackingKey, $keys, 86400);
                }
            }

            return $products;
        }
    }

    /**
     * Восстанавливает пагинатор из кэшированных данных
     */
    private function restorePaginatorFromCache(array $cacheData, Request $request): LengthAwarePaginator
    {
        // Восстанавливаем модели из массива
        $items = collect($cacheData['items'])->map(function ($item) {
            $product = new Product();
            $product->id = $item['id'];
            $product->name = $item['name'];
            $product->created_at = $item['created_at'] ? \Carbon\Carbon::parse($item['created_at']) : null;
            $product->updated_at = $item['updated_at'] ? \Carbon\Carbon::parse($item['updated_at']) : null;
            
            // Восстанавливаем категории
            $product->setRelation('categories', collect($item['categories'])->map(function ($cat) {
                $category = new Category();
                $category->id = $cat['id'];
                $category->name = $cat['name'];
                return $category;
            }));
            
            return $product;
        });

        return new LengthAwarePaginator(
            $items,
            $cacheData['total'],
            $cacheData['per_page'],
            $cacheData['current_page'],
            array_merge($cacheData['query'], ['path' => $cacheData['path']])
        );
    }

    public function getProductById(int $id): ?ProductDTO
    {
        // Кэшируем отдельные продукты на 1 час
        $cacheKey = 'product_' . $id;
        return Cache::remember($cacheKey, 3600, function () use ($id) {
            // Оптимизация: убираем загрузку orders.assignments - это может быть очень тяжело
            // Если нужны заказы, можно загрузить их отдельно с пагинацией
            $product = Product::with([
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
            ])->find($id);

            if (!$product) {
                return null;
            }

            return ProductDTO::fromModel($product);
        });
    }

    public function createProduct(array $data): ProductDTO
    {
        $product = Product::create($data);
        
        // Инвалидируем кэш продуктов
        CacheService::invalidateProductCaches();
        
        return ProductDTO::fromModel($product);
    }

    public function updateProduct(Product $product, array $data): ProductDTO
    {
        $product->update($data);
        
        // Инвалидируем кэш продуктов
        CacheService::invalidateProductCaches($product->id);
        Cache::forget('product_' . $product->id);
        
        return ProductDTO::fromModel($product);
    }

    public function deleteProduct(Product $product): bool
    {
        $productId = $product->id;
        $result = $product->delete();
        
        // Инвалидируем кэш продуктов
        CacheService::invalidateProductCaches($productId);
        Cache::forget('product_' . $productId);
        
        return $result;
    }

    public function getAllProducts(): array
    {
        $products = Product::with(['assignments.user', 'availableStages.roles', 'productStages.stage.roles'])->get();
        return array_map([ProductDTO::class, 'fromModel'], $products->toArray());
    }

    public function getProductsByStage(string $stageName): array
    {
        $products = Product::with(['assignments.user', 'availableStages.roles', 'productStages.stage.roles'])
            ->whereExists(function ($subquery) use ($stageName) {
                $subquery->select(DB::raw(1))
                    ->from('stages')
                    ->join('product_stages', 'stages.id', '=', 'product_stages.stage_id')
                    ->whereColumn('product_stages.product_id', 'products.id')
                    ->where('stages.name', $stageName);
            })
            ->get();
        return array_map([ProductDTO::class, 'fromModel'], $products->toArray());
    }

    public function getProductsByAssignment(int $userId): array
    {
        $products = Product::with(['assignments.user', 'availableStages.roles', 'productStages.stage.roles'])
            ->whereExists(function ($subquery) use ($userId) {
                $subquery->select(DB::raw(1))
                    ->from('product_assignments')
                    ->whereColumn('product_assignments.product_id', 'products.id')
                    ->where('product_assignments.user_id', $userId);
            })
            ->get();
        return array_map([ProductDTO::class, 'fromModel'], $products->toArray());
    }
}
