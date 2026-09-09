{{--
    Partial ini dirender penuh saat load awal halaman (di-include index.blade.php)
    DAN dikirim sebagai response AJAX saat user mengetik di kolom pencarian atau
    klik pagination (lihat SupplierController@index & initAjaxListSearch di
    layouts/app.blade.php). data-total dibaca JS untuk update angka ringkasan
    di atas tabel tanpa perlu reload.

    Tombol Edit/Hapus di sini pakai directive Alpine (@click) yang merujuk ke
    scope "supplierPage" milik elemen x-data di suppliers/index.blade.php. Setelah
    partial ini di-swap via innerHTML oleh JS, Alpine.initTree() dipanggil ulang
    supaya directive di baris-baris baru ini ikut aktif.
--}}
<div data-total="{{ $suppliers->total() }}">
    <div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-ink/[0.03] text-left text-ink/50">
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Nama</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Kontak</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Telepon</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Email</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Tgl Dibuat</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/[0.06]">
                    @forelse ($suppliers as $supplier)
                        <tr class="hover:bg-amber-50/40 transition-colors">
                            <td class="px-5 py-3.5 font-medium">{{ $supplier->name }}</td>
                            <td class="px-5 py-3.5 text-ink/60">{{ $supplier->contact_person ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-ink/60">{{ $supplier->phone ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-ink/60">{{ $supplier->email ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-ink/60 tnum">{{ $supplier->created_at->format('d M Y') }}</td>
                            <td class="px-5 py-3.5 text-right">
                                @if (auth()->user()->isSuperadmin())
                                    <button
                                        @click="openEdit({{ Illuminate\Support\Js::from($supplier) }})"
                                        class="text-ink/60 hover:text-ink font-medium mr-3 transition-colors"
                                    >Edit</button>
                                    <button
                                        @click="remove({{ $supplier->id }})"
                                        class="text-red-600/80 hover:text-red-700 font-medium transition-colors"
                                    >Hapus</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-ink/40">
                                {{ request('search') ? 'Tidak ada supplier yang cocok dengan pencarian.' : 'Belum ada supplier.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 ajax-pagination">
        {{ $suppliers->links() }}
    </div>
</div>
