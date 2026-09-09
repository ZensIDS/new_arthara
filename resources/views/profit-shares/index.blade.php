@extends('layouts.app')

@section('page-title', 'Bagi Hasil')

@section('content')
<div
    x-data="profitSharePage()"
    x-cloak
>
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <p class="text-2xl font-display font-semibold tracking-tight">{{ $profitShares->total() }}</p>
            <p class="text-sm text-ink/50">orang bagi hasil</p>
        </div>

        @if (auth()->user()->isSuperadmin())
            <button
                @click="openCreate()"
                class="inline-flex items-center gap-2 text-sm font-semibold bg-gradient-to-r from-amber-400 to-amber-500 text-ink px-5 py-2.5 rounded-xl shadow-glow hover:brightness-105 active:scale-[0.98] transition-all"
            >
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                Tambah Orang
            </button>
        @endif
    </div>

    <div x-show="flash" x-cloak x-transition
         class="mb-4 rounded-xl text-sm px-4 py-3 shadow-card"
         :class="flashType === 'error' ? 'bg-red-50 text-red-900 border border-red-600/15' : 'bg-emerald-50 text-emerald-900 border border-emerald-600/15'">
        <span x-text="flash"></span>
    </div>

    {{-- Ringkasan total persentase aktif — supaya kelihatan kalau sudah pas 100% atau belum --}}
    <div class="mb-6 rounded-2xl border px-5 py-4 flex items-center justify-between
                {{ $totalActivePercentage == 100 ? 'border-emerald-600/15 bg-emerald-50' : 'border-amber-600/15 bg-amber-50' }}">
        <div>
            <p class="text-sm font-medium {{ $totalActivePercentage == 100 ? 'text-emerald-900' : 'text-amber-900' }}">
                Total persentase bagi hasil aktif
            </p>
            <p class="text-xs {{ $totalActivePercentage == 100 ? 'text-emerald-700/70' : 'text-amber-700/70' }} mt-0.5">
                @if ($totalActivePercentage == 100)
                    Sudah pas 100% dari Laba Bersih.
                @elseif ($totalActivePercentage < 100)
                    Masih tersisa {{ number_format(100 - $totalActivePercentage, 2, ',', '.') }}% yang belum dibagi.
                @else
                    Melebihi 100% &mdash; mohon disesuaikan.
                @endif
            </p>
        </div>
        <p class="text-2xl font-display font-semibold tnum {{ $totalActivePercentage == 100 ? 'text-emerald-700' : 'text-amber-700' }}">
            {{ number_format($totalActivePercentage, 2, ',', '.') }}%
        </p>
    </div>

    <div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-ink/[0.03] text-left text-ink/50">
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Nama</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Persentase</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/[0.06]">
                    @forelse ($profitShares as $profitShare)
                        <tr class="hover:bg-amber-50/40 transition-colors">
                            <td class="px-5 py-3.5 font-medium">{{ $profitShare->name }}</td>
                            <td class="px-5 py-3.5 tnum">{{ number_format($profitShare->percentage, 2, ',', '.') }}%</td>
                            <td class="px-5 py-3.5">
                                @if ($profitShare->is_active)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 text-emerald-700 px-2.5 py-1 text-xs font-semibold">Aktif</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-ink/[0.05] text-ink/50 px-2.5 py-1 text-xs font-semibold">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                @if (auth()->user()->isSuperadmin())
                                    <button
                                        @click="openEdit({{ Illuminate\Support\Js::from($profitShare) }})"
                                        class="text-ink/60 hover:text-ink font-medium mr-3 transition-colors"
                                    >Edit</button>
                                    <button
                                        @click="remove({{ $profitShare->id }})"
                                        class="text-red-600/80 hover:text-red-700 font-medium transition-colors"
                                    >Hapus</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-ink/40">Belum ada data bagi hasil.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $profitShares->links() }}
    </div>

    {{-- Modal Create/Edit --}}
    <div
        x-show="modalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center px-4"
    >
        <div x-show="modalOpen" x-transition.opacity @click="modalOpen = false" class="absolute inset-0 bg-ink/50 backdrop-blur-sm"></div>

        <div
            x-show="modalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="relative bg-white w-full max-w-md rounded-2xl shadow-panel"
        >
            <div class="h-1.5 bg-gradient-to-r from-amber-400 to-amber-500 rounded-t-2xl"></div>

            <div class="p-6">
                <div class="flex items-center gap-3 mb-5">
                    <span class="h-10 w-10 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 0 0-3-3.87M9 20H4v-2a4 4 0 0 1 3-3.87m6-8.13a4 4 0 1 1 0 8 4 4 0 0 1 0-8Zm-6 1a4 4 0 1 1 0 8"/></svg>
                    </span>
                    <h2 class="font-display font-semibold text-lg" x-text="editing ? 'Edit Bagi Hasil' : 'Tambah Orang Bagi Hasil'"></h2>
                </div>

                <form @submit.prevent="submit()">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Nama</label>
                        <input type="text" x-model="form.name" placeholder="mis. Budi, Ani, dll"
                               class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                        <p class="text-xs text-red-600 mt-1" x-text="errors.name?.[0]"></p>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium mb-1.5">Persentase (%)</label>
                        <input type="number" step="0.01" min="0.01" max="100" x-model="form.percentage" placeholder="mis. 40"
                               class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                        <p class="text-xs text-ink/40 mt-1">Persentase bagian dari Laba Bersih.</p>
                        <p class="text-xs text-red-600 mt-1" x-text="errors.percentage?.[0]"></p>
                    </div>

                    <div class="mt-4 flex items-center gap-2.5">
                        <input type="checkbox" id="is_active" x-model="form.is_active"
                               class="h-4 w-4 rounded border-ink/20 text-amber-500 focus:ring-amber-400">
                        <label for="is_active" class="text-sm font-medium">Aktif (ikut dihitung di bagi hasil)</label>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="modalOpen = false"
                                class="text-sm font-medium px-4 py-2.5 rounded-xl border border-ink/12 hover:bg-ink/[0.03] transition-colors">Batal</button>
                        <button type="submit" :disabled="saving"
                                class="text-sm font-semibold px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-amber-500 text-ink shadow-glow hover:brightness-105 disabled:opacity-50 transition-all">
                            <span x-text="saving ? 'Menyimpan...' : 'Simpan'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function profitSharePage() {
        return {
            modalOpen: false,
            editing: null,
            saving: false,
            errors: {},
            flash: null,
            flashType: 'success',
            form: { name: '', percentage: '', is_active: true },

            openCreate() {
                this.editing = null;
                this.form = { name: '', percentage: '', is_active: true };
                this.errors = {};
                this.modalOpen = true;
            },

            openEdit(profitShare) {
                this.editing = profitShare;
                this.form = {
                    name: profitShare.name,
                    percentage: profitShare.percentage,
                    is_active: !!profitShare.is_active,
                };
                this.errors = {};
                this.modalOpen = true;
            },

            async submit() {
                this.saving = true;
                this.errors = {};

                const url = this.editing
                    ? `{{ url('profit-shares') }}/${this.editing.id}`
                    : `{{ route('profit-shares.store') }}`;
                const method = this.editing ? 'PUT' : 'POST';

                const { ok, status, data } = await window.ajaxSend(url, method, this.form);
                this.saving = false;

                if (ok) {
                    this.modalOpen = false;
                    this.flashType = 'success';
                    this.flash = data.message;
                    setTimeout(() => window.location.reload(), 500);
                    return;
                }

                if (status === 422) {
                    this.errors = data.errors || {};
                    return;
                }

                this.flashType = 'error';
                this.flash = data.message || 'Terjadi kesalahan.';
            },

            async remove(id) {
                if (!confirm('Hapus orang ini dari bagi hasil?')) return;

                const { ok, data } = await window.ajaxSend(`{{ url('profit-shares') }}/${id}`, 'DELETE');

                this.flashType = ok ? 'success' : 'error';
                this.flash = data.message || (ok ? 'Berhasil dihapus.' : 'Gagal menghapus.');

                if (ok) setTimeout(() => window.location.reload(), 500);
            },
        };
    }
</script>
@endpush
