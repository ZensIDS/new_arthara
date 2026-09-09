@extends('layouts.app')

@section('title', 'Laporan Laba Rugi')
@section('page-title', 'Laporan Laba Rugi')

@section('content')

    @include('reports.partials.date-filter', ['routeName' => 'reports.profit-loss', 'exportRouteName' => 'reports.export.profit-loss'])

    <div class="grid grid-cols-1 @4xl:grid-cols-3 gap-4">

        {{-- Ringkasan Laba Rugi --}}
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
                    <span class="tnum">Rp {{ number_format($data['revenue_gross'], 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between px-6 py-3.5">
                    <span class="text-ink/60">
                        <a href="{{ route('reports.sales-return', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="hover:text-amber-700 underline decoration-dotted underline-offset-2">Retur Penjualan (SO)</a>
                    </span>
                    <span class="tnum text-red-700">- Rp {{ number_format($data['sales_return'], 0, ',', '.') }}</span>
                </div>
                <div class="px-6 py-3.5 font-medium bg-ink/[0.02]">
                    <div class="flex items-center justify-between">
                        <span>Penjualan Bersih</span>
                        <span class="tnum">Rp {{ number_format($data['revenue'], 0, ',', '.') }}</span>
                    </div>
                    <p class="text-xs font-normal text-ink/40 mt-1">
                        Diterima Rp {{ number_format($data['revenue_paid'], 0, ',', '.') }}
                        &middot; Piutang Rp {{ number_format($data['revenue_pending'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex items-center justify-between px-6 py-3.5">
                    <span class="text-ink/60">HPP (FIFO)</span>
                    <span class="tnum text-red-700">- Rp {{ number_format($data['hpp'], 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between px-6 py-3.5 font-medium bg-ink/[0.02]">
                    <span>Laba Kotor</span>
                    <span class="tnum">Rp {{ number_format($data['gross_profit'], 0, ',', '.') }}</span>
                </div>
                <div class="px-6 py-3.5">
                    <div class="flex items-center justify-between">
                        <span class="text-ink/60">Biaya Operasional</span>
                        <span class="tnum text-red-700">- Rp {{ number_format($data['operational_expense'], 0, ',', '.') }}</span>
                    </div>
                    @if ($data['expense_by_category']->isNotEmpty())
                        <div class="mt-2 space-y-1 pl-3 border-l-2 border-ink/10">
                            @foreach ($data['expense_by_category'] as $row)
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
                        <span class="tnum text-emerald-700">+ Rp {{ number_format($data['other_income'], 0, ',', '.') }}</span>
                    </div>
                    @if ($data['income_by_category']->isNotEmpty())
                        <div class="mt-2 space-y-1 pl-3 border-l-2 border-ink/10">
                            @foreach ($data['income_by_category'] as $row)
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
                    <span class="tnum {{ $data['net_profit'] < 0 ? 'text-red-700' : 'text-emerald-700' }}">
                        Rp {{ number_format($data['net_profit'], 0, ',', '.') }}
                    </span>
                </div>

                {{-- Bagi Hasil: alokasi Laba Bersih per orang, berdasar persentase di master Bagi Hasil.
                     Tidak mengubah Laba Bersih — murni pembagian dari nilai di atas. --}}
                <div class="px-6 py-3.5">
                    <div class="flex items-center justify-between">
                        <span class="text-ink/60">
                            <a href="{{ route('profit-shares.index') }}" class="hover:text-amber-700 underline decoration-dotted underline-offset-2">Bagi Hasil</a>
                            <span class="text-xs text-ink/35">({{ number_format($data['total_profit_share_percentage'], 2, ',', '.') }}%)</span>
                        </span>
                        <span class="tnum text-amber-700">Rp {{ number_format($data['total_profit_share_amount'], 0, ',', '.') }}</span>
                    </div>
                    @if ($data['profit_shares']->isNotEmpty())
                        <div class="mt-2 space-y-1 pl-3 border-l-2 border-ink/10">
                            @foreach ($data['profit_shares'] as $share)
                                <div class="flex items-center justify-between text-xs text-ink/40">
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
                <p class="text-xs text-ink/40 mb-2.5">
                    Pembelian (PO) &mdash; belum memengaruhi laba, karena masih berupa stok. Baru jadi HPP saat barangnya terjual.
                </p>
                <div class="flex items-center justify-between text-xs text-ink/50">
                    <span>Total Pembelian periode ini</span>
                    <span class="tnum">Rp {{ number_format($data['purchase'], 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between text-xs text-ink/50 mt-1">
                    <span>Retur Pembelian (PO)</span>
                    <span class="tnum">Rp {{ number_format($data['purchase_return'], 0, ',', '.') }}</span>
                </div>
                @if ($data['non_profit_loss_income'] > 0)
                    <div class="mt-3 pt-3 border-t border-ink/10">
                        <p class="text-xs text-ink/40 mb-2">
                            Pemasukan Permodalan/Pendanaan &mdash; tidak dihitung sebagai laba (mis. Modal Disetor, Pinjaman Bank), meski tetap tercatat sebagai kas masuk di Laporan Arus Kas.
                        </p>
                        <div class="flex items-center justify-between text-xs text-ink/50">
                            <span>
                                <a href="{{ route('reports.incomes', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="hover:text-amber-700 underline decoration-dotted underline-offset-2">Total periode ini</a>
                            </span>
                            <span class="tnum">Rp {{ number_format($data['non_profit_loss_income'], 0, ',', '.') }}</span>
                        </div>
                        @foreach ($data['non_profit_loss_income_by_category'] as $row)
                            <div class="flex items-center justify-between text-xs text-ink/40 mt-1 pl-3">
                                <span>{{ $row->name }}</span>
                                <span class="tnum">Rp {{ number_format($row->total, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Biaya operasional & pemasukan lain per kategori --}}
        <div class="flex flex-col gap-4">
        <div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
            <div class="px-6 py-4 border-b border-ink/10">
                <h2 class="font-display font-semibold">Biaya per Kategori</h2>
                <p class="text-xs text-ink/40 mt-0.5">Rincian biaya operasional</p>
            </div>
            @if ($data['expense_by_category']->isEmpty())
                <p class="px-6 py-8 text-sm text-ink/40 text-center">Tidak ada biaya operasional pada periode ini.</p>
            @else
                <div class="p-4">
                    <div class="relative h-56">
                        <canvas id="expenseCategoryChart"></canvas>
                        <div id="expenseCategoryChartEmpty" class="hidden absolute inset-0 flex flex-col items-center justify-center text-center gap-2 text-ink/40">
                            <svg viewBox="0 0 24 24" class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 0 1 0 18"/></svg>
                            <p class="text-xs">Tidak ada biaya operasional pada periode ini</p>
                        </div>
                    </div>
                </div>
                <div class="divide-y divide-ink/[0.06] text-sm">
                    @foreach ($data['expense_by_category'] as $row)
                        <div class="flex items-center justify-between px-6 py-3">
                            <span class="text-ink/60">{{ $row->name }}</span>
                            <span class="tnum">Rp {{ number_format($row->total, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Pemasukan lain per kategori --}}
        <div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
            <div class="px-6 py-4 border-b border-ink/10">
                <h2 class="font-display font-semibold">Pemasukan Lain per Kategori</h2>
                <p class="text-xs text-ink/40 mt-0.5">Rincian pemasukan di luar penjualan</p>
            </div>
            @if ($data['income_by_category']->isEmpty())
                <p class="px-6 py-8 text-sm text-ink/40 text-center">Tidak ada pemasukan lain pada periode ini.</p>
            @else
                <div class="p-4">
                    <div class="relative h-56">
                        <canvas id="incomeCategoryChart"></canvas>
                        <div id="incomeCategoryChartEmpty" class="hidden absolute inset-0 flex flex-col items-center justify-center text-center gap-2 text-ink/40">
                            <svg viewBox="0 0 24 24" class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 0 1 0 18"/></svg>
                            <p class="text-xs">Tidak ada pemasukan lain pada periode ini</p>
                        </div>
                    </div>
                </div>
                <div class="divide-y divide-ink/[0.06] text-sm">
                    @foreach ($data['income_by_category'] as $row)
                        <div class="flex items-center justify-between px-6 py-3">
                            <span class="text-ink/60">{{ $row->name }}</span>
                            <span class="tnum">Rp {{ number_format($row->total, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        </div>

        {{-- Retur SO & PO periode ini --}}
        <div class="@4xl:col-span-3 rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
            <div class="px-6 py-4 border-b border-ink/10 flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h2 class="font-display font-semibold">Pembelian &amp; Retur SO / PO</h2>
                    <p class="text-xs text-ink/40 mt-0.5">Nilai pada periode yang dipilih (berdasar tanggal transaksi / tanggal retur)</p>
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <a href="{{ route('reports.sales-return', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
                       class="rounded-full border border-red-600/20 bg-red-50 text-red-700 font-medium px-3 py-1.5 hover:bg-red-100 transition-colors">
                        Detail Retur Penjualan &rarr;
                    </a>
                    <a href="{{ route('reports.purchase-return', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
                       class="rounded-full border border-amber-600/20 bg-amber-50 text-amber-700 font-medium px-3 py-1.5 hover:bg-amber-100 transition-colors">
                        Detail Retur Pembelian &rarr;
                    </a>
                </div>
            </div>
            <div class="grid grid-cols-1 @4xl:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-ink/[0.06] text-sm">
                <div class="px-6 py-4">
                    <p class="text-ink/60 mb-1">Total Pembelian (PO)</p>
                    <p class="tnum text-lg font-semibold">Rp {{ number_format($data['purchase'], 0, ',', '.') }}</p>
                </div>
                <div class="px-6 py-4">
                    <p class="text-ink/60 mb-1">Retur Pembelian (PO)</p>
                    <p class="tnum text-lg font-semibold text-amber-700">Rp {{ number_format($data['purchase_return'], 0, ',', '.') }}</p>
                </div>
                <div class="px-6 py-4">
                    <p class="text-ink/60 mb-1">Retur Penjualan (SO)</p>
                    <p class="tnum text-lg font-semibold text-red-700">Rp {{ number_format($data['sales_return'], 0, ',', '.') }}</p>
                </div>
                <div class="px-6 py-4">
                    <p class="text-ink/60 mb-1">HPP Retur Penjualan</p>
                    <p class="tnum text-lg font-semibold">Rp {{ number_format($data['sales_return_hpp'], 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('expenseCategoryChart');
        if (!el) return;

        const categories = @json($data['expense_by_category']->pluck('name'));
        const totals = @json($data['expense_by_category']->pluck('total'));
        const palette = ['#f59e0b', '#111214', '#10b981', '#ef4444', '#6366f1', '#eab308', '#0ea5e9', '#ec4899'];

        if (!totals.some(v => Number(v) > 0)) {
            el.classList.add('hidden');
            document.getElementById('expenseCategoryChartEmpty').classList.remove('hidden');
            return;
        }

        new Chart(el, {
            type: 'doughnut',
            data: {
                labels: categories,
                datasets: [{
                    data: totals,
                    backgroundColor: categories.map((_, i) => palette[i % palette.length]),
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
            },
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('incomeCategoryChart');
        if (!el) return;

        const categories = @json($data['income_by_category']->pluck('name'));
        const totals = @json($data['income_by_category']->pluck('total'));
        const palette = ['#10b981', '#f59e0b', '#111214', '#6366f1', '#eab308', '#0ea5e9', '#ec4899', '#ef4444'];

        if (!totals.some(v => Number(v) > 0)) {
            el.classList.add('hidden');
            document.getElementById('incomeCategoryChartEmpty').classList.remove('hidden');
            return;
        }

        new Chart(el, {
            type: 'doughnut',
            data: {
                labels: categories,
                datasets: [{
                    data: totals,
                    backgroundColor: categories.map((_, i) => palette[i % palette.length]),
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
            },
        });
    });
</script>
@endpush
