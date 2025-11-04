<?php

namespace App\Repositories;

use App\Models\Product;
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

        return Cache::remember($cacheKey, $cacheTime, function () use ($request) {
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

                // Ручная пагинация
                $paged = new \Illuminate\Pagination\LengthAwarePaginator(
                    $products->forPage($page, $perPage)->values(),
                    $totalCount,
                    $perPage,
                    $page,
                    ['path' => $request->url(), 'query' => $request->query()]
                );

                $paged->getCollection()->transform(function ($product) {
                    return \Illuminate\Support\Facades\Gate::allows('view', $product) ? $product : null;
                });
                $paged->setCollection($paged->getCollection()->filter()->values());
                return $paged;
            } else {
                $query->orderBy($sortBy, $sortOrder);
                $products = $query->paginate($request->get('per_page', 30));
                $products->getCollection()->transform(function ($product) {
                    return \Illuminate\Support\Facades\Gate::allows('view', $product) ? $product : null;
                });
                $products->setCollection($products->getCollection()->filter()->values());
                return $products;
            }
        });
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
