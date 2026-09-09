@php
    $nav = [
        'Ringkasan' => [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'pattern' => 'dashboard'],
        ],
        'Pembelian' => [
            ['label' => 'Purchase Order', 'route' => 'purchase-orders.index', 'pattern' => 'purchase-orders.*'],
            ['label' => 'Supplier', 'route' => 'suppliers.index', 'pattern' => 'suppliers.*'],
        ],
        'Persediaan' => [
            ['label' => 'Stok', 'route' => 'stock.index', 'pattern' => 'stock.*'],
            ['label' => 'Produk', 'route' => 'products.index', 'pattern' => 'products.*'],
            ['label' => 'Kategori', 'route' => 'categories.index', 'pattern' => 'categories.*'],
        ],
        'Penjualan' => [
            ['label' => 'Sales Order', 'route' => 'sales-orders.index', 'pattern' => 'sales-orders.*'],
            ['label' => 'Asal Penjualan', 'route' => 'sale-sources.index', 'pattern' => 'sale-sources.*'],
        ],
        'Operasional' => [
            ['label' => 'Kategori Pemasukan', 'route' => 'income-categories.index', 'pattern' => 'income-categories.*'],
            ['label' => 'Pemasukan Lain', 'route' => 'incomes.index', 'pattern' => 'incomes.*'],
            ['label' => 'Kategori Pengeluaran', 'route' => 'expense-categories.index', 'pattern' => 'expense-categories.*'],
            ['label' => 'Pengeluaran', 'route' => 'expenses.index', 'pattern' => 'expenses.*'],
            ['label' => 'Bagi Hasil', 'route' => 'profit-shares.index', 'pattern' => 'profit-shares.*'],
        ],
        'Laporan' => [
            ['label' => 'Stok', 'route' => 'reports.stock', 'pattern' => 'reports.stock'],
            ['label' => 'Laba Rugi', 'route' => 'reports.profit-loss', 'pattern' => 'reports.profit-loss'],
            ['label' => 'Arus Kas', 'route' => 'reports.cash-flow', 'pattern' => 'reports.cash-flow'],
            ['label' => 'Hutang (AP)', 'route' => 'reports.payable', 'pattern' => 'reports.payable'],
            ['label' => 'Piutang (AR)', 'route' => 'reports.receivable', 'pattern' => 'reports.receivable'],
            ['label' => 'Retur Penjualan (SO)', 'route' => 'reports.sales-return', 'pattern' => 'reports.sales-return'],
            ['label' => 'Retur Pembelian (PO)', 'route' => 'reports.purchase-return', 'pattern' => 'reports.purchase-return'],
            ['label' => 'Pengeluaran', 'route' => 'reports.expenses', 'pattern' => 'reports.expenses'],
            ['label' => 'Pemasukan Lain', 'route' => 'reports.incomes', 'pattern' => 'reports.incomes'],
        ],
    ];
@endphp

{{-- Wrapper: mengatur lebar (0 atau 16rem) supaya bisa collapse penuh di
     semua ukuran layar — mobile (overlay slide), tablet & desktop (dorong konten). --}}
<aside
    :class="sidebarOpen ? 'translate-x-0 lg:w-64' : '-translate-x-full lg:translate-x-0 lg:w-0'"
    class="fixed inset-y-0 left-0 z-40 w-64 bg-ink text-white transition-all duration-200 ease-out lg:static lg:shrink-0 overflow-hidden"
>
    {{-- Konten dalam lebar tetap 16rem supaya tidak "remuk"/wrap saat wrapper mengecil ke 0 --}}
    <div class="w-64 h-full flex flex-col">

        {{-- Brand --}}
        <div class="h-16 flex items-center gap-2.5 px-6 border-b border-white/10 shrink-0">
            <span class="h-7 w-7 rounded-lg bg-amber-400 flex items-center justify-center shrink-0">
                <span class="font-display font-bold text-ink text-sm">B</span>
            </span>
            <a href="{{ route('dashboard') }}" class="font-display font-semibold text-lg tracking-tight">
                BerlianZ<span class="text-white/40">Store</span>
            </a>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-6 space-y-6">
            @foreach ($nav as $group => $items)
                <div>
                    <p class="px-3 text-[11px] font-medium text-white/35 mb-2">{{ $group }}</p>
                    <div class="space-y-0.5">
                        @foreach ($items as $item)
                            @php $active = request()->routeIs($item['pattern']); @endphp
                            <a
                                href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                                class="block px-3 py-2 text-sm transition-colors
                                    {{ $active
                                        ? 'bg-white text-ink font-medium'
                                        : 'text-white/70 hover:text-white hover:bg-white/[0.06]' }}"
                            >
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        {{-- User info + logout --}}
        <div class="border-t border-white/10 p-4 shrink-0">
            <div class="flex items-center justify-between gap-3 px-2">
                <div class="min-w-0">
                    <p class="text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-white/40 capitalize">{{ auth()->user()->role }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="text-xs text-white/50 hover:text-white border border-white/15 hover:border-white/30 px-2.5 py-1.5 transition-colors"
                    >
                        Keluar
                    </button>
                </form>
            </div>
        </div>

    </div>
</aside>
