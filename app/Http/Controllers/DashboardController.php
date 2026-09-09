<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    public function index(Request $request)
    {
        // Default: bulan berjalan. Bisa di-override lewat query string ?start_date=&end_date=
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfMonth();

        // Jaga-jaga kalau user pilih rentang terbalik
        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        $startDateStr = $startDate->toDateString();
        $endDateStr   = $endDate->toDateString();

        $profitLoss = $this->reportService->profitLossReport($startDateStr, $endDateStr);
        $cashFlow   = $this->reportService->cashFlowReport($startDateStr, $endDateStr);

        $expenseBreakdown = $this->reportService->expenseBreakdown($startDateStr, $endDateStr);
        $topProducts      = $this->reportService->topSellingProducts($startDateStr, $endDateStr, 5);
        $dailySalesTrend  = $this->reportService->dailySalesTrend($startDateStr, $endDateStr);

        $totalReceivable = $this->reportService->accountReceivableReport()->sum('remaining_balance');
        $totalPayable    = $this->reportService->accountPayableReport()->sum('remaining_balance');

        $stockValue = Product::query()
            ->join('stock_batches', 'stock_batches.product_id', '=', 'products.id')
            ->where('stock_batches.qty_remaining', '>', 0)
            ->sum(DB::raw('stock_batches.qty_remaining * stock_batches.buy_price'));

        $lowStockProducts = Product::with('category:id,name')
            ->where('is_active', true)
            ->where('qty_on_hand', '<=', 5)
            ->orderBy('qty_on_hand')
            ->limit(5)
            ->get(['id', 'category_id', 'name', 'qty_on_hand']);

        return view('dashboard', [
            'startDate'        => $startDateStr,
            'endDate'          => $endDateStr,
            'profitLoss'       => $profitLoss,
            'cashFlow'         => $cashFlow,
            'expenseBreakdown' => $expenseBreakdown,
            'topProducts'      => $topProducts,
            'dailySalesTrend'  => $dailySalesTrend,
            'totalReceivable'  => $totalReceivable,
            'totalPayable'     => $totalPayable,
            'stockValue'       => (float) $stockValue,
            'lowStockProducts' => $lowStockProducts,
        ]);
    }
}
