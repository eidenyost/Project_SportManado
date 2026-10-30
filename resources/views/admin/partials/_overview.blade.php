<div class="space-y-6 animate-in fade-in duration-300">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="bg-dark-card p-6 rounded-xl border border-slate-800/40">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-[#ff6b00]/10 rounded-xl text-[#ff6b00]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-slate-400 text-xs font-medium">Total Pengguna</h3>
            <p class="font-display font-black text-3xl tracking-tight text-white tabular-nums leading-none mt-1">{{ $totalUsersCount }}</p>
        </div>
        <div class="bg-dark-card p-6 rounded-xl border border-slate-800/40">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-[#ff6b00]/10 rounded-xl text-[#ff6b00]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-slate-400 text-xs font-medium">Total Penyelenggara/EO Aktif</h3>
            <p class="font-display font-black text-3xl tracking-tight text-white tabular-nums leading-none mt-1">{{ $totalOrganizers }}</p>
        </div>
        <div class="bg-dark-card p-6 rounded-xl border border-slate-800/40">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-[#ff6b00]/10 rounded-xl text-[#ff6b00]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                        <polyline points="9 16 11 18 15 14"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-slate-400 text-xs font-medium">Total Event Aktif</h3>
            <p class="font-display font-black text-3xl tracking-tight text-white tabular-nums leading-none mt-1">{{ $totalActiveEvents }}</p>
        </div>
    </div>

    <div>
        <h3 class="text-base font-bold text-white flex items-center gap-2 mb-4">
            <svg class="w-5 h-5 text-[#ff6b00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <line x1="8" y1="6" x2="21" y2="6"/>
                <line x1="8" y1="12" x2="21" y2="12"/>
                <line x1="8" y1="18" x2="21" y2="18"/>
                <line x1="3" y1="6" x2="3.01" y2="6"/>
                <line x1="3" y1="12" x2="3.01" y2="12"/>
                <line x1="3" y1="18" x2="3.01" y2="18"/>
            </svg>
            Semua Event Terdaftar
        </h3>

        @if($allEvents->isEmpty())
            <div class="bg-dark-card rounded-xl p-12 text-center border border-slate-800/40">
                <p class="text-3xl mb-2">📭</p>
                <p class="text-sm text-slate-400">Belum ada event terdaftar.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($allEvents as $event)
                @php
                    $statusLabel = match($event->status) {
                        'active' => 'Aktif',
                        'pending' => 'Pending',
                        'completed' => 'Selesai',
                        'cancelled' => 'Batal',
                        'rejected' => 'Ditolak',
                        default => 'Draft',
                    };
                    $statusColors = match($event->status) {
                        'active' => 'bg-emerald-500/90 text-white',
                        'pending' => 'bg-amber-500/90 text-white',
                        'completed' => 'bg-indigo-500/90 text-white',
                        'cancelled', 'rejected' => 'bg-red-500/90 text-white',
                        default => 'bg-slate-500/90 text-white',
                    };
                    $slotsLeft = $event->max_participants - $event->active_used_tickets_count;
                @endphp
                <div class="bg-dark-card border-2 border-[#ff6b00]/30 rounded-xl overflow-hidden">
                    <div class="relative block aspect-video bg-slate-900 overflow-hidden">
                        @if($event->banner)
                            <img src="{{ asset('storage/' . $event->banner) }}" class="w-full h-full object-cover" alt="{{ $event->title }}">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-slate-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <polyline points="21 15 16 10 5 21"/>
                                </svg>
                            </div>
                        @endif
                        <span class="absolute top-2 right-2 px-2 py-0.5 rounded text-[10px] font-bold {{ $statusColors }}">{{ $statusLabel }}</span>
                    </div>
                    <div class="p-4 space-y-3">
                        <div class="font-bold text-sm text-white line-clamp-2 leading-snug">
                            {{ $event->title }}
                        </div>
                        <div class="flex items-center gap-3 text-xs text-slate-400">
                            <span class="inline-flex shrink-0 whitespace-nowrap items-center gap-1">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                {{ $event->event_date->translatedFormat('d F Y') }}
                            </span>
                            <span class="inline-flex min-w-0 items-center gap-1 truncate">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                                {{ $event->location }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-slate-800/40">
                            <div class="flex items-center gap-2 text-xs">
                                @if($event->ticket_price > 0)
                                    <span class="font-bold text-[#ff6b00]">Rp {{ number_format($event->ticket_price, 0, ',', '.') }}</span>
                                @else
                                    <span class="font-semibold text-emerald-400">Gratis</span>
                                @endif
                                <span class="text-slate-600">•</span>
                                @if($slotsLeft <= 0)
                                    <span class="text-red-400 font-medium">Penuh</span>
                                @else
                                    <span class="text-slate-500">{{ $slotsLeft }} slot</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
