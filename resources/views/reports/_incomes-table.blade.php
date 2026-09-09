{{--
    Partial ini dirender penuh saat load awal halaman (di-include reports/incomes.blade.php)
    DAN dikirim sebagai response AJAX saat user mengetik di kolom pencarian atau klik
    pagination (lihat ReportController@incomes & initAjaxListSearch di layouts/app.blade.php).
--}}
<div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
    @if ($data->isEmpty())
        <p class="px-5 py-10 text-center text-ink/40 text-sm">
            @if ($search)
                Tidak ada pemasukan yang cocok dengan pencarian.
            @else
                Tidak ada pemasukan pada periode / kategori ini.
            @endif
        </p>
    @else
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-ink/40 text-xs uppercase tracking-wide border-b border-ink/10">
                    <th class="px-5 py-3 font-medium">Tanggal</th>
                    <th class="px-5 py-3 font-medium">Kategori</th>
                    <th class="px-5 py-3 font-medium">Deskripsi</th>
                    <th class="px-5 py-3 font-medium text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink/[0.06]">
                @foreach ($data as $i)
                    <tr class="hover:bg-amber-50/30 transition-colors">
                        <td class="px-5 py-3 tnum whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($i['income_date'])->format('d M Y') }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-ink/5 text-ink/60">
                                {{ $i['category'] }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-ink/70">{{ $i['description'] ?: '—' }}</td>
                        <td class="px-5 py-3 tnum text-right font-medium text-emerald-700">+Rp {{ number_format($i['amount'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="mt-4 ajax-pagination">
    {{ $data->links() }}
</div>
