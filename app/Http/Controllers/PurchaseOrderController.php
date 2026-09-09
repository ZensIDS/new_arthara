<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\StorePurchasePaymentRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchasePaymentRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct(protected PurchaseOrderService $service) {}

    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $search    = trim((string) $request->input('search', ''));

        $purchaseOrders = PurchaseOrder::query()
            ->with('supplier:id,name') // hanya kolom yang dipakai di tabel, bukan seluruh model supplier
            ->when($startDate, fn($q) => $q->whereDate('po_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('po_date', '<=', $endDate))
            ->when($search !== '', function ($q) use ($search) {
                // Cari di semua kolom yang tampil di tabel: No. PO, Supplier, Status,
                // serta Total/Sisa Hutang (dicocokkan sebagai teks angka).
                $q->where(function ($q) use ($search) {
                    $q->where('po_number', 'like', "%{$search}%")
                        ->orWhere('note', 'like', "%{$search}%")
                        ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                        ->orWhere('total_amount', 'like', "%{$search}%")
                        ->orWhereRaw('(total_amount - paid_amount) LIKE ?', ["%{$search}%"])
                        ->orWhere('payment_status', $this->mapStatusSearch($search));
                });
            })
            ->latest('po_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax()) {
            return view('purchase-orders._table', compact('purchaseOrders'));
        }

        return view('purchase-orders.index', compact('purchaseOrders', 'startDate', 'endDate', 'search'));
        // Kalau API: return response()->json($purchaseOrders);
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

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);

        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'qty_on_hand']);

        return view('purchase-orders.create', compact('suppliers', 'products'));
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'items.product', 'items.stockBatch', 'items.returnItems', 'payments', 'returns.items.product']);

        return view('purchase-orders.show', compact('purchaseOrder'));
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        // Edit tetap boleh diakses meskipun PO sudah ada pembayaran (partial/lunas).
        // Satu-satunya hal yang benar-benar memblokir adalah kalau barang dari PO
        // ini sudah terlanjur terjual — itu baru ketahuan saat submit (lihat
        // PurchaseOrderService::guardCanModify), dan errornya ditangkap di update().
        $purchaseOrder->load('items');

        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);

        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'qty_on_hand']);

        return view('purchase-orders.edit', compact('purchaseOrder', 'suppliers', 'products'));
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validated();

        try {
            $po = $this->service->update(
                po: $purchaseOrder,
                data: [
                    'supplier_id' => $validated['supplier_id'],
                    'po_date'     => $validated['po_date'],
                    'note'        => $validated['note'] ?? null,
                ],
                items: $validated['items'],
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('purchase-orders.show', $po)
            ->with('success', "PO {$po->po_number} berhasil diperbarui.");
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        // Hapus tetap boleh meskipun PO sudah ada pembayaran (partial/lunas) —
        // pembayaran & entry cashflow-nya ikut dibersihkan di service. Satu-
        // satunya hal yang memblokir adalah kalau barangnya sudah terjual.
        try {
            $poNumber = $purchaseOrder->po_number;
            $this->service->delete($purchaseOrder);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('purchase-orders.index')
            ->with('success', "PO {$poNumber} berhasil dihapus, stok terkait sudah dikembalikan.");
    }

    public function store(StorePurchaseOrderRequest $request)
    {
        $validated = $request->validated();

        try {
            $po = $this->service->create(
                data: [
                    'supplier_id' => $validated['supplier_id'],
                    'po_date'     => $validated['po_date'],
                    'note'        => $validated['note'] ?? null,
                ],
                items: $validated['items'],
                initialPayment: $validated['initial_payment'] ?? null,
                paymentMethod: $validated['payment_method'] ?? 'cash',
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
            // Kalau API: return response()->json(['message' => $e->getMessage()], 422);
        }

        return redirect()
            ->route('purchase-orders.show', $po)
            ->with('success', "PO {$po->po_number} berhasil dibuat.");
    }

    public function storePayment(StorePurchasePaymentRequest $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validated();

        try {
            $this->service->addPayment(
                po: $purchaseOrder,
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
     * paid_amount, payment_status PO, dan ledger cash_flow ikut disinkronkan ulang.
     */
    public function updatePayment(UpdatePurchasePaymentRequest $request, PurchaseOrder $purchaseOrder, PurchasePayment $payment)
    {
        abort_unless($payment->purchase_order_id === $purchaseOrder->id, 404);

        $validated = $request->validated();

        try {
            $this->service->updatePayment($payment, $validated);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Pembayaran berhasil diperbarui.');
    }
}
