{{--
    Partial ini dirender penuh saat load awal halaman (di-include index.blade.php)
    DAN dikirim sebagai response AJAX saat user mengetik di kolom pencarian atau
    klik pagination (lihat IncomeController@index & initAjaxListSearch di
    layouts/app.blade.php). data-total dibaca JS untuk update angka ringkasan
    di atas tabel tanpa perlu reload.

    Tombol Edit/Hapus di sini pakai directive Alpine (@click) yang merujuk ke
    scope "incomePage" milik elemen x-data di incomes/index.blade.php. Setelah
    partial ini di-swap via innerHTML oleh JS, Alpine.initTree() dipanggil ulang
    supaya directive di baris-baris baru ini ikut aktif.
--}}
<div data-total="{{ $incomes->total() }}">
    <div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-ink/[0.03] text-left text-ink/50">
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Tanggal</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Kategori</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Keterangan</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide text-right">Jumlah</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/[0.06]">
                    @forelse ($incomes as $income)
                        <tr class="hover:bg-amber-50/40 transition-colors">
                            <td class="px-5 py-3.5 tnum text-ink/70">{{ $income->income_date->format('d M Y') }}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center rounded-full bg-ink/[0.05] px-2.5 py-1 text-xs font-medium text-ink/70">
                                    {{ $income->category->name }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-ink/50">{{ $income->description ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-right tnum font-semibold text-emerald-700/90">+Rp{{ number_format($income->amount, 0, ',', '.') }}</td>
                            <td class="px-5 py-3.5 text-right">
                                @if (auth()->user()->isSuperadmin())
                                    <button
                                        @click="openEdit({{ Illuminate\Support\Js::from($income) }})"
                                        class="text-ink/60 hover:text-ink font-medium mr-3 transition-colors"
                                    >Edit</button>
                                    <button
                                        @click="remove({{ $income->id }})"
                                        class="text-red-600/80 hover:text-red-700 font-medium transition-colors"
                                    >Hapus</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-ink/40">
                                {{ request('search') ? 'Tidak ada catatan pemasukan yang cocok dengan pencarian.' : 'Belum ada catatan pemasukan.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 ajax-pagination">
        {{ $incomes->links() }}
    </div>
</div>
