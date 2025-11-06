<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use App\Services\CacheService;

class CategoryController extends Controller
{
    /**
     * Получить список всех категорий
     */
    public function index(Request $request): JsonResponse
    {
        if (Gate::denies('viewAny', Category::class)) {
            return response()->json([
                'message' => 'Not Authorized'
            ], 403);
        }

        // Проверяем, нужно ли принудительно обновить кэш
        $cacheTime = $request->has('force_refresh') ? 0 : 900;
        
        // Кэшируем результаты на 15 минут
        $cacheKey = 'categories_' . md5($request->fullUrl());
        $result = CacheService::rememberWithTags($cacheKey, $cacheTime, function () use ($request) {
            $query = Category::query();

            // Поиск
            if ($request->has('search') && $request->search) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            // Сортировка
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Пагинация
            $perPage = $request->get('per_page', 30);
            $categories = $query->paginate($perPage);

            return [
                'data' => $categories->items(),
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'total' => $categories->total(),
                'per_page' => $categories->perPage(),
                'from' => $categories->firstItem(),
                'to' => $categories->lastItem(),
            ];
        }, [CacheService::TAG_CATEGORIES]);

        return response()->json($result);
    }

    /**
     * Создать новую категорию
     */
    public function store(Request $request): JsonResponse
    {
        if (Gate::denies('create', Category::class)) {
            return response()->json([
                'message' => 'Not Authorized'
            ], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        $category = Category::create($data);

        // Очищаем кэш категорий
        CacheService::invalidateCategoryCaches();

        return response()->json([
            'data' => $category,
            'message' => 'Категория успешно создана'
        ], 201);
    }

    /**
     * Получить конкретную категорию
     */
    public function show(Category $category): JsonResponse
    {
        if (Gate::denies('view', $category)) {
            return response()->json([
                'message' => 'Not Authorized'
            ], 403);
        }

        // Используем with() вместо load() для предотвращения N+1 проблемы
        $category = Category::with('products')->find($category->id);

        return response()->json([
            'data' => $category
        ]);
    }

    /**
     * Обновить категорию
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        if (Gate::denies('update', $category)) {
            return response()->json([
                'message' => 'Not Authorized'
            ], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
        ]);

        $category->update($data);

        // Очищаем кэш категорий
        CacheService::invalidateCategoryCaches();

        return response()->json([
            'data' => $category,
            'message' => 'Категория успешно обновлена'
        ]);
    }

    /**
     * Удалить категорию
     */
    public function destroy(Category $category): JsonResponse
    {
        if (Gate::denies('delete', $category)) {
            return response()->json([
                'message' => 'Not Authorized'
            ], 403);
        }

        // Проверяем, есть ли продукты в этой категории
        if ($category->products()->count() > 0) {
            return response()->json([
                'message' => 'Нельзя удалить категорию, в которой есть продукты'
            ], 422);
        }

        $category->delete();

        // Очищаем кэш категорий
        CacheService::invalidateCategoryCaches();

        return response()->json([
            'message' => 'Категория успешно удалена'
        ]);
    }

    /**
     * Получить все категории (для селектов)
     */
    public function all(): JsonResponse
    {
        $categories = Category::orderBy('name')->get();

        return response()->json([
            'data' => $categories
        ]);
    }

    /**
     * Получить продукты категории
     */
    public function products(Category $category): JsonResponse
    {
        if (Gate::denies('view', $category)) {
            return response()->json([
                'message' => 'Not Authorized'
            ], 403);
        }

        $products = $category->products()->with(['categories', 'availableStages'])->get();

        return response()->json([
            'data' => $products
        ]);
    }
}
