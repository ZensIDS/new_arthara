{{--
    Partial ini dirender penuh saat load awal halaman (di-include reports/cash-flow.blade.php)
    DAN dikirim sebagai response AJAX saat user mengetik di kolom pencarian atau klik
    pagination (lihat ReportController@cashFlow & initAjaxListSearch di layouts/app.blade.php).
--}}
@if ($details->isEmpty())
    <p class="px-6 py-10 text-sm text-ink/40 text-center">
        {{ $search ? 'Tidak ada transaksi yang cocok dengan pencarian.' : 'Tidak ada transaksi kas pada periode ini.' }}
    </p>
@else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-ink/40 uppercase tracking-wide border-b border-ink/[0.06]">
                    <th class="px-6 py-3 font-medium">Tanggal</th>
                    <th class="px-6 py-3 font-medium">Keterangan</th>
                    <th class="px-6 py-3 font-medium">Arah</th>
                    <th class="px-6 py-3 font-medium text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink/[0.06]">
                @foreach ($details as $row)
                    <tr>
                        <td class="px-6 py-3 tnum whitespace-nowrap">{{ $row->transaction_date->format('d M Y') }}</td>
                        <td class="px-6 py-3 text-ink/70">{{ $row->description }}</td>
                        <td class="px-6 py-3">
                            <span class="text-xs font-medium rounded-full px-2.5 py-1
                                {{ $row->direction === 'in' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-700' }}">
                                {{ $row->direction === 'in' ? 'Masuk' : 'Keluar' }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-right tnum font-medium {{ $row->direction === 'in' ? 'text-emerald-700' : 'text-red-700' }}">
                            {{ $row->direction === 'in' ? '+' : '-' }}Rp {{ number_format($row->amount, 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 ajax-pagination">
        {{ $details->links() }}
    </div>
@endif
