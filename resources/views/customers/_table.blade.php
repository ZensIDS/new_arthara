{{--
    Partial ini dirender penuh saat load awal halaman (di-include index.blade.php)
    DAN dikirim sebagai response AJAX saat user mengetik di kolom pencarian atau
    klik pagination (lihat CustomerController@index & initAjaxListSearch di
    layouts/app.blade.php). data-total dibaca JS untuk update angka ringkasan
    di atas tabel tanpa perlu reload.

    Tombol Edit/Hapus di sini pakai directive Alpine (@click) yang merujuk ke
    scope "customerPage" milik elemen x-data di customers/index.blade.php. Setelah
    partial ini di-swap via innerHTML oleh JS, Alpine.initTree() dipanggil ulang
    supaya directive di baris-baris baru ini ikut aktif.
--}}
<div data-total="{{ $customers->total() }}">
    <div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-ink/[0.03] text-left text-ink/50">
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Nama</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Telepon</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Email</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Tgl Dibuat</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/[0.06]">
                    @forelse ($customers as $customer)
                        <tr class="hover:bg-amber-50/40 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="h-8 w-8 rounded-full bg-ink text-white flex items-center justify-center text-xs font-semibold shrink-0">
                                        {{ strtoupper(substr($customer->name, 0, 1)) }}
                                    </span>
                                    <span class="font-medium">{{ $customer->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-ink/60">{{ $customer->phone ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-ink/60">{{ $customer->email ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-ink/60 tnum">{{ $customer->created_at->format('d M Y') }}</td>
                            <td class="px-5 py-3.5 text-right">
                                @if (auth()->user()->isSuperadmin())
                                    <button
                                        @click="openEdit({{ Illuminate\Support\Js::from($customer) }})"
                                        class="text-ink/60 hover:text-ink font-medium mr-3 transition-colors"
                                    >Edit</button>
                                    <button
                                        @click="remove({{ $customer->id }})"
                                        class="text-red-600/80 hover:text-red-700 font-medium transition-colors"
                                    >Hapus</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-ink/40">
                                {{ request('search') ? 'Tidak ada customer yang cocok dengan pencarian.' : 'Belum ada customer.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 ajax-pagination">
        {{ $customers->links() }}
    </div>
</div>
