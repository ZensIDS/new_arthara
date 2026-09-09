{{--
    Partial filter tanggal, dipakai di reports.profit-loss & reports.cash-flow.
    Butuh variabel: $routeName (nama route saat ini), $startDate, $endDate.
    Opsional: $exportRouteName (nama route export Excel) untuk menampilkan tombol Export Excel.
--}}
<div class="mb-6 rounded-2xl border border-ink/10 bg-white shadow-card p-4 sm:p-5">
    <form method="GET" action="{{ route($routeName) }}" class="flex flex-wrap items-end gap-3">
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

        @isset($exportRouteName)
            <a href="{{ route($exportRouteName, ['start_date' => $startDate, 'end_date' => $endDate]) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-600/20 bg-emerald-50 text-emerald-700 text-sm font-medium px-4 py-2 hover:bg-emerald-100 transition-colors">
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                Export Excel
            </a>
        @endisset

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
                <a href="{{ route($routeName, ['start_date' => $range[0], 'end_date' => $range[1]]) }}"
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
