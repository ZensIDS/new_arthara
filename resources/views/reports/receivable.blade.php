@extends('layouts.app')

@section('title', 'Laporan Piutang (AR)')
@section('page-title', 'Laporan Piutang (AR)')

@section('content')
<div>

    {{-- KPI: agregasi SQL atas SELURUH SO belum lunas, independen dari pagination/pencarian --}}
    <div class="grid grid-cols-1 @4xl:grid-cols-2 gap-4 mb-6">
        <div class="rounded-2xl border border-ink/10 bg-white shadow-card p-6">
            <p class="text-xs font-medium text-ink/50 mb-2 uppercase tracking-wide">Jumlah SO Belum Lunas</p>
            <p class="font-display font-semibold text-2xl tnum">{{ number_format($kpis['count'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl bg-gradient-to-br from-ink to-[#0a0a0b] text-white shadow-panel p-6">
            <p class="text-xs font-medium text-white/50 mb-2 uppercase tracking-wide">Total Sisa Piutang</p>
            <p class="font-display font-semibold text-2xl tnum text-amber-400">Rp {{ number_format($kpis['total_outstanding'], 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="flex items-center justify-between mb-4 gap-3">
        {{-- Search: AJAX, tidak perlu tekan Enter --}}
        <div class="relative flex-1 max-w-sm">
            <svg viewBox="0 0 24 24" class="h-4 w-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/35" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m21 21-4.3-4.3"/></svg>
            <input
                id="receivable-search"
                type="text"
                value="{{ $search }}"
                placeholder="Cari nomor SO atau customer..."
                autocomplete="off"
                class="w-full rounded-xl border border-ink/12 pl-10 pr-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow"
            >
        </div>
        <a href="{{ route('reports.export.receivable') }}"
           class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-600/20 bg-emerald-50 text-emerald-700 text-sm font-medium px-4 py-2 hover:bg-emerald-100 transition-colors whitespace-nowrap shrink-0">
            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
            Export Excel
        </a>
    </div>

    <div id="receivable-table-container">
        @include('reports._receivable-table')
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.initAjaxListSearch({
            inputEl: document.getElementById('receivable-search'),
            containerEl: document.getElementById('receivable-table-container'),
            baseUrl: '{{ route('reports.receivable') }}',
        });
    });
</script>
@endpush
