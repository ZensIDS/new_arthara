<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SaleSource;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Services\ExpenseService;
use App\Services\PurchaseOrderService;
use App\Services\PurchaseReturnService;
use App\Services\SalesOrderService;
use App\Services\SalesReturnService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Generate satu set data dummy yang lengkap dan saling terhubung untuk
 * SEMUA fitur aplikasi, lewat service layer yang sama persis dengan yang
 * dipakai controller — supaya stok, kas, piutang/hutang, dan laporan
 * semuanya konsisten seperti dipakai beneran, siap buat demo/presentasi.
 *
 * Cakupan:
 * - Master data (kategori, produk, supplier, customer, asal penjualan, kategori biaya)
 * - Purchase Order: lunas, cicilan (dengan 2x termin), belum bayar
 * - Sales Order: lunas, cicilan, belum bayar, dari berbagai asal penjualan
 * - Retur pembelian (ke supplier) & retur penjualan (dari customer)
 * - Biaya operasional dari beberapa kategori
 * - Semua tanggal disebar mundur ~60 hari dari hari ini, supaya laporan
 *   (harian/bulanan, grafik, dsb) juga ada isinya, bukan cuma "hari ini".
 */
class GenerateDemoData extends Command
{
    protected $signature = 'demo:generate
        {--fresh : Jalankan migrate:fresh dulu sebelum generate (database lama akan hilang total)}';

    protected $description = 'Generate data dummy lengkap untuk semua fitur (PO, SO, retur, biaya, dll) — siap buat demo/presentasi';

    protected PurchaseOrderService $poService;
    protected SalesOrderService $soService;
    protected PurchaseReturnService $poReturnService;
    protected SalesReturnService $soReturnService;
    protected ExpenseService $expenseService;

    public function __construct(
        PurchaseOrderService $poService,
        SalesOrderService $soService,
        PurchaseReturnService $poReturnService,
        SalesReturnService $soReturnService,
        ExpenseService $expenseService,
    ) {
        parent::__construct();

        $this->poService = $poService;
        $this->soService = $soService;
        $this->poReturnService = $poReturnService;
        $this->soReturnService = $soReturnService;
        $this->expenseService = $expenseService;
    }

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->warn('Menjalankan migrate:fresh — semua data lama akan dihapus total.');
            Artisan::call('migrate:fresh', [], $this->output);
        } elseif (PurchaseOrder::count() > 0 || SalesOrder::count() > 0) {
            $this->error(
                'Sudah ada data transaksi di database. Jalankan ulang dengan --fresh kalau ' .
                    'mau reset total ke kondisi bersih sebelum generate data demo. ' .
                    'Contoh: php artisan demo:generate --fresh'
            );

            return self::FAILURE;
        }

        $this->info('1/6 Menyiapkan master data...');
        $this->seedMasterData();

        $this->info('2/6 Membuat Purchase Order (lunas, cicilan, belum bayar)...');
        $this->generatePurchaseOrders();

        $this->info('3/6 Membuat Sales Order (lunas, cicilan, belum bayar)...');
        $this->generateSalesOrders();

        $this->info('4/6 Membuat retur pembelian & retur penjualan...');
        $this->generateReturns();

        $this->info('5/6 Membuat biaya operasional...');
        $this->generateExpenses();

        $this->info('6/6 Memastikan hasil akhir untung (bukan rugi)...');
        $this->ensureOverallProfit();

        $this->newLine();
        $this->info('Ringkasan data yang dibuat:');
        $this->printSummary();

        $this->newLine();
        $this->info('Ringkasan Laba Rugi (seluruh periode data demo):');
        $this->printProfitLoss();

        $this->newLine();
        $this->info('Login: superadmin / password  (atau viewer / password untuk role read-only)');

        return self::SUCCESS;
    }

    protected function seedMasterData(): void
    {
        if (Category::count() === 0) {
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CategorySeeder', '--force' => true]);
        }
        if (Product::count() === 0) {
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\ProductSeeder', '--force' => true]);
        }
        if (Supplier::count() === 0) {
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\SupplierSeeder', '--force' => true]);
        }
        if (Customer::count() === 0) {
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CustomerSeeder', '--force' => true]);
        }
        if (SaleSource::count() === 0) {
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\SaleSourceSeeder', '--force' => true]);
        }
        if (DB::table('users')->count() === 0) {
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\UserSeeder', '--force' => true]);
        }

        foreach (['Sewa Toko', 'Listrik & Air', 'Gaji Karyawan', 'Transportasi', 'ATK & Kemasan', 'Lain-lain'] as $name) {
            ExpenseCategory::firstOrCreate(['name' => $name]);
        }
    }

    /**
     * Bikin beberapa PO tersebar mundur 55 hari, dengan buy_price acak wajar
     * per produk (disimpan di memori supaya konsisten dipakai lagi waktu SO,
     * biar harga jual bisa dibandingkan ke harga beli / HPP).
     */
    protected array $buyPrices = [];

    protected function generatePurchaseOrders(): void
    {
        $suppliers = Supplier::all();
        $productsByCategory = Product::with('category')->get()->groupBy(fn($p) => $p->category_id);

        // Tentukan harga beli acuan per produk sekali di awal (Rp 3.000 - Rp 45.000).
        foreach (Product::all() as $product) {
            $this->buyPrices[$product->id] = random_int(3, 45) * 1000;
        }

        $scenarios = [
            // [hari mundur, status pembayaran]
            [55, 'paid'],
            [50, 'paid'],
            [46, 'partial'],
            [42, 'paid'],
            [37, 'unpaid'],
            [33, 'paid'],
            [28, 'partial'],
            [24, 'paid'],
            [19, 'paid'],
            [15, 'unpaid'],
            [10, 'partial'],
            [6, 'paid'],
            [3, 'paid'],
            [1, 'unpaid'],
        ];

        foreach ($scenarios as $i => [$daysAgo, $status]) {
            $supplier = $suppliers[$i % $suppliers->count()];
            $date = now()->subDays($daysAgo)->toDateString();

            $items = Product::inRandomOrder()->take(random_int(3, 5))->get()->map(fn($p) => [
                'product_id' => $p->id,
                'qty'        => random_int(30, 90),
                'buy_price'  => $this->buyPrices[$p->id],
            ])->values()->all();

            $data = [
                'supplier_id' => $supplier->id,
                'po_date'     => $date,
                'note'        => 'Restock rutin dari ' . $supplier->name,
            ];

            $totalAmount = collect($items)->sum(fn($it) => $it['qty'] * $it['buy_price']);

            $initialPayment = match ($status) {
                'paid'    => $totalAmount,
                'partial' => round($totalAmount * 0.5, -3),
                'unpaid'  => null,
            };

            $po = $this->poService->create($data, $items, $initialPayment ? (float) $initialPayment : null);

            // PO cicilan dapat 1 termin tambahan beberapa hari kemudian, biar
            // kelihatan riwayat pembayaran bertahap di halaman detail PO.
            if ($status === 'partial') {
                $remaining = (float) $po->total_amount - (float) $po->paid_amount;
                $secondPayment = round($remaining * 0.4, -3);

                if ($secondPayment > 0) {
                    $this->poService->addPayment(
                        $po,
                        now()->subDays(max($daysAgo - 5, 0))->toDateString(),
                        $secondPayment,
                        'transfer',
                        'Pelunasan termin ke-2'
                    );
                }
            }
        }
    }

    protected function generateSalesOrders(): void
    {
        $customers = Customer::all();
        $sources = SaleSource::all();

        $scenarios = [
            [55, 'paid'],
            [52, 'paid'],
            [49, 'partial'],
            [47, 'paid'],
            [44, 'paid'],
            [42, 'unpaid'],
            [39, 'paid'],
            [37, 'paid'],
            [34, 'partial'],
            [32, 'paid'],
            [29, 'paid'],
            [27, 'unpaid'],
            [24, 'paid'],
            [22, 'paid'],
            [20, 'partial'],
            [18, 'paid'],
            [15, 'paid'],
            [13, 'unpaid'],
            [11, 'paid'],
            [9, 'paid'],
            [7, 'partial'],
            [6, 'paid'],
            [4, 'paid'],
            [3, 'paid'],
            [2, 'unpaid'],
            [1, 'paid'],
            [0, 'paid'],
            [0, 'partial'],
        ];

        foreach ($scenarios as $i => [$daysAgo, $status]) {
            $customer = $customers[$i % $customers->count()];
            $source = $sources[$i % $sources->count()];
            $date = now()->subDays($daysAgo)->toDateString();

            // Pilih 3-5 produk yang masih ada stoknya saat ini — volume jual
            // dibuat cukup besar (relatif ke stok beli) supaya laba kotor
            // realistis menutup beban operasional dan usaha ini untung.
            $available = Product::where('qty_on_hand', '>', 0)->inRandomOrder()->take(random_int(3, 5))->get();

            if ($available->isEmpty()) {
                continue; // stok habis semua, skip skenario ini
            }

            $items = [];
            foreach ($available as $product) {
                $maxQty = min($product->qty_on_hand, random_int(10, 30));
                if ($maxQty < 1) {
                    continue;
                }

                $buyPrice = $this->buyPrices[$product->id] ?? 10000;
                // Harga jual wajar toko retail: markup 35%-70% dari harga beli acuan.
                $sellPrice = (int) round($buyPrice * (1 + random_int(35, 70) / 100), -2);

                $items[] = [
                    'product_id' => $product->id,
                    'qty'        => $maxQty,
                    'sell_price' => $sellPrice,
                ];
            }

            if (empty($items)) {
                continue;
            }

            $data = [
                'customer_id' => $customer->id,
                'so_date'     => $date,
                'note'        => null,
                'source_id'   => $source->id,
            ];

            $totalAmount = collect($items)->sum(fn($it) => $it['qty'] * $it['sell_price']);

            $initialPayment = match ($status) {
                'paid'    => $totalAmount,
                'partial' => round($totalAmount * 0.5, -3),
                'unpaid'  => null,
            };

            $so = $this->soService->create($data, $items, $initialPayment ? (float) $initialPayment : null);

            if ($status === 'partial') {
                $remaining = (float) $so->total_amount - (float) $so->paid_amount;
                $secondPayment = round($remaining * 0.5, -3);

                if ($secondPayment > 0) {
                    $this->soService->addPayment(
                        $so,
                        now()->subDays(max($daysAgo - 3, 0))->toDateString(),
                        $secondPayment,
                        'cash',
                        'Pelunasan termin ke-2'
                    );
                }
            }
        }
    }

    protected function generateReturns(): void
    {
        // Retur pembelian: ambil 1 PO yang masih punya item dengan stok utuh
        // (belum kejual sama sekali) supaya retur pasti valid.
        $poForReturn = PurchaseOrder::with('items')
            ->orderByDesc('po_date')
            ->get()
            ->first(function (PurchaseOrder $po) {
                return $po->items->contains(fn($item) => $item->product->qty_on_hand >= $item->qty);
            });

        if ($poForReturn) {
            $item = $poForReturn->items->first(fn($item) => $item->product->qty_on_hand >= $item->qty);

            if ($item) {
                $returnQty = max(1, (int) floor($item->qty * 0.2));

                try {
                    $this->poReturnService->create(
                        $poForReturn,
                        [
                            'return_date' => now()->subDays(2)->toDateString(),
                            'note'        => 'Sebagian barang rusak/cacat produksi, dikembalikan ke supplier',
                        ],
                        [['purchase_order_item_id' => $item->id, 'qty' => $returnQty]]
                    );
                } catch (\RuntimeException $e) {
                    $this->warn('Lewati retur pembelian contoh: ' . $e->getMessage());
                }
            }
        }

        // Retur penjualan: ambil 1 SO terbaru beserta itemnya.
        $soForReturn = SalesOrder::with('items')->orderByDesc('so_date')->first();

        if ($soForReturn && $soForReturn->items->isNotEmpty()) {
            $item = $soForReturn->items->first();
            $returnQty = max(1, (int) floor($item->qty * 0.3));

            try {
                $this->soReturnService->create(
                    $soForReturn,
                    [
                        'return_date' => now()->subDay()->toDateString(),
                        'note'        => 'Customer komplain barang tidak sesuai, dikembalikan sebagian',
                    ],
                    [['sale_item_id' => $item->id, 'qty' => $returnQty]]
                );
            } catch (\RuntimeException $e) {
                $this->warn('Lewati retur penjualan contoh: ' . $e->getMessage());
            }
        }
    }

    protected function generateExpenses(): void
    {
        $categories = ExpenseCategory::all()->keyBy('name');

        $expenses = [
            [50, 'Sewa Toko', 1_500_000, 'Sewa toko bulan lalu'],
            [45, 'Listrik & Air', 350_000, 'Tagihan listrik & air bulan lalu'],
            [40, 'Gaji Karyawan', 1_800_000, 'Gaji karyawan periode 1'],
            [30, 'Transportasi', 120_000, 'BBM & antar barang'],
            [25, 'ATK & Kemasan', 150_000, 'Plastik kemasan & nota'],
            [20, 'Sewa Toko', 1_500_000, 'Sewa toko bulan ini'],
            [18, 'Listrik & Air', 380_000, 'Tagihan listrik & air bulan ini'],
            [12, 'Gaji Karyawan', 1_800_000, 'Gaji karyawan periode 2'],
            [8, 'Lain-lain', 200_000, 'Perbaikan etalase'],
            [3, 'Transportasi', 100_000, 'BBM antar barang ke customer'],
        ];

        foreach ($expenses as [$daysAgo, $categoryName, $amount, $desc]) {
            $category = $categories->get($categoryName);
            if (! $category) {
                continue;
            }

            $this->expenseService->create([
                'expense_category_id' => $category->id,
                'expense_date'        => now()->subDays($daysAgo)->toDateString(),
                'amount'              => $amount,
                'description'         => $desc,
            ]);
        }
    }

    /**
     * Jaring pengaman: karena harga & qty di atas pakai angka acak, hitung
     * ulang Laba Rugi riil (persis rumus yang dipakai ReportService::profitLossReport,
     * yaitu Pendapatan - HPP - Biaya Operasional) setelah semua transaksi dibuat.
     * Kalau ternyata masih rugi/pas-pasan, tambahkan 1 SO ekstra hari ini
     * (lunas, margin tinggi) sebesar kekurangannya + buffer, supaya HASIL AKHIR
     * dijamin untung terlepas dari hasil acak di atas.
     */
    protected function ensureOverallProfit(): void
    {
        $revenue = (float) SalesOrder::sum('total_amount');
        $hpp = (float) SalesOrder::sum('total_hpp');
        $expense = (float) Expense::sum('amount');
        $netProfit = $revenue - $hpp - $expense;

        $targetBuffer = 1_500_000; // biar untungnya terasa jelas, bukan cuma tipis di atas 0
        $shortfall = $netProfit >= $targetBuffer ? 0 : ($targetBuffer - $netProfit);

        if ($shortfall <= 0) {
            $this->line('   Sudah untung tanpa perlu tambahan — dilewati.');

            return;
        }

        $customer = Customer::first();
        $source = SaleSource::first();

        if (! $customer || ! $source) {
            $this->warn('   Tidak ada customer/asal penjualan untuk transaksi penambal — dilewati.');

            return;
        }

        // Ambil produk dengan stok tersisa terbanyak, jual dengan markup besar
        // (80%) sampai target laba tambahan tercapai.
        $products = Product::where('qty_on_hand', '>', 0)->orderByDesc('qty_on_hand')->get();

        $items = [];
        $profitCovered = 0;

        foreach ($products as $product) {
            if ($profitCovered >= $shortfall) {
                break;
            }

            $buyPrice = $this->buyPrices[$product->id] ?? 15000;
            $sellPrice = (int) round($buyPrice * 1.8, -2); // markup 80%
            $profitPerUnit = $sellPrice - $buyPrice;

            if ($profitPerUnit <= 0) {
                continue;
            }

            $needed = (int) ceil(($shortfall - $profitCovered) / $profitPerUnit);
            $qty = min($product->qty_on_hand, max($needed, 1));

            if ($qty < 1) {
                continue;
            }

            $items[] = [
                'product_id' => $product->id,
                'qty'        => $qty,
                'sell_price' => $sellPrice,
            ];

            $profitCovered += $qty * $profitPerUnit;
        }

        if (empty($items)) {
            $this->warn('   Stok tersisa tidak cukup untuk transaksi penambal — dilewati.');

            return;
        }

        $data = [
            'customer_id' => $customer->id,
            'so_date'     => now()->toDateString(),
            'note'        => 'Penjualan borongan',
            'source_id'   => $source->id,
        ];

        $totalAmount = collect($items)->sum(fn($it) => $it['qty'] * $it['sell_price']);

        $this->soService->create($data, $items, (float) $totalAmount);

        $this->line('   Ditambahkan 1 transaksi penambal supaya rekap akhir positif untung.');
    }

    protected function printProfitLoss(): void
    {
        $revenue = (float) SalesOrder::sum('total_amount');
        $hpp = (float) SalesOrder::sum('total_hpp');
        $grossProfit = $revenue - $hpp;
        $expense = (float) Expense::sum('amount');
        $netProfit = $grossProfit - $expense;

        $format = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');

        $this->table(
            ['Komponen', 'Nilai'],
            [
                ['Total Pendapatan (semua SO)', $format($revenue)],
                ['Total HPP (FIFO)', $format($hpp)],
                ['Laba Kotor', $format($grossProfit)],
                ['Total Biaya Operasional', $format($expense)],
                ['LABA BERSIH', $format($netProfit)],
            ]
        );

        if ($netProfit > 0) {
            $this->info('=> Rekap akhir UNTUNG, siap dipresentasikan.');
        } else {
            $this->error('=> Rekap akhir masih rugi — coba jalankan ulang dengan --fresh.');
        }
    }

    protected function printSummary(): void
    {
        $this->table(
            ['Data', 'Jumlah'],
            [
                ['Kategori produk', Category::count()],
                ['Produk', Product::count()],
                ['Supplier', Supplier::count()],
                ['Customer', Customer::count()],
                ['Asal penjualan', SaleSource::count()],
                ['Kategori biaya', ExpenseCategory::count()],
                ['Purchase Order', PurchaseOrder::count()],
                ['Sales Order', SalesOrder::count()],
                ['Retur pembelian', DB::table('purchase_returns')->count()],
                ['Retur penjualan', DB::table('sales_returns')->count()],
                ['Biaya operasional', Expense::count()],
                ['Entri arus kas', DB::table('cash_flows')->count()],
            ]
        );
    }
}
