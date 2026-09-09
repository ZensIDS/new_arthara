<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalesOrderRequest;
use App\Http\Requests\StoreSalesPaymentRequest;
use App\Http\Requests\UpdateSalesOrderRequest;
use App\Http\Requests\UpdateSalesPaymentRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SaleSource;
use App\Models\SalesOrder;
use App\Models\SalesPayment;
use App\Models\StockBatch;
use App\Services\SalesOrderService;
use Illuminate\Http\Request;

class SalesOrderController extends Controller
{
    public function __construct(protected SalesOrderService $service) {}

    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $search    = trim((string) $request->input('search', ''));

        $salesOrders = SalesOrder::query()
            ->with(['customer:id,name', 'source:id,name']) // hanya kolom yang dipakai di tabel
            ->when($startDate, fn($q) => $q->whereDate('so_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('so_date', '<=', $endDate))
            ->when($search !== '', function ($q) use ($search) {
                // Cari di semua kolom yang tampil di tabel: No. SO, Customer, Status,
                // serta Total/Sisa Piutang (dicocokkan sebagai teks angka).
                $q->where(function ($q) use ($search) {
                    $q->where('so_number', 'like', "%{$search}%")
                        ->orWhere('note', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('source', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                        ->orWhere('total_amount', 'like', "%{$search}%")
                        ->orWhereRaw('(total_amount - paid_amount) LIKE ?', ["%{$search}%"])
                        ->orWhere('payment_status', $this->mapStatusSearch($search));
                });
            })
            ->latest('so_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax()) {
            return view('sales-orders._table', compact('salesOrders'));
        }

        return view('sales-orders.index', compact('salesOrders', 'startDate', 'endDate', 'search'));
    }

    /**
     * Terjemahkan kata kunci pencarian ala label status ("lunas", "sebagian",
     * "belum bayar") ke nilai enum payment_status di DB, supaya user bisa cari
     * pakai label yang tampil di tabel, bukan cuma nilai mentahnya.
     */
    protected function mapStatusSearch(string $search): string
    {
        $search = strtolower($search);

        return match (true) {
            str_contains($search, 'lunas')  => 'paid',
            str_contains($search, 'sebagian') => 'partial',
            str_contains($search, 'belum')  => 'unpaid',
            default => $search,
        };
    }

    protected function productsForForm(): \Illuminate\Support\Collection
    {
        // Semua produk aktif dikirim (termasuk yang stoknya 0) supaya tetap terlihat
        // di dropdown; qty_on_hand dipakai di form (JS) untuk menonaktifkan opsi yang
        // stoknya habis dan validasi ringan sebelum submit. Batas final tetap ditegakkan
        // server-side lewat StockService::allocateFifo().
        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'qty_on_hand']);

        // Harga beli acuan tiap produk = harga beli batch TERTUA yang masih ada
        // sisa stok — itu batch yang bakal benar-benar kepakai duluan kalau produk
        // ini dijual (sesuai urutan FIFO). Dipakai di form (JS) buat alert kalau
        // harga jual ditulis di bawah harga beli, bukan buat disimpan ke SO.
        $nextBuyPrices = StockBatch::where('qty_remaining', '>', 0)
            ->orderBy('batch_date')
            ->orderBy('id')
            ->get(['product_id', 'buy_price'])
            ->unique('product_id')
            ->pluck('buy_price', 'product_id');

        $products->each(function (Product $product) use ($nextBuyPrices) {
            $product->next_buy_price = $nextBuyPrices->get($product->id);
        });

        return $products;
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $products = $this->productsForForm();
        $sources = SaleSource::orderBy('name')->get(['id', 'name']);

        return view('sales-orders.create', compact('customers', 'products', 'sources'));
    }

    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load(['customer', 'source', 'items.product', 'items.allocations.stockBatch', 'items.returnItems', 'payments', 'returns.items.product']);

        return view('sales-orders.show', compact('salesOrder'));
    }

    public function edit(SalesOrder $salesOrder)
    {
        // Edit tetap boleh diakses meskipun SO sudah ada pembayaran (partial/lunas).
        // Satu-satunya hal yang benar-benar memblokir adalah kalau ada item dari
        // SO ini yang sudah pernah diretur customer — itu baru ketahuan saat
        // submit (lihat SalesOrderService::guardCanModify), errornya ditangkap di update().
        $salesOrder->load('items');

        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $sources = SaleSource::orderBy('name')->get(['id', 'name']);
        // Saat edit, qty yang lagi dipakai transaksi ini sendiri harus dianggap
        // "tersedia lagi" di form (supaya user bisa submit ulang qty yang sama
        // tanpa kena validasi stok kurang) — tambahkan balik qty existing per produk.
        $products = $this->productsForForm();
        $existingQtyByProduct = $salesOrder->items->groupBy('product_id')->map->sum('qty');
        $products->each(function (Product $product) use ($existingQtyByProduct) {
            $product->qty_on_hand += (int) ($existingQtyByProduct->get($product->id) ?? 0);
        });

        return view('sales-orders.edit', compact('salesOrder', 'customers', 'products', 'sources'));
    }

    public function update(UpdateSalesOrderRequest $request, SalesOrder $salesOrder)
    {
        $validated = $request->validated();

        try {
            $so = $this->service->update(
                so: $salesOrder,
                data: [
                    'customer_id' => $validated['customer_id'],
                    'so_date'     => $validated['so_date'],
                    'note'        => $validated['note'] ?? null,
                    'source_id'   => $validated['source_id'],
                ],
                items: $validated['items'],
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('sales-orders.show', $so)
            ->with('success', "Transaksi {$so->so_number} berhasil diperbarui.");
    }

    public function destroy(SalesOrder $salesOrder)
    {
        // Hapus tetap boleh meskipun SO sudah ada pembayaran (partial/lunas) —
        // pembayaran & entry cashflow-nya ikut dibersihkan di service. Satu-
        // satunya hal yang memblokir adalah kalau itemnya sudah diretur customer.
        try {
            $soNumber = $salesOrder->so_number;
            $this->service->delete($salesOrder);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('sales-orders.index')
            ->with('success', "Transaksi {$soNumber} berhasil dihapus, stok terkait sudah dikembalikan.");
    }

    public function store(StoreSalesOrderRequest $request)
    {
        $validated = $request->validated();

        try {
            $so = $this->service->create(
                data: [
                    'customer_id' => $validated['customer_id'],
                    'so_date'     => $validated['so_date'],
                    'note'        => $validated['note'] ?? null,
                    'source_id'   => $validated['source_id'],
                ],
                items: $validated['items'],
                initialPayment: $validated['initial_payment'] ?? null,
                paymentMethod: $validated['payment_method'] ?? 'cash',
            );
        } catch (\RuntimeException $e) {
            // Termasuk error "stok tidak mencukupi" dari StockService::allocateFifo()
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('sales-orders.show', $so)
            ->with('success', "Transaksi {$so->so_number} berhasil dibuat.");
    }

    public function storePayment(StoreSalesPaymentRequest $request, SalesOrder $salesOrder)
    {
        $validated = $request->validated();

        try {
            $this->service->addPayment(
                so: $salesOrder,
                date: $validated['payment_date'],
                amount: $validated['amount'],
                method: $validated['method'],
                note: $validated['note'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Pembayaran berhasil dicatat.');
    }

    /**
     * Edit pembayaran yang sudah tercatat (koreksi nominal/tanggal/dll).
     * paid_amount, payment_status SO, dan ledger cash_flow ikut disinkronkan ulang.
     */
    public function updatePayment(UpdateSalesPaymentRequest $request, SalesOrder $salesOrder, SalesPayment $payment)
    {
        abort_unless($payment->sales_order_id === $salesOrder->id, 404);

        $validated = $request->validated();

        try {
            $this->service->updatePayment($payment, $validated);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Pembayaran berhasil diperbarui.');
    }
}
