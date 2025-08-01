<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\ProductResource;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if (Gate::denies('viewAny', Product::class)) {
            return response()->json([
                'message' => 'Not Authorized'
            ], 403);
        }

        $allowedPerPage = [10, 20, 50, 100, 200, 500];
        $perPage = (int) $request->get('per_page', 30);
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 30;
        }
        $query = Product::with(['assignments.user', 'orders.assignments', 'availableStages.roles']);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%');
            });
        }

        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'desc');
        if ($sortBy === 'name') {
            // Сортируем по name: сначала кириллица, потом латиница, с поддержкой asc/desc
            $products = $query->get();
            $products = $products->sort(function ($a, $b) use ($sortOrder) {
                $isCyrA = preg_match('/^[А-Яа-яЁё]/u', $a->name);
                $isCyrB = preg_match('/^[А-Яа-яЁё]/u', $b->name);
                if ($isCyrA && !$isCyrB) return $sortOrder === 'asc' ? -1 : 1;
                if (!$isCyrA && $isCyrB) return $sortOrder === 'asc' ? 1 : -1;
                // Если оба на кириллице или оба на латинице — обычная сортировка
                return $sortOrder === 'asc'
                    ? mb_strtolower($a->name) <=> mb_strtolower($b->name)
                    : mb_strtolower($b->name) <=> mb_strtolower($a->name);
            });
            // Пагинация вручную
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
            return ProductResource::collection($paged);
        } else {
            $query->orderBy($sortBy, $sortOrder);
            $products = $query->paginate($perPage);
            $products->getCollection()->transform(function ($product) {
                return \Illuminate\Support\Facades\Gate::allows('view', $product) ? $product : null;
            });
            $products->setCollection($products->getCollection()->filter()->values());
            return ProductResource::collection($products);
        }
    }

    public function show(Product $product)
    {
        if (Gate::denies('view', $product)) {
            return response()->json([
                'message' => 'Not Authorized'
            ], 403);
        }

        $product->load(['assignments.user', 'orders.assignments', 'availableStages.roles']);
        return new ProductResource($product);
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
            'designer_id' => 'nullable|exists:users,id',
            'print_operator_id' => 'nullable|exists:users,id',
            'workshop_worker_id' => 'nullable|exists:users,id',
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
        }

        $product->load(['assignments.user', 'orders.assignments', 'availableStages.roles']);
        return response()->json(['data' => new ProductResource($product)], 201);
    }

    public function allProducts()
    {
        if (Gate::denies('allProducts', Product::class)) {
            abort(403, 'Доступ запрещён');
        }

        $products = Cache::remember('all_products', 60, function () {
            return Product::with(['assignments.user', 'orders.assignments', 'availableStages.roles'])->orderBy('id')->get();
        });
        return ProductResource::collection($products);
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
            'designer_id' => 'nullable|exists:users,id',
            'print_operator_id' => 'nullable|exists:users,id',
            'workshop_worker_id' => 'nullable|exists:users,id',
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
        }

        $product->load(['assignments.user', 'orders.assignments', 'availableStages.roles']);
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

        return response()->json(['message' => 'Товар удалён']);
    }
}
