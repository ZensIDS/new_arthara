{{--
    Partial ini dirender penuh saat load awal halaman (di-include reports/stock.blade.php)
    DAN dikirim sebagai response AJAX saat user mengetik di kolom pencarian atau klik
    pagination (lihat ReportController@stock & initAjaxListSearch di layouts/app.blade.php).
--}}
<div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-ink/40 uppercase tracking-wide border-b border-ink/[0.06]">
                    <th class="px-6 py-3 font-medium">Produk</th>
                    <th class="px-6 py-3 font-medium">Kategori</th>
                    <th class="px-6 py-3 font-medium text-right">Qty</th>
                    <th class="px-6 py-3 font-medium text-right">Nilai Stok</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink/[0.06]">
                @forelse ($data as $p)
                    <tr x-data="{ open: false }">
                        <td colspan="4" class="p-0">
                            <button type="button" @click="open = !open"
                                    class="w-full flex items-center gap-3 px-6 py-3 hover:bg-amber-50/40 transition-colors text-left"
                                    {{ count($p['batches']) === 0 ? 'disabled' : '' }}>
                                <svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 transition-transform {{ count($p['batches']) === 0 ? 'text-ink/15' : 'text-ink/40' }}"
                                     :class="open ? 'rotate-90' : ''"
                                     fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 6l6 6-6 6"/>
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium truncate">{{ $p['name'] }}</p>
                                    <p class="text-xs text-ink/40">
                                        {{ $p['category'] ?? 'Tanpa kategori' }}
                                        @if (count($p['batches']) > 0)
                                            &middot; {{ count($p['batches']) }} batch
                                        @endif
                                    </p>
                                </div>
                                <span class="tnum font-semibold rounded-full px-2.5 py-1 text-xs shrink-0
                                    {{ $p['total_qty'] == 0 ? 'bg-red-100 text-red-700' : ($p['total_qty'] <= 5 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700') }}">{{ $p['total_qty'] }}</span>
                                <span class="tnum text-sm w-32 text-right shrink-0">Rp {{ number_format($p['stock_value'], 0, ',', '.') }}</span>
                            </button>

                            <div x-show="open" x-transition class="px-6 pb-4 pl-16">
                                @if (count($p['batches']) === 0)
                                    <p class="text-xs text-ink/40 py-1">Tidak ada batch stok tersisa untuk produk ini.</p>
                                @else
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="text-ink/40 text-left">
                                                <th class="py-1.5 font-medium">Tgl Batch (masuk)</th>
                                                <th class="py-1.5 font-medium text-right">Harga Beli</th>
                                                <th class="py-1.5 font-medium text-right">Qty Masuk</th>
                                                <th class="py-1.5 font-medium text-right">Qty Saat Ini</th>
                                                <th class="py-1.5 font-medium text-right">Nilai Batch</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-ink/[0.05]">
                                            @foreach ($p['batches'] as $b)
                                                <tr>
                                                    <td class="py-1.5 tnum">{{ \Illuminate\Support\Carbon::parse($b['batch_date'])->format('d M Y') }}</td>
                                                    <td class="py-1.5 tnum text-right">Rp {{ number_format($b['buy_price'], 0, ',', '.') }}</td>
                                                    <td class="py-1.5 tnum text-right">{{ $b['qty_in'] }}</td>
                                                    <td class="py-1.5 tnum text-right font-medium">{{ $b['qty_remaining'] }}</td>
                                                    <td class="py-1.5 tnum text-right font-medium">Rp {{ number_format($b['qty_remaining'] * $b['buy_price'], 0, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="border-t border-ink/[0.08] font-semibold">
                                                <td class="py-1.5" colspan="3">Total</td>
                                                <td class="py-1.5 tnum text-right">{{ $p['total_qty'] }}</td>
                                                <td class="py-1.5 tnum text-right">Rp {{ number_format($p['stock_value'], 0, ',', '.') }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-10 text-center text-ink/40">{{ $search ? 'Tidak ada produk yang cocok dengan pencarian.' : 'Belum ada produk.' }}</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="border-t border-ink/10 bg-amber-50/60 font-semibold text-sm">
                    <td class="px-6 py-3" colspan="2">Total (semua produk)</td>
                    <td class="px-6 py-3 text-right tnum">{{ number_format($kpis['total_qty'], 0, ',', '.') }}</td>
                    <td class="px-6 py-3 text-right tnum">Rp {{ number_format($kpis['total_value'], 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="px-6 py-4 ajax-pagination">
        {{ $data->links() }}
    </div>
</div>
