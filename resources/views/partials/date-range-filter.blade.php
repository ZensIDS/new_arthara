{{--
    Partial filter rentang tanggal untuk halaman listing/index (bukan reports).
    Beda dari reports/partials/date-filter: di sini filter sifatnya opsional
    (kalau kosong, semua data tetap tampil) dan mempertahankan query lain
    (misal ?search=...) lewat $extraParams.

    Variabel yang dibutuhkan:
    - $routeName   : nama route halaman ini (untuk action form & link reset)
    - $startDate   : value awal (nullable, format Y-m-d)
    - $endDate     : value akhir (nullable, format Y-m-d)
    - $dateLabel   : (opsional) label field tanggal, default "Tanggal"
    - $extraParams : (opsional) array query string lain yang perlu dipertahankan, misal ['search' => $search]
--}}
@php
    $dateLabel   = $dateLabel ?? 'Tanggal';
    $extraParams = $extraParams ?? [];
@endphp
<div class="mb-6 rounded-2xl border border-ink/10 bg-white shadow-card p-4 sm:p-5">
    <form method="GET" action="{{ route($routeName) }}" class="flex flex-wrap items-end gap-3">
        @foreach ($extraParams as $key => $value)
            @if (filled($value))
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach

        <div>
            <label class="block text-xs font-medium text-ink/50 mb-1 uppercase tracking-wide">{{ $dateLabel }} Dari</label>
            <input type="date" name="start_date" value="{{ $startDate }}"
                   class="rounded-lg border border-ink/15 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400/60">
        </div>
        <div>
            <label class="block text-xs font-medium text-ink/50 mb-1 uppercase tracking-wide">{{ $dateLabel }} Sampai</label>
            <input type="date" name="end_date" value="{{ $endDate }}"
                   class="rounded-lg border border-ink/15 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400/60">
        </div>

        <button type="submit"
                class="rounded-lg bg-ink text-white text-sm font-medium px-4 py-2 hover:bg-ink/90 transition-colors">
            Terapkan
        </button>

        @if (filled($startDate) || filled($endDate))
            <a href="{{ route($routeName, $extraParams) }}"
               class="text-sm font-medium px-4 py-2 rounded-lg border border-ink/12 text-ink/60 hover:bg-ink/[0.03] transition-colors">
                Reset
            </a>
        @endif

        <div class="flex items-center gap-1.5 ml-auto flex-wrap">
            @php
                $presets = [
                    'Hari Ini'          => [now()->toDateString(), now()->toDateString()],
                    'Minggu Ini'        => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
                    'Bulan Ini'         => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
                    'Bulan Lalu'        => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
                    '30 Hari Terakhir'  => [now()->subDays(29)->toDateString(), now()->toDateString()],
                ];
            @endphp
            @foreach ($presets as $label => $range)
                <a href="{{ route($routeName, array_merge($extraParams, ['start_date' => $range[0], 'end_date' => $range[1]])) }}"
                   class="text-xs font-medium px-3 py-1.5 rounded-full border transition-colors
                          {{ $startDate === $range[0] && $endDate === $range[1] ? 'bg-amber-400/90 border-amber-400/90 text-ink' : 'border-ink/15 text-ink/60 hover:border-ink/30' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </form>
</div>
