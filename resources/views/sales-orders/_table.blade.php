{{--
    Partial ini dirender penuh saat load awal halaman (di-include index.blade.php)
    DAN dikirim sebagai response AJAX saat user mengetik di kolom pencarian atau
    klik pagination (lihat SalesOrderController@index & initAjaxListSearch di
    layouts/app.blade.php). data-total dibaca JS untuk update angka ringkasan
    di atas tabel tanpa perlu reload.
--}}
<div data-total="{{ $salesOrders->total() }}">
    <div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-ink/[0.03] text-left text-ink/50">
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">No. SO</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Tanggal</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Customer</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide text-right">Total</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide text-right">Sisa Piutang</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/[0.06]">
                    @forelse ($salesOrders as $so)
                        <tr class="hover:bg-amber-50/40 transition-colors">
                            <td class="px-5 py-3.5 font-medium tnum">{{ $so->so_number }}</td>
                            <td class="px-5 py-3.5 text-ink/60 tnum">{{ $so->so_date->format('d M Y') }}</td>
                            <td class="px-5 py-3.5">{{ $so->customer->name ?? 'Customer umum' }}</td>
                            <td class="px-5 py-3.5 text-right tnum">Rp {{ number_format($so->total_amount, 0, ',', '.') }}</td>
                            <td class="px-5 py-3.5 text-right tnum {{ $so->remaining_balance > 0 ? 'text-red-700 font-medium' : 'text-ink/40' }}">
                                Rp {{ number_format($so->remaining_balance, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3.5">
                                @php
                                    $statusStyle = [
                                        'paid'    => 'bg-emerald-100 text-emerald-700',
                                        'partial' => 'bg-amber-100 text-amber-800',
                                        'unpaid'  => 'bg-red-100 text-red-700',
                                    ][$so->payment_status] ?? 'bg-ink/[0.06] text-ink/60';
                                    $statusLabel = [
                                        'paid'    => 'Lunas',
                                        'partial' => 'Sebagian',
                                        'unpaid'  => 'Belum Bayar',
                                    ][$so->payment_status] ?? $so->payment_status;
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusStyle }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('sales-orders.show', $so) }}" class="text-ink/60 hover:text-ink font-medium transition-colors">
                                    Lihat &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-ink/40">
                                {{ request('search') ? 'Tidak ada transaksi yang cocok dengan pencarian.' : 'Belum ada transaksi penjualan.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 ajax-pagination">
        {{ $salesOrders->links() }}
    </div>
</div>
