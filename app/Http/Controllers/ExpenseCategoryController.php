<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseCategoryRequest;
use App\Http\Requests\UpdateExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        $expenseCategories = ExpenseCategory::withCount('expenses')
            ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('expense-categories.index', compact('expenseCategories', 'startDate', 'endDate'));
    }

    public function store(StoreExpenseCategoryRequest $request)
    {
        $expenseCategory = ExpenseCategory::create($request->validated());

        return response()->json([
            'message' => 'Kategori biaya berhasil ditambahkan.',
            'data'    => $expenseCategory,
        ]);
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory)
    {
        $expenseCategory->update($request->validated());

        return response()->json([
            'message' => 'Kategori biaya berhasil diperbarui.',
            'data'    => $expenseCategory,
        ]);
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        if ($expenseCategory->expenses()->exists()) {
            return response()->json([
                'message' => 'Kategori biaya tidak bisa dihapus karena masih dipakai.',
            ], 422);
        }

        $expenseCategory->delete();

        return response()->json([
            'message' => 'Kategori biaya berhasil dihapus.',
        ]);
    }
}
