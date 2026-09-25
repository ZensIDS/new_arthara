<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomeRequest;
use App\Http\Requests\UpdateIncomeRequest;
use App\Models\Cash;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Services\IncomeService;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    public function __construct(protected IncomeService $service) {}

    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $search    = trim((string) $request->input('search', ''));

        $incomes = Income::query()
            ->with(['category:id,name', 'cash:id,name']) // hanya kolom yang dipakai di tabel
            ->when($startDate, fn($q) => $q->whereDate('income_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('income_date', '<=', $endDate))
            ->when($search !== '', function ($q) use ($search) {
                // Cari di semua kolom yang tampil di tabel: Kategori, Keterangan, Jumlah.
                $q->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhere('amount', 'like', "%{$search}%")
                        ->orWhereHas('category', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('income_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $incomeCategories = IncomeCategory::orderBy('name')->get(['id', 'name', 'affects_profit_loss']);
        $cashes           = Cash::where('is_active', true)->orderBy('name')->get(['id', 'name', 'type', 'current_balance']);

        if ($request->ajax()) {
            return view('incomes._table', compact('incomes'));
        }

        return view('incomes.index', compact('incomes', 'incomeCategories', 'cashes', 'startDate', 'endDate', 'search'));
    }

    public function store(StoreIncomeRequest $request)
    {
        try {
            $income = $this->service->create($request->validated());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Pemasukan berhasil dicatat.',
            'data'    => $income->load('category'),
        ]);
    }

    public function update(UpdateIncomeRequest $request, Income $income)
    {
        try {
            $this->service->update($income, $request->validated());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Pemasukan berhasil diperbarui.',
            'data'    => $income->load('category'),
        ]);
    }

    public function destroy(Income $income)
    {
        try {
            $this->service->delete($income);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Pemasukan berhasil dihapus.',
        ]);
    }
}