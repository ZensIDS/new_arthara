<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCashRequest;
use App\Http\Requests\UpdateCashRequest;
use App\Models\Cash;

class CashController extends Controller
{
    public function index()
    {
        $cashes = Cash::latest()->paginate(10)->withQueryString();

        $totalBalance = Cash::where('is_active', true)->sum('current_balance');

        return view('cashes.index', compact('cashes', 'totalBalance'));
    }

    public function store(StoreCashRequest $request)
    {
        $data                    = $request->validated();
        $data['current_balance'] = $data['initial_balance'];

        $cash = Cash::create($data);

        return response()->json([
            'message' => 'Kas berhasil ditambahkan.',
            'data'    => $cash,
        ]);
    }

    public function update(UpdateCashRequest $request, Cash $cash)
    {
        $data = $request->validated();

        // Saldo berjalan tetap ikut selisih saat saldo awal diubah, supaya
        // penyesuaian manual (kalau nanti sudah ada trx) tidak ketimpa balik ke saldo awal.
        $diff                    = $data['initial_balance'] - $cash->initial_balance;
        $data['current_balance'] = $cash->current_balance + $diff;

        $cash->update($data);

        return response()->json([
            'message' => 'Kas berhasil diperbarui.',
            'data'    => $cash,
        ]);
    }

    public function destroy(Cash $cash)
    {
        if ((float) $cash->current_balance !== 0.0) {
            return response()->json([
                'message' => 'Kas tidak bisa dihapus karena saldo belum nol.',
            ], 422);
        }

        $cash->delete();

        return response()->json([
            'message' => 'Kas berhasil dihapus.',
        ]);
    }
}