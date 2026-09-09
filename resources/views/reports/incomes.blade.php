@extends('layouts.app')

@section('title', 'Laporan Pemasukan Lain')
@section('page-title', 'Laporan Pemasukan Lain')

@section('content')

    {{-- Filter: rentang tanggal + kategori dalam satu form --}}
    <div class="mb-6 rounded-2xl border border-ink/10 bg-white shadow-card p-4 sm:p-5">
        <form method="GET" action="{{ route('reports.incomes') }}" class="flex flex-wrap items-end gap-3">
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
            <div>
                <label class="block text-xs font-medium text-ink/50 mb-1 uppercase tracking-wide">Kategori</label>
                <select name="category_id"
                        class="rounded-lg border border-ink/15 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400/60 bg-white">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ (int) $categoryId === $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                    class="rounded-lg bg-ink text-white text-sm font-medium px-4 py-2 hover:bg-ink/90 transition-colors">
                Terapkan
            </button>

            <a href="{{ route('reports.export.incomes', ['start_date' => $startDate, 'end_date' => $endDate, 'category_id' => $categoryId]) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-600/20 bg-emerald-50 text-emerald-700 text-sm font-medium px-4 py-2 hover:bg-emerald-100 transition-colors">
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                Export Excel
            </a>

            <div class="flex items-center gap-1.5 ml-auto flex-wrap">
                @php
                    $presets = [
                        'Hari Ini'         => [now()->toDateString(), now()->toDateString()],
                        'Minggu Ini'       => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
                        'Bulan Ini'        => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
                        'Bulan Lalu'       => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
                        '30 Hari Terakhir' => [now()->subDays(29)->toDateString(), now()->toDateString()],
                    ];
                @endphp
                @foreach ($presets as $label => $range)
                    <a href="{{ route('reports.incomes', ['start_date' => $range[0], 'end_date' => $range[1], 'category_id' => $categoryId]) }}"
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
            @if ($categoryId)
                &middot; Kategori:
                <span class="font-medium text-ink/60">{{ $categories->firstWhere('id', $categoryId)->name ?? '—' }}</span>
            @endif
        </p>
    </div>

    {{-- KPI: agregasi SQL atas SELURUH baris pada filter ini, independen dari pagination/pencarian deskripsi --}}
    <div class="grid grid-cols-1 @4xl:grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl border border-ink/10 bg-white shadow-card p-6">
            <p class="text-xs font-medium text-ink/50 mb-2 uppercase tracking-wide">Jumlah Transaksi</p>
            <p class="font-display font-semibold text-2xl tnum">{{ number_format($kpis['count'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl bg-gradient-to-br from-ink to-[#0a0a0b] text-white shadow-panel p-6">
            <p class="text-xs font-medium text-white/50 mb-2 uppercase tracking-wide">Total Pemasukan</p>
            <p class="font-display font-semibold text-2xl tnum text-emerald-400">Rp {{ number_format($kpis['total'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-ink/10 bg-white shadow-card p-6">
            <p class="text-xs font-medium text-ink/50 mb-2 uppercase tracking-wide">Rata-rata / Transaksi</p>
            <p class="font-display font-semibold text-2xl tnum">Rp {{ number_format($kpis['average'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Search: AJAX, tidak perlu tekan Enter --}}
    <div class="relative max-w-sm mb-4">
        <svg viewBox="0 0 24 24" class="h-4 w-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/35" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m21 21-4.3-4.3"/></svg>
        <input
            id="incomes-search"
            type="text"
            value="{{ $search }}"
            placeholder="Cari deskripsi pemasukan..."
            autocomplete="off"
            class="w-full rounded-xl border border-ink/12 pl-10 pr-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow"
        >
    </div>

    {{-- Tabel per item, urut tanggal terbaru --}}
    <div id="incomes-table-container">
        @include('reports._incomes-table')
    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.initAjaxListSearch({
            inputEl: document.getElementById('incomes-search'),
            containerEl: document.getElementById('incomes-table-container'),
            baseUrl: '{{ route('reports.incomes') }}',
            getExtraParams: () => ({
                start_date: '{{ $startDate }}',
                end_date: '{{ $endDate }}',
                category_id: '{{ $categoryId }}',
            }),
        });
    });
</script>
@endpush
