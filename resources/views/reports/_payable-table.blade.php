{{--
    Partial ini dirender penuh saat load awal halaman (di-include reports/payable.blade.php)
    DAN dikirim sebagai response AJAX saat user mengetik di kolom pencarian atau klik
    pagination (lihat ReportController@payable & initAjaxListSearch di layouts/app.blade.php).
--}}
<div class="rounded-2xl border border-ink/10 bg-white shadow-card divide-y divide-ink/[0.06] overflow-hidden">
    @forelse ($data as $po)
        <div x-data="{ open: false }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between gap-4 px-5 py-4 hover:bg-amber-50/40 transition-colors text-left">
                <div class="flex items-center gap-3 min-w-0">
                    <svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-ink/40 transition-transform"
                         :class="open ? 'rotate-90' : ''"
                         fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 6l6 6-6 6"/>
                    </svg>
                    <div class="min-w-0">
                        <p class="font-medium truncate">{{ $po['po_number'] }}</p>
                        <p class="text-xs text-ink/40">{{ $po['supplier'] }} &middot; {{ \Illuminate\Support\Carbon::parse($po['po_date'])->format('d M Y') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 sm:gap-6 shrink-0">
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                        {{ $po['payment_status'] === 'partial' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-700' }}">
                        {{ $po['payment_status'] === 'partial' ? 'Sebagian' : 'Belum Bayar' }}
                    </span>
                    <span class="text-sm font-medium tnum w-32 text-right">Rp {{ number_format($po['remaining_balance'], 0, ',', '.') }}</span>
                </div>
            </button>

            <div x-show="open" x-transition class="px-5 pb-4 pl-12">
                <div class="grid grid-cols-3 gap-3 text-xs mb-3">
                    <div><p class="text-ink/40">Total PO</p><p class="tnum font-medium">Rp {{ number_format($po['total_amount'], 0, ',', '.') }}</p></div>
                    <div><p class="text-ink/40">Sudah Dibayar</p><p class="tnum font-medium">Rp {{ number_format($po['paid_amount'], 0, ',', '.') }}</p></div>
                    <div><p class="text-ink/40">Sisa</p><p class="tnum font-medium">Rp {{ number_format($po['remaining_balance'], 0, ',', '.') }}</p></div>
                </div>
                @if (count($po['payment_history']) === 0)
                    <p class="text-xs text-ink/40 py-1">Belum ada pembayaran.</p>
                @else
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-ink/40 text-left">
                                <th class="py-1.5 font-medium">Tgl Bayar</th>
                                <th class="py-1.5 font-medium">Metode</th>
                                <th class="py-1.5 font-medium text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink/[0.05]">
                            @foreach ($po['payment_history'] as $pmt)
                                <tr>
                                    <td class="py-1.5 tnum">{{ \Illuminate\Support\Carbon::parse($pmt['payment_date'])->format('d M Y') }}</td>
                                    <td class="py-1.5">{{ $pmt['method'] }}</td>
                                    <td class="py-1.5 tnum text-right font-medium">Rp {{ number_format($pmt['amount'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    @empty
        <p class="px-5 py-10 text-center text-ink/40 text-sm">
            @if ($search)
                Tidak ada PO yang cocok dengan pencarian.
            @else
                Tidak ada hutang tertunggak. Semua PO sudah lunas 🎉
            @endif
        </p>
    @endforelse
</div>

<div class="mt-4 ajax-pagination">
    {{ $data->links() }}
</div>
