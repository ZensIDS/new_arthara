@extends('layouts.app')

@section('title', 'Laporan Stok')
@section('page-title', 'Laporan Stok')

@section('content')
<div>

    {{-- KPI: agregasi SQL atas SELURUH data, independen dari pagination/pencarian --}}
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

    {{-- Nilai stok per kategori: diagregasi langsung via SQL GROUP BY (byCategory),
         bukan dihitung ulang di JS dari seluruh data produk. --}}
    <div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-ink/10">
            <h2 class="font-display font-semibold">Nilai Stok per Kategori</h2>
        </div>
        <div class="p-4">
            <div class="relative h-56">
                <canvas id="stockByCategoryChart"></canvas>
                <div id="stockByCategoryChartEmpty" class="hidden absolute inset-0 flex flex-col items-center justify-center text-center gap-2 text-ink/40">
                    <svg viewBox="0 0 24 24" class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18M7 15l4-4 3 3 5-6"/></svg>
                    <p class="text-xs">Belum ada data stok untuk ditampilkan</p>
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between mb-4 gap-3">
        {{-- Search: AJAX, tidak perlu tekan Enter --}}
        <div class="relative flex-1 max-w-sm">
            <svg viewBox="0 0 24 24" class="h-4 w-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/35" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m21 21-4.3-4.3"/></svg>
            <input
                id="stock-search"
                type="text"
                value="{{ $search }}"
                placeholder="Cari nama produk..."
                autocomplete="off"
                class="w-full rounded-xl border border-ink/12 pl-10 pr-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow"
            >
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ route('reports.export.stock') }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-600/20 bg-emerald-50 text-emerald-700 text-sm font-medium px-4 py-2 hover:bg-emerald-100 transition-colors whitespace-nowrap">
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                Export Excel
            </a>
            <a href="{{ route('stock.index') }}" class="text-sm font-medium text-ink/50 hover:text-ink whitespace-nowrap">
                Kelola stok &rarr;
            </a>
        </div>
    </div>

    <div id="stock-table-container">
        @include('reports._stock-table')
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.initAjaxListSearch({
            inputEl: document.getElementById('stock-search'),
            containerEl: document.getElementById('stock-table-container'),
            baseUrl: '{{ route('reports.stock') }}',
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        // Sumbernya sudah agregat dari server (GROUP BY kategori di SQL),
        // bukan dihitung ulang dari seluruh data produk di JS.
        const byCategory = @json($byCategory);
        const values = byCategory.map(c => c.value);

        if (!values.some(v => Number(v) > 0)) {
            document.getElementById('stockByCategoryChart').classList.add('hidden');
            document.getElementById('stockByCategoryChartEmpty').classList.remove('hidden');
            return;
        }

        new Chart(document.getElementById('stockByCategoryChart'), {
            type: 'bar',
            data: {
                labels: byCategory.map(c => c.name),
                datasets: [{
                    label: 'Nilai Stok',
                    data: values,
                    backgroundColor: '#f59e0b',
                    borderRadius: 6,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { ticks: { callback: (v) => 'Rp ' + (v / 1000) + 'rb' } } },
            },
        });
    });
</script>
@endpush
