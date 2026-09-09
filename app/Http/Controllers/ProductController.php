<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $search    = trim((string) $request->input('search', ''));

        $products = Product::query()
            ->with('category:id,name') // hanya kolom yang dipakai di tabel
            ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
            ->when($search !== '', function ($q) use ($search) {
                // Cari di semua kolom yang tampil di tabel: Nama, Kategori, Satuan, Stok, Status.
                $activeMatch = $this->mapActiveSearch($search);

                $q->where(function ($q) use ($search, $activeMatch) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('unit', 'like', "%{$search}%")
                        ->orWhere('qty_on_hand', 'like', "%{$search}%")
                        ->orWhereHas('category', fn($sq) => $sq->where('name', 'like', "%{$search}%"));

                    if (! is_null($activeMatch)) {
                        $q->orWhere('is_active', $activeMatch);
                    }
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $categories = Category::orderBy('name')->get(['id', 'name']);

        if ($request->ajax()) {
            return view('products._table', compact('products'));
        }

        return view('products.index', compact('products', 'categories', 'startDate', 'endDate', 'search'));
    }

    /**
     * Terjemahkan kata kunci pencarian ala label status ("aktif", "nonaktif")
     * ke nilai boolean is_active. Balikin null kalau kata kuncinya tidak
     * menyinggung status sama sekali, supaya tidak salah nge-filter data
     * yang sebenarnya cuma dicari lewat kolom lain (nama/satuan/dst).
     */
    protected function mapActiveSearch(string $search): ?bool
    {
        $search = strtolower($search);

        return match (true) {
            str_contains($search, 'nonaktif') => false,
            str_contains($search, 'aktif')    => true,
            default => null,
        };
    }

    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());

        return response()->json([
            'message' => 'Produk berhasil ditambahkan.',
            'data'    => $product->load('category'),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return response()->json([
            'message' => 'Produk berhasil diperbarui.',
            'data'    => $product->load('category'),
        ]);
    }

    public function destroy(Product $product)
    {
        if ($product->qty_on_hand > 0 || $product->purchaseOrderItems()->exists() || $product->saleItems()->exists()) {
            return response()->json([
                'message' => 'Produk tidak bisa dihapus karena sudah punya riwayat transaksi/stok.',
            ], 422);
        }

        $product->delete();

        return response()->json([
            'message' => 'Produk berhasil dihapus.',
        ]);
    }
}
