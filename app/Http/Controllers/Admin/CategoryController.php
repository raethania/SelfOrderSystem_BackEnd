<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Class CategoryController
 *
 * Mengelola CRUD resource kategori produk.
 * Otorisasi menggunakan CategoriesPolicy.
 *
 * @package App\Http\Controllers\Admin
 */
class CategoryController extends Controller
{
    use AuthorizesRequests;
    
    /**
     * Menampilkan daftar kategori dengan filter dan paginasi.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @queryParam  name   string  Pencarian berdasarkan nama kategori (partial match).
     * @queryParam  limit  integer Jumlah item per halaman. Default: 10.
     *
     * @return \Illuminate\Http\JsonResponse  200 — Daftar kategori dengan paginasi.
     *
     * @authenticated
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Categories::class);

        $query = Categories::query();

        // Apply search filter
        if ($request->filled("name")){
            $query->where("name","like","%". $request->name ."%");
        }

        $limit = $request->input("limit", 10);
        $categories = $query->paginate($limit);

        if ($categories->isEmpty()){
            return $this->successResponse($this->emptyDataMessage("categories"));
        } else {
            return $this->paginateResponse($this->availableDataMessage("categories"), $categories);
        }
    }

    /**
     * Membuat kategori baru.
     *
     * Slug otomatis di-generate dari nama kategori menggunakan Str::slug().
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @bodyParam  name  string  required  Nama kategori. Min: 3, Max: 50 karakter.
     *
     * @return \Illuminate\Http\JsonResponse  200 — Kategori berhasil dibuat.
     *
     * @authenticated
     */
    public function store(Request $request)
    {
        $this->authorize('create', Categories::class);

        $validated = $request->validate([
            'name' => 'required|string|min:3|max:50'
        ]);

        // Generate slug from name
        $validated['slug'] = Str::slug($validated['name']);
        
        $category = Categories::create($validated);

        return $this->successResponse($this->successMessage('created'), $category);
    }

    /**
     * Menampilkan detail kategori berdasarkan ID.
     *
     * @param  \App\Models\Categories  $category  Instance kategori (route model binding).
     *
     * @return \Illuminate\Http\JsonResponse  200 — Detail kategori.
     *                                        404 — Kategori tidak ditemukan.
     *
     * @authenticated
     */
    public function show(Categories $category)
    {
        $this->authorize('view', $category);

        if (!$category){
            return $this->errorResponse($this->emptyDataMessage("category"), [], 404);
        } else {
            return $this->successResponse($this->availableDataMessage("category"), $category);
        }
    }

    /**
     * Mengupdate kategori yang sudah ada.
     *
     * Slug akan di-regenerate otomatis dari nama yang baru.
     *
     * @param  \Illuminate\Http\Request     $request
     * @param  \App\Models\Categories       $category  Instance kategori (route model binding).
     *
     * @bodyParam  name  string  required  Nama kategori baru. Min: 3, Max: 50 karakter.
     *
     * @return \Illuminate\Http\JsonResponse  200 — Kategori berhasil diupdate.
     *
     * @authenticated
     */
    public function update(Request $request, Categories $category)
    {
        $this->authorize('update', $category);

        $validated = $request->validate([
            'name' => 'required|string|min:3|max:50'
        ]);

        // Regenerate slug from new name
        $validated['slug'] = Str::slug($validated['name']);
        
        $category->update($validated);

        return $this->successResponse($this->successMessage('updated'), $category);
    }

    /**
     * Menghapus kategori (soft delete).
     *
     * @param  \App\Models\Categories  $category  Instance kategori (route model binding).
     *
     * @return \Illuminate\Http\JsonResponse  200 — Kategori berhasil dihapus.
     *
     * @authenticated
     */
    public function destroy(Categories $category)
    {
        $this->authorize('delete', $category);

        $categoryTemp = $category;
        
        // Soft delete category
        $category->delete();

        return $this->successResponse($this->successMessage('updated'), $categoryTemp);
    }
}
