<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeCategoryController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfitShareController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\SaleSourceController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SalesReturnController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

// --- Auth (guest only) ---
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'create'])->name('login');
    Route::post('login', [AuthController::class, 'store']);
});

Route::post('logout', [AuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// --- Halaman yang butuh login (semua role: superadmin & viewer boleh lihat) ---
Route::middleware('auth')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Route 'create' WAJIB didaftarkan sebelum resource(['index','show']) di bawah ini,
    // supaya path /purchase-orders/create tidak ketangkep duluan oleh wildcard
    // /purchase-orders/{purchaseOrder} milik route 'show'.
    Route::get('purchase-orders/create', [PurchaseOrderController::class, 'create'])
        ->middleware('role:superadmin')
        ->name('purchase-orders.create');

    Route::get('purchase-orders/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])
        ->middleware('role:superadmin')
        ->name('purchase-orders.edit');

    Route::resource('purchase-orders', PurchaseOrderController::class)
        ->only(['index', 'show']);

    // Sama seperti purchase-orders/create di atas: harus didaftarkan sebelum
    // resource(['index','show']) supaya /sales-orders/create tidak ketangkep
    // wildcard /sales-orders/{salesOrder} milik route 'show'.
    Route::get('sales-orders/create', [SalesOrderController::class, 'create'])
        ->middleware('role:superadmin')
        ->name('sales-orders.create');

    Route::get('sales-orders/{salesOrder}/edit', [SalesOrderController::class, 'edit'])
        ->middleware('role:superadmin')
        ->name('sales-orders.edit');

    Route::resource('sales-orders', SalesOrderController::class)
        ->only(['index', 'show']);

    // Modul di bawah ini pakai pola index + modal (create/edit AJAX),
    // jadi cuma butuh route 'index' — store/update/destroy ada di grup superadmin.
    Route::get('stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('sale-sources', [SaleSourceController::class, 'index'])->name('sale-sources.index');
    Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('expense-categories', [ExpenseCategoryController::class, 'index'])->name('expense-categories.index');
    Route::get('incomes', [IncomeController::class, 'index'])->name('incomes.index');
    Route::get('income-categories', [IncomeCategoryController::class, 'index'])->name('income-categories.index');
    Route::get('profit-shares', [ProfitShareController::class, 'index'])->name('profit-shares.index');

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('stock', [ReportController::class, 'stock'])->name('stock');
        Route::get('profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
        Route::get('cash-flow', [ReportController::class, 'cashFlow'])->name('cash-flow');
        Route::get('payable', [ReportController::class, 'payable'])->name('payable');
        Route::get('receivable', [ReportController::class, 'receivable'])->name('receivable');
        Route::get('sales-return', [ReportController::class, 'salesReturn'])->name('sales-return');
        Route::get('purchase-return', [ReportController::class, 'purchaseReturn'])->name('purchase-return');
        Route::get('expenses', [ReportController::class, 'expenses'])->name('expenses');
        Route::get('incomes', [ReportController::class, 'incomes'])->name('incomes');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('stock', [ReportExportController::class, 'stock'])->name('stock');
            Route::get('profit-loss', [ReportExportController::class, 'profitLoss'])->name('profit-loss');
            Route::get('cash-flow', [ReportExportController::class, 'cashFlow'])->name('cash-flow');
            Route::get('payable', [ReportExportController::class, 'payable'])->name('payable');
            Route::get('receivable', [ReportExportController::class, 'receivable'])->name('receivable');
            Route::get('sales-return', [ReportExportController::class, 'salesReturn'])->name('sales-return');
            Route::get('purchase-return', [ReportExportController::class, 'purchaseReturn'])->name('purchase-return');
            Route::get('expenses', [ReportExportController::class, 'expenses'])->name('expenses');
            Route::get('incomes', [ReportExportController::class, 'incomes'])->name('incomes');
        });
    });
});

// --- Aksi tulis (create/update/delete) — KHUSUS superadmin ---
// Dipisah dari grup 'auth' di atas supaya viewer otomatis 403 di semua
// aksi tulis tanpa perlu diingat satu-satu tiap kali bikin route baru.
// Product/Category/Supplier/Customer/Expense/ExpenseCategory: store/update/destroy
// dipanggil via AJAX dari modal di halaman index masing-masing (tidak ada halaman create/edit terpisah).
Route::middleware(['auth', 'role:superadmin'])->group(function () {

    Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])
        ->name('purchase-orders.store');
    Route::put('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])
        ->name('purchase-orders.update');
    Route::delete('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])
        ->name('purchase-orders.destroy');
    Route::post('purchase-orders/{purchaseOrder}/payments', [PurchaseOrderController::class, 'storePayment'])
        ->name('purchase-orders.payments.store');
    Route::put('purchase-orders/{purchaseOrder}/payments/{payment}', [PurchaseOrderController::class, 'updatePayment'])
        ->name('purchase-orders.payments.update');
    Route::post('purchase-orders/{purchaseOrder}/returns', [PurchaseReturnController::class, 'store'])
        ->name('purchase-orders.returns.store');
    Route::delete('purchase-orders/{purchaseOrder}/returns/{return}', [PurchaseReturnController::class, 'destroy'])
        ->name('purchase-orders.returns.destroy');

    Route::post('sales-orders', [SalesOrderController::class, 'store'])
        ->name('sales-orders.store');
    Route::put('sales-orders/{salesOrder}', [SalesOrderController::class, 'update'])
        ->name('sales-orders.update');
    Route::delete('sales-orders/{salesOrder}', [SalesOrderController::class, 'destroy'])
        ->name('sales-orders.destroy');
    Route::post('sales-orders/{salesOrder}/payments', [SalesOrderController::class, 'storePayment'])
        ->name('sales-orders.payments.store');
    Route::put('sales-orders/{salesOrder}/payments/{payment}', [SalesOrderController::class, 'updatePayment'])
        ->name('sales-orders.payments.update');
    Route::post('sales-orders/{salesOrder}/returns', [SalesReturnController::class, 'store'])
        ->name('sales-orders.returns.store');
    Route::delete('sales-orders/{salesOrder}/returns/{return}', [SalesReturnController::class, 'destroy'])
        ->name('sales-orders.returns.destroy');

    Route::resource('products', ProductController::class)
        ->only(['store', 'update', 'destroy']);

    Route::resource('categories', CategoryController::class)
        ->only(['store', 'update', 'destroy']);

    Route::resource('suppliers', SupplierController::class)
        ->only(['store', 'update', 'destroy']);

    Route::resource('customers', CustomerController::class)
        ->only(['store', 'update', 'destroy']);

    Route::resource('sale-sources', SaleSourceController::class)
        ->only(['store', 'update', 'destroy']);

    Route::resource('expenses', ExpenseController::class)
        ->only(['store', 'update', 'destroy']);

    Route::resource('expense-categories', ExpenseCategoryController::class)
        ->only(['store', 'update', 'destroy']);

    Route::resource('incomes', IncomeController::class)
        ->only(['store', 'update', 'destroy']);

    Route::resource('income-categories', IncomeCategoryController::class)
        ->only(['store', 'update', 'destroy']);

    Route::resource('profit-shares', ProfitShareController::class)
        ->only(['store', 'update', 'destroy']);
});
