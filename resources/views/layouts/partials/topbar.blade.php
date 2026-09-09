<header class="h-16 border-b border-ink/10 bg-white/80 backdrop-blur-sm flex items-center justify-between px-4 sm:px-6 lg:px-10 shrink-0 sticky top-0 z-20">
    <div class="flex items-center gap-4">
        {{-- Toggle sidebar — aktif di semua ukuran layar (mobile, tablet, desktop) --}}
        <button
            @click="sidebarOpen = !sidebarOpen"
            class="-ml-1 p-2 rounded-lg text-ink/70 hover:text-ink hover:bg-ink/5"
            :aria-label="sidebarOpen ? 'Tutup menu' : 'Buka menu'"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
            </svg>
        </button>

        <div>
            <h1 class="font-display font-semibold text-lg tracking-tight leading-none">
                @yield('page-title', 'Dashboard')
            </h1>
            <p class="text-[11px] text-ink/40 mt-0.5">Kelola dengan mudah &amp; akurat</p>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <div class="hidden sm:flex items-center gap-2 rounded-full border border-ink/10 bg-ink/[0.03] px-3.5 py-1.5 text-xs font-medium text-ink/60 tnum">
            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
            {{ now()->translatedFormat('l, d F Y') }}
        </div>
    </div>
</header>
