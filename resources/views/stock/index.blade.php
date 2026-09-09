@extends('layouts.app')

@section('page-title', 'Stok')

@section('content')
<div>

    {{-- KPI: dihitung via agregasi SQL atas SELURUH data (tidak terpengaruh pagination/pencarian) --}}
    <div class="grid grid-cols-1 @4xl:grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl border border-ink/10 bg-white shadow-card p-6">
            <p class="text-xs font-medium text-ink/50 mb-2 uppercase tracking-wide">Produk Terdaftar</p>
            <p class="font-display font-semibold text-2xl tnum">{{ number_format($kpis['product_count'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-ink/10 bg-white shadow-card p-6">
            <p class="text-xs font-medium text-ink/50 mb-2 uppercase tracking-wide">Total Unit Stok</p>
            <p class="font-display font-semibold text-2xl tnum">{{ number_format($kpis['total_qty'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl bg-gradient-to-br from-ink to-[#0a0a0b] text-white shadow-panel p-6">
            <p class="text-xs font-medium text-white/50 mb-2 uppercase tracking-wide">Total Nilai Stok</p>
            <p class="font-display font-semibold text-2xl tnum text-amber-400">Rp {{ number_format($kpis['total_value'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Search: dikirim ke server (WHERE ... LIKE), bukan filter JS atas seluruh data --}}
    <div class="flex items-center justify-between mb-4 gap-3">
        <form method="GET" action="{{ route('stock.index') }}" class="relative flex-1 max-w-sm">
            <svg viewBox="0 0 24 24" class="h-4 w-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/35" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m21 21-4.3-4.3"/></svg>
            <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama produk..." onchange="this.form.submit()"
                   class="w-full rounded-xl border border-ink/12 pl-10 pr-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
        </form>
        <a href="{{ route('reports.stock') }}" class="text-sm font-medium text-ink/50 hover:text-ink whitespace-nowrap">
            Versi laporan &rarr;
        </a>
    </div>

    {{-- Daftar produk (klik untuk expand breakdown batch). Hanya produk pada
         halaman aktif yang dikirim ke browser, sudah beserta batch-nya. --}}
    <div class="rounded-2xl border border-ink/10 bg-white shadow-card divide-y divide-ink/[0.06] overflow-hidden">
        @forelse ($stock as $p)
            <div x-data="{ open: false }">
                <button
                    type="button"
                    @click="open = !open"
                    class="w-full flex items-center justify-between gap-4 px-5 py-4 hover:bg-amber-50/40 transition-colors text-left"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-ink/40 transition-transform"
                             :class="open ? 'rotate-90' : ''"
                             fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 6l6 6-6 6"/>
                        </svg>
                        <div class="min-w-0">
                            <p class="font-medium truncate">{{ $p['name'] }}</p>
                            <p class="text-xs text-ink/40">{{ $p['category'] ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 sm:gap-6 shrink-0">
                        <span class="inline-flex items-center justify-center min-w-[2.5rem] rounded-full px-2.5 py-1 text-xs font-semibold tnum
                            {{ $p['total_qty'] == 0 ? 'bg-red-100 text-red-700' : ($p['total_qty'] <= 5 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700') }}">
                            {{ $p['total_qty'] }}
                        </span>
                        <span class="text-sm font-medium tnum w-28 sm:w-32 text-right">Rp {{ number_format($p['stock_value'], 0, ',', '.') }}</span>
                    </div>
                </button>

                <div x-show="open" x-transition class="px-5 pb-4 pl-12">
                    @if (count($p['batches']) === 0)
                        <p class="text-xs text-ink/40 py-2">Tidak ada batch stok tersisa untuk produk ini.</p>
                    @else
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-ink/40 text-left">
                                    <th class="py-1.5 font-medium">Tgl Masuk</th>
                                    <th class="py-1.5 font-medium text-right">Harga Beli</th>
                                    <th class="py-1.5 font-medium text-right">Qty Masuk</th>
                                    <th class="py-1.5 font-medium text-right">Sisa</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ink/[0.05]">
                                @foreach ($p['batches'] as $b)
                                    <tr>
                                        <td class="py-1.5 tnum">{{ \Illuminate\Support\Carbon::parse($b['batch_date'])->format('d M Y') }}</td>
                                        <td class="py-1.5 tnum text-right">Rp {{ number_format($b['buy_price'], 0, ',', '.') }}</td>
                                        <td class="py-1.5 tnum text-right">{{ $b['qty_in'] }}</td>
                                        <td class="py-1.5 tnum text-right font-medium">{{ $b['qty_remaining'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        @empty
            <p class="px-5 py-10 text-center text-ink/40 text-sm">
                {{ $search ? 'Tidak ada produk yang cocok dengan pencarian.' : 'Belum ada produk.' }}
            </p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $stock->links() }}
    </div>
</div>
@endsection
