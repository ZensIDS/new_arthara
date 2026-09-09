{{--
    Partial ini dirender penuh saat load awal halaman (di-include index.blade.php)
    DAN dikirim sebagai response AJAX saat user mengetik di kolom pencarian atau
    klik pagination (lihat ExpenseController@index & initAjaxListSearch di
    layouts/app.blade.php). data-total dibaca JS untuk update angka ringkasan
    di atas tabel tanpa perlu reload.

    Tombol Edit/Hapus di sini pakai directive Alpine (@click) yang merujuk ke
    scope "expensePage" milik elemen x-data di expenses/index.blade.php. Setelah
    partial ini di-swap via innerHTML oleh JS, Alpine.initTree() dipanggil ulang
    supaya directive di baris-baris baru ini ikut aktif.
--}}
<div data-total="{{ $expenses->total() }}">
    <div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-ink/[0.03] text-left text-ink/50">
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Tanggal</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Kategori</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Keterangan</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Sumber</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide text-right">Jumlah</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/[0.06]">
                    @forelse ($expenses as $expense)
                        <tr class="hover:bg-amber-50/40 transition-colors">
                            <td class="px-5 py-3.5 tnum text-ink/70">{{ $expense->expense_date->format('d M Y') }}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center rounded-full bg-ink/[0.05] px-2.5 py-1 text-xs font-medium text-ink/70">
                                    {{ $expense->category->name }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-ink/50">{{ $expense->description ?? '—' }}</td>
                            <td class="px-5 py-3.5">
                                @if ($expense->purchase_order_id)
                                    <a href="{{ route('purchase-orders.show', $expense->purchase_order_id) }}"
                                       class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 hover:bg-amber-100 transition-colors"
                                       title="Lihat detail PO">
                                        PO {{ $expense->purchaseOrder->po_number ?? '#'.$expense->purchase_order_id }}
                                        <svg viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M9 7h8v8"/></svg>
                                    </a>
                                @elseif ($expense->sales_order_id)
                                    <a href="{{ route('sales-orders.show', $expense->sales_order_id) }}"
                                       class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 hover:bg-amber-100 transition-colors"
                                       title="Lihat detail SO">
                                        SO {{ $expense->salesOrder->so_number ?? '#'.$expense->sales_order_id }}
                                        <svg viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M9 7h8v8"/></svg>
                                    </a>
                                @else
                                    <span class="text-xs text-ink/35">Manual</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right tnum font-semibold text-red-700/90">Rp{{ number_format($expense->amount, 0, ',', '.') }}</td>
                            <td class="px-5 py-3.5 text-right">
                                @if ($expense->purchase_order_id)
                                    <span class="text-xs text-ink/35" title="Diatur dari PO — edit/hapus lewat halaman PO terkait">Dikelola dari PO</span>
                                @elseif ($expense->sales_order_id)
                                    <span class="text-xs text-ink/35" title="Diatur dari SO — edit/hapus lewat halaman SO terkait">Dikelola dari SO</span>
                                @elseif (auth()->user()->isSuperadmin())
                                    <button
                                        @click="openEdit({{ Illuminate\Support\Js::from($expense) }})"
                                        class="text-ink/60 hover:text-ink font-medium mr-3 transition-colors"
                                    >Edit</button>
                                    <button
                                        @click="remove({{ $expense->id }})"
                                        class="text-red-600/80 hover:text-red-700 font-medium transition-colors"
                                    >Hapus</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-ink/40">
                                {{ request('search') ? 'Tidak ada catatan biaya yang cocok dengan pencarian.' : 'Belum ada catatan biaya.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 ajax-pagination">
        {{ $expenses->links() }}
    </div>
</div>
