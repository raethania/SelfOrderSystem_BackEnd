<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Class ProductController
 *
 * Mengelola CRUD resource produk termasuk upload gambar.
 * Mendukung filter berdasarkan kategori, pencarian nama, dan status.
 * Otorisasi menggunakan ProductsPolicy.
 *
 * @package App\Http\Controllers\Admin
 */
class ProductController extends Controller
{
    use AuthorizesRequests;

    /**
     * Menampilkan daftar produk dengan filter, pencarian, dan paginasi.
     *
     * Mendukung filter berdasarkan kategori (category_id), pencarian nama (search),
     * dan status produk (available/unavailable). Relasi category di-eager load.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @queryParam  page         integer  Nomor halaman. Default: 1.
     * @queryParam  limit        integer  Jumlah item per halaman. Min: 1, Max: 50. Default: 10.
     * @queryParam  category_id  integer  Filter berdasarkan ID kategori.
     * @queryParam  search       string   Pencarian berdasarkan nama produk (partial match). Max: 100 karakter.
     * @queryParam  status       string   Filter berdasarkan status: available, unavailable.
     *
     * @return \Illuminate\Http\JsonResponse  200 — Daftar produk dengan paginasi.
     *
     * @authenticated
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

        // Load relasi category
        $query = Products::with('category');

        // Apply filters
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
     * Membuat produk baru.
     *
     * Menyimpan data produk beserta upload gambar (opsional) ke disk 'public'.
     * Relasi category di-load pada response.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @bodyParam  category_id  integer  required  ID kategori produk (harus ada di tabel categories).
     * @bodyParam  name         string   required  Nama produk. Max: 100 karakter.
     * @bodyParam  description  string   nullable  Deskripsi produk.
     * @bodyParam  price        numeric  required  Harga produk. Min: 0.
     * @bodyParam  stock        integer  required  Jumlah stok. Min: 0.
     * @bodyParam  image        file     nullable  Gambar produk (jpg, jpeg, png, webp). Max: 2MB.
     * @bodyParam  status       string   optional  Status produk: available atau unavailable.
     *
     * @return \Illuminate\Http\JsonResponse  201 — Produk berhasil dibuat.
     *
     * @authenticated
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

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Products::create($data);
        
        // Load category for response
        $product->load('category');

        return $this->successResponse($this->successMessage('created'), $product, 201);
    }

    /**
     * Menampilkan detail produk berdasarkan ID.
     *
     * Relasi category di-eager load pada response.
     *
     * @param  \App\Models\Products  $product  Instance produk (route model binding).
     *
     * @return \Illuminate\Http\JsonResponse  200 — Detail produk.
     *
     * @authenticated
     */
    public function show(Products $product)
    {
        $this->authorize('view', $product);
        
        $product->load('category');

        return $this->successResponse($this->availableDataMessage('Product'), $product);
    }

    /**
     * Mengupdate produk yang sudah ada.
     *
     * Mendukung partial update (hanya field yang dikirim yang diupdate).
     * Jika gambar baru di-upload, gambar lama akan otomatis dihapus dari storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Products      $product  Instance produk (route model binding).
     *
     * @bodyParam  category_id  integer  optional  ID kategori produk baru.
     * @bodyParam  name         string   optional  Nama produk baru. Max: 100 karakter.
     * @bodyParam  description  string   nullable  Deskripsi produk.
     * @bodyParam  price        numeric  optional  Harga produk baru. Min: 0.
     * @bodyParam  stock        integer  optional  Jumlah stok baru. Min: 0.
     * @bodyParam  image        file     nullable  Gambar produk baru (jpg, jpeg, png, webp). Max: 2MB.
     * @bodyParam  status       string   optional  Status produk: available atau unavailable.
     *
     * @return \Illuminate\Http\JsonResponse  200 — Produk berhasil diupdate.
     *
     * @authenticated
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

        // Handle image replacement
        if ($request->hasFile('image')) {
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
     * Menghapus produk (soft delete).
     *
     * Produk dihapus secara soft delete sehingga masih tersimpan di database
     * dan dapat di-restore jika diperlukan.
     *
     * @param  \App\Models\Products  $product  Instance produk (route model binding).
     *
     * @return \Illuminate\Http\JsonResponse  200 — Produk berhasil dihapus.
     *
     * @authenticated
     */
    public function destroy(Products $product)
    {
        $this->authorize('delete', $product);

        $tempProduct = $product;
        
        // Soft delete product
        $product->delete();

        return $this->successResponse($this->successMessage('deleted'), $tempProduct);
    }
}
