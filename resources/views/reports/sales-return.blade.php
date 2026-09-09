@extends('layouts.app')

@section('title', 'Laporan Retur Penjualan')
@section('page-title', 'Laporan Retur Penjualan (SO)')

@section('content')

    @include('reports.partials.date-filter', ['routeName' => 'reports.sales-return', 'exportRouteName' => 'reports.export.sales-return'])

    {{-- KPI: agregasi SQL atas SELURUH retur pada rentang tanggal, independen dari pagination/pencarian --}}
    <div class="grid grid-cols-1 @4xl:grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl border border-ink/10 bg-white shadow-card p-6">
            <p class="text-xs font-medium text-ink/50 mb-2 uppercase tracking-wide">Jumlah Retur</p>
            <p class="font-display font-semibold text-2xl tnum">{{ number_format($kpis['count'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl bg-gradient-to-br from-ink to-[#0a0a0b] text-white shadow-panel p-6">
            <p class="text-xs font-medium text-white/50 mb-2 uppercase tracking-wide">Total Nilai Retur</p>
            <p class="font-display font-semibold text-2xl tnum text-red-400">Rp {{ number_format($kpis['total'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-ink/10 bg-white shadow-card p-6">
            <p class="text-xs font-medium text-ink/50 mb-2 uppercase tracking-wide">Total HPP Retur</p>
            <p class="font-display font-semibold text-2xl tnum">Rp {{ number_format($kpis['total_hpp'], 0, ',', '.') }}</p>
            <p class="text-xs text-ink/40 mt-1">Nilai barang yang kembali ke stok</p>
        </div>
    </div>

    {{-- Search: AJAX, tidak perlu tekan Enter --}}
    <div class="relative max-w-sm mb-4">
        <svg viewBox="0 0 24 24" class="h-4 w-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/35" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m21 21-4.3-4.3"/></svg>
        <input
            id="sales-return-search"
            type="text"
            value="{{ $search }}"
            placeholder="Cari no. retur, no. SO, atau customer..."
            autocomplete="off"
            class="w-full rounded-xl border border-ink/12 pl-10 pr-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow"
        >
    </div>

    <div id="sales-return-table-container">
        @include('reports._sales-return-table')
    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.initAjaxListSearch({
            inputEl: document.getElementById('sales-return-search'),
            containerEl: document.getElementById('sales-return-table-container'),
            baseUrl: '{{ route('reports.sales-return') }}',
            getExtraParams: () => ({
                start_date: '{{ $startDate }}',
                end_date: '{{ $endDate }}',
            }),
        });
    });
</script>
@endpush
