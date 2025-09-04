<?php

namespace App\Repositories;

use App\Models\Product;
use App\DTOs\ProductDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductRepository
{
    public function getPaginatedProducts(Request $request): LengthAwarePaginator
    {
        $query = Product::with(['assignments.user', 'orders.assignments', 'availableStages.roles', 'productStages.stage.roles', 'categories']);

        // Поиск
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%');
            });
        }

        // Фильтрация по категории
        if ($request->has('category_id') && $request->category_id) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');

        // Специальная сортировка по имени
        if ($sortBy === 'name') {
            $products = $query->get();
            $products = $products->sort(function ($a, $b) use ($sortOrder) {
                $isCyrA = preg_match('/^[А-Яа-яЁё]/u', $a->name);
                $isCyrB = preg_match('/^[А-Яа-яЁё]/u', $b->name);
                if ($isCyrA && !$isCyrB) return $sortOrder === 'asc' ? -1 : 1;
                if (!$isCyrA && $isCyrB) return $sortOrder === 'asc' ? 1 : -1;
                return $sortOrder === 'asc'
                    ? mb_strtolower($a->name) <=> mb_strtolower($b->name)
                    : mb_strtolower($b->name) <=> mb_strtolower($a->name);
            });

            // Ручная пагинация
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 30);
            $paged = new \Illuminate\Pagination\LengthAwarePaginator(
                $products->forPage($page, $perPage)->values(),
                $products->count(),
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
    }

    public function getProductById(int $id): ?ProductDTO
    {
        // Кэшируем отдельные продукты на 15 минут
        $cacheKey = 'product_' . $id;
        return Cache::remember($cacheKey, 900, function () use ($id) {
            $product = Product::with(['assignments.user', 'orders.assignments', 'availableStages.roles', 'productStages.stage.roles', 'categories'])->find($id);

            if (!$product) {
                return null;
            }

            return ProductDTO::fromModel($product);
        });
    }

    public function createProduct(array $data): ProductDTO
    {
        $product = Product::create($data);
        return ProductDTO::fromModel($product);
    }

    public function updateProduct(Product $product, array $data): ProductDTO
    {
        $product->update($data);
        return ProductDTO::fromModel($product);
    }

    public function deleteProduct(Product $product): bool
    {
        return $product->delete();
    }

    public function getAllProducts(): array
    {
        $products = Product::with(['assignments.user', 'availableStages.roles', 'productStages.stage.roles'])->get();
        return array_map([ProductDTO::class, 'fromModel'], $products->toArray());
    }

    public function getProductsByStage(string $stageName): array
    {
        $products = Product::with(['assignments.user', 'availableStages.roles', 'productStages.stage.roles'])
            ->whereHas('availableStages', function ($query) use ($stageName) {
                $query->where('name', $stageName);
            })
            ->get();
        return array_map([ProductDTO::class, 'fromModel'], $products->toArray());
    }

    public function getProductsByAssignment(int $userId): array
    {
        $products = Product::with(['assignments.user', 'availableStages.roles', 'productStages.stage.roles'])
            ->whereHas('assignments', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->get();
        return array_map([ProductDTO::class, 'fromModel'], $products->toArray());
    }
}
