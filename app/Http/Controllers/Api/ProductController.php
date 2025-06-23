<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Product::class);

        return Product::with('defaultDesigner')->get();
    }

    public function show(Product $product)
    {
        $this->authorize('view', $product);

        return $product->load('defaultDesigner');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Product::class);

        $data = $request->validate([
            'name' => 'required|string',
            'default_designer_id' => 'nullable|exists:users,id',
            'is_workshop_required' => 'boolean',
            'workshop_type' => 'nullable|in:montage,binding',
        ]);

        $product = Product::create($data);

        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $data = $request->validate([
            'name' => 'sometimes|string',
            'default_designer_id' => 'nullable|exists:users,id',
            'is_workshop_required' => 'sometimes|boolean',
            'workshop_type' => 'nullable|in:montage,binding',
        ]);

        $product->update($data);

        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response()->json(['message' => 'Товар удалён']);
    }
}
