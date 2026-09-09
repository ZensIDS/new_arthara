@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

    {{-- Filter rentang tanggal --}}
    <div class="mb-6 rounded-2xl border border-ink/10 bg-white shadow-card p-4 sm:p-5">
        <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-ink/50 mb-1 uppercase tracking-wide">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                       class="rounded-lg border border-ink/15 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400/60">
            </div>
            <div>
                <label class="block text-xs font-medium text-ink/50 mb-1 uppercase tracking-wide">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                       class="rounded-lg border border-ink/15 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400/60">
            </div>
            <button type="submit"
                    class="rounded-lg bg-ink text-white text-sm font-medium px-4 py-2 hover:bg-ink/90 transition-colors">
                Terapkan
            </button>

            <div class="flex items-center gap-1.5 ml-auto flex-wrap">
                @php
                    $presets = [
                        'Hari Ini'      => [now()->toDateString(), now()->toDateString()],
                        'Minggu Ini'    => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
                        'Bulan Ini'     => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
                        'Bulan Lalu'    => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
                        '30 Hari Terakhir' => [now()->subDays(29)->toDateString(), now()->toDateString()],
                    ];
                @endphp
                @foreach ($presets as $label => $range)
                    <a href="{{ route('dashboard', ['start_date' => $range[0], 'end_date' => $range[1]]) }}"
                       class="text-xs font-medium px-3 py-1.5 rounded-full border transition-colors
                              {{ $startDate === $range[0] && $endDate === $range[1] ? 'bg-amber-400/90 border-amber-400/90 text-ink' : 'border-ink/15 text-ink/60 hover:border-ink/30' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </form>
        <p class="text-xs text-ink/40 mt-3">
            Menampilkan data periode
            <span class="font-medium text-ink/60">{{ \Illuminate\Support\Carbon::parse($startDate)->format('d M Y') }}</span>
            &ndash;
            <span class="font-medium text-ink/60">{{ \Illuminate\Support\Carbon::parse($endDate)->format('d M Y') }}</span>
        </p>
    </div>

    {{-- KPI utama: hanya angka headline, masing-masing sekali tampil di sini.
         Rinciannya ada di card/section di bawah — tidak diulang sebagai card
         terpisah supaya tidak dobel. --}}
    <div class="grid grid-cols-1 @lg:grid-cols-2 @4xl:grid-cols-4 gap-4 mb-4">

        <div class="rounded-2xl border border-ink/10 bg-white shadow-card p-6">
            <p class="text-xs font-medium text-ink/50 mb-2 uppercase tracking-wide">Laba Bersih</p>
            <p class="font-display font-semibold text-2xl tnum {{ $profitLoss['net_profit'] < 0 ? 'text-red-700' : 'text-emerald-700' }}">
                Rp {{ number_format($profitLoss['net_profit'], 0, ',', '.') }}
            </p>
            <p class="text-xs text-ink/40 mt-1">Rincian di Ringkasan Laba Rugi &darr;</p>
        </div>

        <div class="rounded-2xl border border-ink/10 bg-white shadow-card p-6">
            <p class="text-xs font-medium text-ink/50 mb-2 uppercase tracking-wide">Kas Bersih</p>
            <p class="font-display font-semibold text-2xl tnum {{ $cashFlow['net_cash'] < 0 ? 'text-red-700' : 'text-emerald-700' }}">
                Rp {{ number_format($cashFlow['net_cash'], 0, ',', '.') }}
            </p>
            <p class="text-xs text-ink/40 mt-1">Kas masuk &ndash; kas keluar periode ini</p>
        </div>

        <div class="rounded-2xl bg-gradient-to-br from-ink to-[#0a0a0b] text-white shadow-panel p-6">
            <p class="text-xs font-medium text-white/50 mb-2 uppercase tracking-wide">Nilai Stok Saat Ini</p>
            <p class="font-display font-semibold text-2xl tnum text-amber-400">
                Rp {{ number_format($stockValue, 0, ',', '.') }}
            </p>
            <p class="text-xs text-white/40 mt-1">Berdasarkan harga beli (FIFO)</p>
        </div>

        <div class="rounded-2xl border border-ink/10 bg-white shadow-card p-6">
            <p class="text-xs font-medium text-ink/50 mb-2 uppercase tracking-wide">Piutang &amp; Hutang</p>
            <div class="flex items-center justify-between mt-1">
                <a href="{{ route('reports.receivable') }}" class="group">
                    <span class="block text-[11px] text-ink/40">Piutang (AR)</span>
                    <span class="font-display font-semibold text-lg tnum group-hover:text-amber-600 transition-colors">
                        Rp {{ number_format($totalReceivable, 0, ',', '.') }}
                    </span>
                </a>
                <span class="h-8 w-px bg-ink/10"></span>
                <a href="{{ route('reports.payable') }}" class="group text-right">
                    <span class="block text-[11px] text-ink/40">Hutang (AP)</span>
                    <span class="font-display font-semibold text-lg tnum group-hover:text-amber-600 transition-colors">
                        Rp {{ number_format($totalPayable, 0, ',', '.') }}
                    </span>
                </a>
            </div>
        </div>

    </div>

    {{-- Ringkasan Laba Rugi + panel Kas & Pembelian --}}
    <div class="grid grid-cols-1 @4xl:grid-cols-3 gap-4 mb-6">

        {{-- Ringkasan Laba Rugi: satu-satunya tempat untuk alur pendapatan -> retur -> HPP -> laba --}}
        <div class="@4xl:col-span-2 rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
            <div class="px-6 py-4 border-b border-ink/10 flex items-center gap-2.5">
                <span class="h-7 w-7 rounded-lg bg-amber-50 flex items-center justify-center">
                    <svg viewBox="0 0 24 24" class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 16 6-6 4 4 8-9M13.5 5H21v7.5"/></svg>
                </span>
                <h2 class="font-display font-semibold">Ringkasan Laba Rugi</h2>
            </div>

            {{-- Alur utama: tiap baris mengurangi baris sebelumnya, sampai Laba Bersih --}}
            <div class="divide-y divide-ink/[0.06] text-sm">
                <div class="flex items-center justify-between px-6 py-3.5">
                    <span class="text-ink/60">Penjualan Kotor</span>
                    <span class="tnum">Rp {{ number_format($profitLoss['revenue_gross'], 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between px-6 py-3.5">
                    <span class="text-ink/60">
                        <a href="{{ route('reports.sales-return', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="hover:text-amber-700 underline decoration-dotted underline-offset-2">Retur Penjualan (SO)</a>
                    </span>
                    <span class="tnum text-red-700">- Rp {{ number_format($profitLoss['sales_return'], 0, ',', '.') }}</span>
                </div>
                <div class="px-6 py-3.5 font-medium bg-ink/[0.02]">
                    <div class="flex items-center justify-between">
                        <span>Penjualan Bersih</span>
                        <span class="tnum">Rp {{ number_format($profitLoss['revenue'], 0, ',', '.') }}</span>
                    </div>
                    <p class="text-xs font-normal text-ink/40 mt-1">
                        Diterima Rp {{ number_format($profitLoss['revenue_paid'], 0, ',', '.') }}
                        &middot; Piutang Rp {{ number_format($profitLoss['revenue_pending'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex items-center justify-between px-6 py-3.5">
                    <span class="text-ink/60">HPP (FIFO)</span>
                    <span class="tnum text-red-700">- Rp {{ number_format($profitLoss['hpp'], 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between px-6 py-3.5 font-medium bg-ink/[0.02]">
                    <span>Laba Kotor</span>
                    <span class="tnum">Rp {{ number_format($profitLoss['gross_profit'], 0, ',', '.') }}</span>
                </div>
                <div class="px-6 py-3.5">
                    <div class="flex items-center justify-between">
                        <span class="text-ink/60">Biaya Operasional</span>
                        <span class="tnum text-red-700">- Rp {{ number_format($profitLoss['operational_expense'], 0, ',', '.') }}</span>
                    </div>
                    @if ($profitLoss['expense_by_category']->isNotEmpty())
                        <div class="mt-2 space-y-1 pl-3 border-l-2 border-ink/10">
                            @foreach ($profitLoss['expense_by_category'] as $row)
                                <div class="flex items-center justify-between text-xs text-ink/40">
                                    <span>{{ $row->name }}</span>
                                    <span class="tnum">Rp {{ number_format($row->total, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="px-6 py-3.5">
                    <div class="flex items-center justify-between">
                        <span class="text-ink/60">
                            <a href="{{ route('reports.incomes', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="hover:text-amber-700 underline decoration-dotted underline-offset-2">Pemasukan Lain</a>
                        </span>
                        <span class="tnum text-emerald-700">+ Rp {{ number_format($profitLoss['other_income'], 0, ',', '.') }}</span>
                    </div>
                    @if ($profitLoss['income_by_category']->isNotEmpty())
                        <div class="mt-2 space-y-1 pl-3 border-l-2 border-ink/10">
                            @foreach ($profitLoss['income_by_category'] as $row)
                                <div class="flex items-center justify-between text-xs text-ink/40">
                                    <span>{{ $row->name }}</span>
                                    <span class="tnum">Rp {{ number_format($row->total, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="flex items-center justify-between px-6 py-3.5 font-semibold bg-amber-50/60">
                    <span>Laba Bersih</span>
                    <span class="tnum {{ $profitLoss['net_profit'] < 0 ? 'text-red-700' : 'text-emerald-700' }}">
                        Rp {{ number_format($profitLoss['net_profit'], 0, ',', '.') }}
                    </span>
                </div>

                {{-- Bagi Hasil: alokasi Laba Bersih per orang, berdasar persentase di master Bagi Hasil. --}}
                <div class="px-6 py-3.5">
                    <div class="flex items-center justify-between">
                        <span class="text-ink/60">
                            <a href="{{ route('profit-shares.index') }}" class="hover:text-amber-700 underline decoration-dotted underline-offset-2">Bagi Hasil</a>
                            <span class="text-xs text-ink/35">({{ number_format($profitLoss['total_profit_share_percentage'], 2, ',', '.') }}%)</span>
                        </span>
                        <span class="tnum text-amber-700">Rp {{ number_format($profitLoss['total_profit_share_amount'], 0, ',', '.') }}</span>
                    </div>
                    @if ($profitLoss['profit_shares']->isNotEmpty())
                        <div class="mt-2 space-y-1 pl-3 border-l-2 border-ink/10">
                            @foreach ($profitLoss['profit_shares'] as $share)
                                <div class="flex items-center justify-between text-xs text-ink">
                                    <span>{{ $share['name'] }} <span class="text-ink/30">({{ number_format($share['percentage'], 2, ',', '.') }}%)</span></span>
                                    <span class="tnum">Rp {{ number_format($share['amount'], 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-ink/35 mt-1.5">Belum ada data di master Bagi Hasil.</p>
                    @endif
                </div>
            </div>

            {{-- Info tambahan, di luar alur laba rugi (belum memengaruhi laba) --}}
            <div class="border-t border-ink/10 bg-ink/[0.015] px-6 py-3.5">
                <p class="text-xs text-ink/40">
                    Retur Pembelian (PO) periode ini: Rp {{ number_format($profitLoss['purchase_return'], 0, ',', '.') }}
                    &mdash; mengurangi hutang, belum memengaruhi laba (lihat panel Pembelian di samping).
                </p>
                @if ($profitLoss['non_profit_loss_income'] > 0)
                    <p class="text-xs text-ink/40 mt-2 pt-2 border-t border-ink/10">
                        Pemasukan Permodalan/Pendanaan (Modal, Pinjaman, dll) periode ini: Rp {{ number_format($profitLoss['non_profit_loss_income'], 0, ',', '.') }}
                        &mdash; tercatat sebagai kas masuk, tidak dihitung sebagai laba.
                    </p>
                @endif
            </div>
        </div>

        {{-- Panel Kas & Pembelian: rangkuman arus kas + PO, saling melengkapi Ringkasan Laba Rugi --}}
        <div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-ink/10">
                <h2 class="font-display font-semibold">Kas &amp; Pembelian</h2>
                <p class="text-xs text-ink/40 mt-0.5">Riil (kas) &amp; PO periode ini</p>
            </div>
            <div class="divide-y divide-ink/[0.06] text-sm flex-1">
                <div class="flex items-center justify-between px-6 py-3">
                    <span class="text-ink/60 flex items-center gap-1.5">
                        <svg viewBox="0 0 24 24" class="h-3.5 w-3.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
                        Kas Masuk
                    </span>
                    <span class="tnum text-emerald-700">Rp {{ number_format($cashFlow['total_in'], 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between px-6 py-3">
                    <span class="text-ink/60 flex items-center gap-1.5">
                        <svg viewBox="0 0 24 24" class="h-3.5 w-3.5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                        Kas Keluar
                    </span>
                    <span class="tnum text-red-700">Rp {{ number_format($cashFlow['total_out'], 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between px-6 py-3">
                    <span class="text-ink/60">Biaya Operasional</span>
                    <span class="tnum">Rp {{ number_format($expenseBreakdown['operational'], 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between px-6 py-3">
                    <span class="text-ink/60">Total Pembelian (PO)</span>
                    <span class="tnum">Rp {{ number_format($profitLoss['purchase'], 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between px-6 py-3">
                    <span class="text-ink/60">
                        <a href="{{ route('reports.purchase-return', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="hover:text-amber-700 underline decoration-dotted underline-offset-2">Retur Pembelian (PO)</a>
                    </span>
                    <span class="tnum">Rp {{ number_format($profitLoss['purchase_return'], 0, ',', '.') }}</span>
                </div>
            </div>
            <p class="text-[11px] text-ink/35 px-6 py-2.5 border-t border-ink/10">
                Pembelian belum jadi HPP &mdash; baru diakui saat barangnya terjual.
            </p>
        </div>

    </div>

    {{-- Grafik --}}
    <div class="grid grid-cols-1 @4xl:grid-cols-3 gap-4 mb-6">

        <div class="@4xl:col-span-2 rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
            <div class="px-6 py-4 border-b border-ink/10">
                <h2 class="font-display font-semibold">Tren Penjualan &amp; Arus Kas Harian</h2>
                <p class="text-xs text-ink/40 mt-0.5">Penjualan (accrual) dibanding kas masuk &amp; keluar riil</p>
            </div>
            <div class="p-4">
                <div class="relative h-56">
                    <canvas id="trendChart"></canvas>
                    <div id="trendChartEmpty" class="hidden absolute inset-0 flex flex-col items-center justify-center text-center gap-2 text-ink/40">
                        <svg viewBox="0 0 24 24" class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18M7 15l4-4 3 3 5-6"/><path d="m3 3 18 18" stroke="currentColor" opacity="0.5"/></svg>
                        <p class="text-xs">Belum ada data untuk periode ini</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
            <div class="px-6 py-4 border-b border-ink/10">
                <h2 class="font-display font-semibold">Komposisi Biaya</h2>
                <p class="text-xs text-ink/40 mt-0.5">Operasional vs. kas ke supplier</p>
            </div>
            <div class="p-4">
                <div class="relative h-56">
                    <canvas id="expenseChart"></canvas>
                    <div id="expenseChartEmpty" class="hidden absolute inset-0 flex flex-col items-center justify-center text-center gap-2 text-ink/40">
                        <svg viewBox="0 0 24 24" class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 0 1 0 18"/></svg>
                        <p class="text-xs">Belum ada biaya pada periode ini</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Produk & Stok --}}
    <div class="grid grid-cols-1 @2xl:grid-cols-2 gap-4">

        {{-- Stok menipis --}}
        <div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
            <div class="px-6 py-4 border-b border-ink/10 flex items-center gap-2.5">
                <span class="h-7 w-7 rounded-lg bg-red-50 flex items-center justify-center">
                    <svg viewBox="0 0 24 24" class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4m0 4h.01M10.3 3.86 1.8 18a1.5 1.5 0 0 0 1.3 2.25h17.8A1.5 1.5 0 0 0 22.2 18L13.7 3.86a1.5 1.5 0 0 0-2.6 0Z"/></svg>
                </span>
                <h2 class="font-display font-semibold">Stok Menipis</h2>
            </div>
            @if ($lowStockProducts->isEmpty())
                <p class="px-6 py-8 text-sm text-ink/40 text-center">Tidak ada produk dengan stok rendah.</p>
            @else
                <div class="divide-y divide-ink/[0.06] text-sm">
                    @foreach ($lowStockProducts as $product)
                        <div class="flex items-center justify-between px-6 py-3.5">
                            <div class="min-w-0">
                                <p class="truncate font-medium">{{ $product->name }}</p>
                                <p class="text-xs text-ink/40">{{ $product->category->name ?? '—' }}</p>
                            </div>
                            <span class="tnum font-semibold rounded-full px-2.5 py-1 text-xs shrink-0
                                {{ $product->qty_on_hand == 0 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800' }}">
                                {{ $product->qty_on_hand }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Produk terlaris (tabel — sudah mencakup qty & revenue, jadi tidak perlu grafik batang terpisah) --}}
        <div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
            <div class="px-6 py-4 border-b border-ink/10 flex items-center gap-2.5">
                <span class="h-7 w-7 rounded-lg bg-emerald-50 flex items-center justify-center">
                    <svg viewBox="0 0 24 24" class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18M7 15l4-4 3 3 5-6"/></svg>
                </span>
                <h2 class="font-display font-semibold">Produk Terlaris</h2>
            </div>
            @if ($topProducts->isEmpty())
                <p class="px-6 py-8 text-sm text-ink/40 text-center">Belum ada penjualan pada periode ini.</p>
            @else
                <div class="divide-y divide-ink/[0.06] text-sm">
                    @foreach ($topProducts as $i => $product)
                        <div class="flex items-center justify-between px-6 py-3.5">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="h-6 w-6 shrink-0 rounded-full bg-ink/5 text-ink/50 text-xs font-semibold flex items-center justify-center">{{ $i + 1 }}</span>
                                <div class="min-w-0">
                                    <p class="truncate font-medium">{{ $product->product_name }}</p>
                                    <p class="text-xs text-ink/40">Rp {{ number_format($product->total_revenue, 0, ',', '.') }}</p>
                                </div>
                            </div>
                            <span class="tnum font-semibold rounded-full px-2.5 py-1 text-xs shrink-0 bg-emerald-100 text-emerald-800">
                                {{ (int) $product->total_qty }} terjual
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Helper: sembunyikan canvas & tampilkan pesan "tidak ada data" kalau semua nilainya kosong/0.
        function showEmptyState(canvasId, emptyId) {
            document.getElementById(canvasId).classList.add('hidden');
            document.getElementById(emptyId).classList.remove('hidden');
        }
        const hasAnyValue = (arr) => arr.some(v => Number(v) > 0);

        const trendLabels = @json($dailySalesTrend->keys());
        const salesData   = @json($dailySalesTrend->values());

        const cashDaily   = @json($cashFlow['daily']);
        const cashInData  = trendLabels.map(d => (cashDaily[d]?.in ?? 0));
        const cashOutData = trendLabels.map(d => (cashDaily[d]?.out ?? 0));

        const displayLabels = trendLabels.map(d => {
            const parts = d.split('-');
            return parts[2] + '/' + parts[1];
        });

        if (!hasAnyValue(salesData) && !hasAnyValue(cashInData) && !hasAnyValue(cashOutData)) {
            showEmptyState('trendChart', 'trendChartEmpty');
        } else {
            new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: {
                    labels: displayLabels,
                    datasets: [
                        {
                            label: 'Penjualan',
                            data: salesData,
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245,158,11,0.12)',
                            tension: 0.3,
                            fill: true,
                        },
                        {
                            label: 'Kas Masuk',
                            data: cashInData,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16,185,129,0.08)',
                            tension: 0.3,
                        },
                        {
                            label: 'Kas Keluar',
                            data: cashOutData,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239,68,68,0.08)',
                            tension: 0.3,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
                    scales: {
                        y: { ticks: { callback: (v) => 'Rp ' + (v / 1000) + 'rb' } },
                    },
                },
            });
        }

        const expenseData = [{{ $expenseBreakdown['operational'] }}, {{ $expenseBreakdown['purchase'] }}];

        if (!hasAnyValue(expenseData)) {
            showEmptyState('expenseChart', 'expenseChartEmpty');
        } else {
            new Chart(document.getElementById('expenseChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Biaya Operasional', 'Kas ke Supplier (pembelian)'],
                    datasets: [{
                        data: expenseData,
                        backgroundColor: ['#f59e0b', '#111214'],
                        borderWidth: 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
                },
            });
        }
    });
</script>
@endpush
