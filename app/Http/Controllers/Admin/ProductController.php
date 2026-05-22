<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ProductController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource with filtering, search, and pagination.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Products::class);

        $request->validate([
            'page'        => 'integer|min:1',
            'limit'       => 'integer|min:1|max:50',
            'category_id' => 'integer|exists:categories,id',
            'search'      => 'string|max:100',
            'status'      => 'string|in:available,unavailable',
        ]);

        $limit = $request->input('limit', 10);

        $query = Products::with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->paginate($limit);

        if ($products->isEmpty()) {
            return $this->successResponse($this->emptyDataMessage('product'));
        }

        return $this->paginateResponse($this->availableDataMessage('Product'), $products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Products::class);

        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status'      => 'in:available,unavailable',
        ]);

        $data = $request->only(['category_id', 'name', 'description', 'price', 'stock', 'status']);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Products::create($data);
        $product->load('category');

        return $this->successResponse($this->successMessage('created'), $product, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Products $product)
    {
        $this->authorize('view', $product);
        $product->load('category');

        return $this->successResponse($this->availableDataMessage('Product'), $product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Products $product)
    {
        $this->authorize('update', $product);

        $request->validate([
            'category_id' => 'sometimes|required|exists:categories,id',
            'name'        => 'sometimes|required|string|max:100',
            'description' => 'nullable|string',
            'price'       => 'sometimes|required|numeric|min:0',
            'stock'       => 'sometimes|required|integer|min:0',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status'      => 'in:available,unavailable',
        ]);

        $data = $request->only(['category_id', 'name', 'description', 'price', 'stock', 'status']);

        if ($request->hasFile('image')) {
            // Hapus gambar lama jika ada
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);
        $product->load('category');

        return $this->successResponse($this->successMessage('updated'), $product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Products $product)
    {
        $this->authorize('delete', $product);

        $tempProduct = $product;
        $product->delete();

        return $this->successResponse($this->successMessage('deleted'), $tempProduct);
    }
}
