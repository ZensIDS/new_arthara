<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleSourceRequest;
use App\Http\Requests\UpdateSaleSourceRequest;
use App\Models\SaleSource;
use Illuminate\Http\Request;

class SaleSourceController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        $saleSources = SaleSource::withCount('salesOrders')
            ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('sale-sources.index', compact('saleSources', 'startDate', 'endDate'));
    }

    public function store(StoreSaleSourceRequest $request)
    {
        $saleSource = SaleSource::create($request->validated());

        return response()->json([
            'message' => 'Asal penjualan berhasil ditambahkan.',
            'data'    => $saleSource,
        ]);
    }

    public function update(UpdateSaleSourceRequest $request, SaleSource $saleSource)
    {
        $saleSource->update($request->validated());

        return response()->json([
            'message' => 'Asal penjualan berhasil diperbarui.',
            'data'    => $saleSource,
        ]);
    }

    public function destroy(SaleSource $saleSource)
    {
        if ($saleSource->salesOrders()->exists()) {
            return response()->json([
                'message' => 'Asal penjualan tidak bisa dihapus karena masih dipakai transaksi.',
            ], 422);
        }

        $saleSource->delete();

        return response()->json([
            'message' => 'Asal penjualan berhasil dihapus.',
        ]);
    }
}
