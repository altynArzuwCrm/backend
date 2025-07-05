<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if (Gate::denies('viewAny', Product::class)) {
            return response()->json([
                'message' => 'Not Authorized'
            ], 403);
        }

        $perPage = $request->get('per_page', 10);
        $query = Product::with('designer');

        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    public function show(Product $product)
    {
        if (Gate::denies('view', $product)) {
            return response()->json([
                'message' => 'Not Authorized'
            ], 403);
        }

        return $product->load('designer');
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
            'is_workshop_required' => 'boolean',
            'workshop_type' => 'nullable|in:montage,binding',
        ]);

        $product = Product::create($data);

        return response()->json($product->load('designer'), 201);
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
            'is_workshop_required' => 'sometimes|boolean',
            'workshop_type' => 'nullable|in:montage,binding',
        ]);

        $product->update($data);

        return response()->json($product->load('designer'));
    }

    public function destroy(Product $product)
    {
        if (Gate::denies('delete', $product)) {
            return response()->json([
                'message' => 'Not Authorized'
            ], 403);
        }

        $product->delete();

        return response()->json(['message' => 'Товар удалён']);
    }
}
