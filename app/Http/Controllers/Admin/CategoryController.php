<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Categories::query();

        if ($request->filled("name")){
            $query->where("name","like","%". $request->name ."%");
        }

        if ($request->filled("per_page")){
            $categories = $query->paginate($request->per_page);
        } else {
            $categories = $query->paginate(15);
        }

        if ($categories->isEmpty()){
            return $this->successResponse($this->emptyDataMessage("categories"));
        } else {
            return $this->successResponse($this->availableDataMessage("categories"), $categories);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $categories = Categories::paginate(15);

        if ($categories->isEmpty()){
            return $this->successResponse($this->emptyDataMessage("categories"));
        } else {
            return $this->paginateResponse($this->availableDataMessage("categories"), $categories);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:50'
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $category = Categories::create($validated);

        return $this->successResponse($this->successMessage('created'), $category);
    }

    /**
     * Display the specified resource.
     */
    public function show(Categories $category)
    {
        if (!$category){
            return $this->errorResponse($this->emptyDataMessage("categories"), [], 404);
            } else {
            return $this->successResponse($this->emptyDataMessage("category"));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Categories $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:50'
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $category->update($validated);

        return $this->successResponse($this->successMessage('updated'), $category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Categories $category)
    {
        $categoryTemp = $category;
        $category->delete();

        return $this->successResponse($this->successMessage('updated'), $categoryTemp);
    }
}
