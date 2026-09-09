<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    /**
     * Halaman cek stok cepat (real-time): total stok + breakdown batch per
     * produk. Beda dari reports.stock yang diperlakukan sebagai laporan
     * formal — data sumbernya sama-sama dari ReportService, cuma tampilannya
     * dibuat untuk pengecekan harian yang cepat (expand/collapse per produk).
     *
     * Pencarian & pagination dilakukan di server (bukan lagi client-side atas
     * seluruh produk) supaya halaman tetap ringan walau jumlah produk sudah
     * ribuan baris.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $stock = $this->reportService->stockReportPaginated($search, 25);
        $kpis  = $this->reportService->stockKpis();

        return view('stock.index', compact('stock', 'kpis', 'search'));
    }
}
