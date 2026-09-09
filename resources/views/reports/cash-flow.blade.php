@extends('layouts.app')

@section('title', 'Laporan Arus Kas')
@section('page-title', 'Laporan Arus Kas')

@section('content')

    @include('reports.partials.date-filter', ['routeName' => 'reports.cash-flow', 'exportRouteName' => 'reports.export.cash-flow'])

    {{-- KPI --}}
    <div class="grid grid-cols-1 @4xl:grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl border border-ink/10 bg-white shadow-card p-6">
            <p class="text-xs font-medium text-ink/50 mb-2 uppercase tracking-wide">Kas Masuk</p>
            <p class="font-display font-semibold text-2xl tnum text-emerald-700">
                Rp {{ number_format($data['total_in'], 0, ',', '.') }}
            </p>
        </div>
        <div class="rounded-2xl border border-ink/10 bg-white shadow-card p-6">
            <p class="text-xs font-medium text-ink/50 mb-2 uppercase tracking-wide">Kas Keluar</p>
            <p class="font-display font-semibold text-2xl tnum text-red-700">
                Rp {{ number_format($data['total_out'], 0, ',', '.') }}
            </p>
        </div>
        <div class="rounded-2xl bg-gradient-to-br from-ink to-[#0a0a0b] text-white shadow-panel p-6">
            <p class="text-xs font-medium text-white/50 mb-2 uppercase tracking-wide">Kas Bersih (Masuk &ndash; Keluar)</p>
            <p class="font-display font-semibold text-2xl tnum {{ $data['net_cash'] < 0 ? 'text-red-400' : 'text-amber-400' }}">
                Rp {{ number_format($data['net_cash'], 0, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Grafik harian --}}
    <div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-ink/10">
            <h2 class="font-display font-semibold">Arus Kas Harian</h2>
            <p class="text-xs text-ink/40 mt-0.5">Kas masuk vs. kas keluar per hari</p>
        </div>
        <div class="p-4">
            <div class="relative h-56">
                <canvas id="cashFlowChart"></canvas>
                <div id="cashFlowChartEmpty" class="hidden absolute inset-0 flex flex-col items-center justify-center text-center gap-2 text-ink/40">
                    <svg viewBox="0 0 24 24" class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18M7 15l4-4 3 3 5-6"/></svg>
                    <p class="text-xs">Tidak ada transaksi kas pada periode ini</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Daftar transaksi. Diurutkan dari server (transaction_date DESC, lalu
         id DESC) supaya transaksi yang paling baru dicatat selalu tampil
         paling atas — termasuk saat beberapa transaksi terjadi di tanggal
         yang sama. Dipaginasi & bisa dicari (AJAX), terpisah dari agregat
         KPI & grafik di atas yang tetap menghitung semua transaksi pada
         periode ini. --}}
    <div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
        <div class="px-6 py-4 border-b border-ink/10 flex items-center justify-between gap-3">
            <h2 class="font-display font-semibold">Rincian Transaksi</h2>
            {{-- Search: AJAX, tidak perlu tekan Enter --}}
            <div class="relative max-w-xs w-full">
                <svg viewBox="0 0 24 24" class="h-4 w-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/35" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m21 21-4.3-4.3"/></svg>
                <input
                    id="cash-flow-search"
                    type="text"
                    value="{{ $search }}"
                    placeholder="Cari keterangan..."
                    autocomplete="off"
                    class="w-full rounded-xl border border-ink/12 pl-10 pr-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow"
                >
            </div>
        </div>
        <div id="cash-flow-table-container">
            @include('reports._cash-flow-table')
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.initAjaxListSearch({
            inputEl: document.getElementById('cash-flow-search'),
            containerEl: document.getElementById('cash-flow-table-container'),
            baseUrl: '{{ route('reports.cash-flow') }}',
            getExtraParams: () => ({
                start_date: '{{ $startDate }}',
                end_date: '{{ $endDate }}',
            }),
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('cashFlowChart');
        if (!el) return;

        const daily = @json($data['daily']);
        const labels = Object.keys(daily);
        const displayLabels = labels.map(d => {
            const parts = d.split('-');
            return parts[2] + '/' + parts[1];
        });

        const inData  = labels.map(d => daily[d].in);
        const outData = labels.map(d => daily[d].out);
        const hasAnyValue = (arr) => arr.some(v => Number(v) > 0);

        if (!hasAnyValue(inData) && !hasAnyValue(outData)) {
            el.classList.add('hidden');
            document.getElementById('cashFlowChartEmpty').classList.remove('hidden');
            return;
        }

        new Chart(el, {
            type: 'bar',
            data: {
                labels: displayLabels,
                datasets: [
                    {
                        label: 'Kas Masuk',
                        data: inData,
                        backgroundColor: '#10b981',
                        borderRadius: 4,
                    },
                    {
                        label: 'Kas Keluar',
                        data: outData,
                        backgroundColor: '#ef4444',
                        borderRadius: 4,
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
    });
</script>
@endpush
