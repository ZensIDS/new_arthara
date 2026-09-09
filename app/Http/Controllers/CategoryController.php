<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        $categories = Category::withCount('products')
            ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('categories.index', compact('categories', 'startDate', 'endDate'));
    }

    public function store(StoreCategoryRequest $request)
    {
        $validated = $request->validated();
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);

        $category = Category::create($validated);

        return response()->json([
            'message' => 'Kategori berhasil ditambahkan.',
            'data'    => $category,
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $validated = $request->validated();
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);

        $category->update($validated);

        return response()->json([
            'message' => 'Kategori berhasil diperbarui.',
            'data'    => $category,
        ]);
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return response()->json([
                'message' => 'Kategori tidak bisa dihapus karena masih dipakai produk.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Kategori berhasil dihapus.',
        ]);
    }
}
