{{--
    Partial ini dirender penuh saat load awal halaman (di-include reports/purchase-return.blade.php)
    DAN dikirim sebagai response AJAX saat user mengetik di kolom pencarian atau klik
    pagination (lihat ReportController@purchaseReturn & initAjaxListSearch di layouts/app.blade.php).
--}}
<div class="rounded-2xl border border-ink/10 bg-white shadow-card divide-y divide-ink/[0.06] overflow-hidden">
    @forelse ($data as $r)
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
                        <p class="font-medium truncate">{{ $r['return_number'] }}</p>
                        <p class="text-xs text-ink/40">
                            {{ $r['supplier'] }} &middot; PO {{ $r['po_number'] }} &middot;
                            {{ \Illuminate\Support\Carbon::parse($r['return_date'])->format('d M Y') }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-4 sm:gap-6 shrink-0">
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold bg-amber-100 text-amber-800">
                        {{ count($r['items']) }} item
                    </span>
                    <span class="text-sm font-medium tnum w-32 text-right text-amber-700">Rp {{ number_format($r['total_amount'], 0, ',', '.') }}</span>
                </div>
            </button>

            <div x-show="open" x-transition class="px-5 pb-4 pl-12">
                @if ($r['note'])
                    <p class="text-xs mb-3"><span class="text-ink/40">Catatan:</span> {{ $r['note'] }}</p>
                @endif
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-ink/40 text-left">
                            <th class="py-1.5 font-medium">Produk</th>
                            <th class="py-1.5 font-medium text-right">Qty</th>
                            <th class="py-1.5 font-medium text-right">Harga Beli</th>
                            <th class="py-1.5 font-medium text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink/[0.05]">
                        @foreach ($r['items'] as $item)
                            <tr>
                                <td class="py-1.5">{{ $item['product'] }}</td>
                                <td class="py-1.5 tnum text-right">{{ $item['qty'] }}</td>
                                <td class="py-1.5 tnum text-right">Rp {{ number_format($item['buy_price'], 0, ',', '.') }}</td>
                                <td class="py-1.5 tnum text-right font-medium">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <p class="px-5 py-10 text-center text-ink/40 text-sm">
            @if ($search)
                Tidak ada retur yang cocok dengan pencarian.
            @else
                Tidak ada retur pembelian pada periode ini.
            @endif
        </p>
    @endforelse
</div>

<div class="mt-4 ajax-pagination">
    {{ $data->links() }}
</div>
