<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Получить список всех категорий
     */
    public function index(): JsonResponse
    {
        $categories = Category::orderBy('name')->get();

        return response()->json([
            'data' => $categories
        ]);
    }

    /**
     * Создать новую категорию
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        $category = Category::create([
            'name' => $request->name,
        ]);

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
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
        ]);

        $category->update([
            'name' => $request->name,
        ]);

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
        // Проверяем, есть ли продукты в этой категории
        if ($category->products()->count() > 0) {
            return response()->json([
                'message' => 'Нельзя удалить категорию, в которой есть продукты'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Категория успешно удалена'
        ]);
    }

    /**
     * Получить продукты категории
     */
    public function products(Category $category): JsonResponse
    {
        $products = $category->products()->with(['categories', 'availableStages'])->get();

        return response()->json([
            'data' => $products
        ]);
    }
}
