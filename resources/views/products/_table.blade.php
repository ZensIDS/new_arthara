{{--
    Partial ini dirender penuh saat load awal halaman (di-include index.blade.php)
    DAN dikirim sebagai response AJAX saat user mengetik di kolom pencarian atau
    klik pagination (lihat ProductController@index & initAjaxListSearch di
    layouts/app.blade.php). data-total dibaca JS untuk update angka ringkasan
    di atas tabel tanpa perlu reload.

    Tombol Edit/Hapus di sini pakai directive Alpine (@click) yang merujuk ke
    scope "productPage" milik elemen x-data di products/index.blade.php. Setelah
    partial ini di-swap via innerHTML oleh JS, Alpine.initTree() dipanggil ulang
    supaya directive di baris-baris baru ini ikut aktif.
--}}
<div data-total="{{ $products->total() }}">
    <div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-ink/[0.03] text-left text-ink/50">
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Nama Produk</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Kategori</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Satuan</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Stok</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Tgl Dibuat</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/[0.06]">
                    @forelse ($products as $product)
                        <tr class="hover:bg-amber-50/40 transition-colors">
                            <td class="px-5 py-3.5 font-medium">{{ $product->name }}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center rounded-full bg-ink/[0.05] px-2.5 py-1 text-xs font-medium text-ink/70">
                                    {{ $product->category->name }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-ink/60">{{ $product->unit ?? '—' }}</td>
                            <td class="px-5 py-3.5 tnum">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                                    {{ $product->qty_on_hand == 0
                                        ? 'bg-red-100 text-red-700'
                                        : ($product->qty_on_hand <= 5 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700') }}">
                                    {{ $product->qty_on_hand }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium {{ $product->is_active ? 'text-emerald-700' : 'text-ink/35' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $product->is_active ? 'bg-emerald-500' : 'bg-ink/25' }}"></span>
                                    {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-ink/60 tnum">{{ $product->created_at->format('d M Y') }}</td>
                            <td class="px-5 py-3.5 text-right">
                                @if (auth()->user()->isSuperadmin())
                                    <button
                                        @click="openEdit({{ Illuminate\Support\Js::from($product) }})"
                                        class="text-ink/60 hover:text-ink font-medium mr-3 transition-colors"
                                    >Edit</button>
                                    <button
                                        @click="remove({{ $product->id }})"
                                        class="text-red-600/80 hover:text-red-700 font-medium transition-colors"
                                    >Hapus</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-ink/40">
                                {{ request('search') ? 'Tidak ada produk yang cocok dengan pencarian.' : 'Belum ada produk.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 ajax-pagination">
        {{ $products->links() }}
    </div>
</div>
