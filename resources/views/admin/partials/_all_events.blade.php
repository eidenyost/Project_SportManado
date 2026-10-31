<div class="space-y-4 animate-in fade-in duration-300">
    @php $statusCountsArr = $statusCounts instanceof \Illuminate\Support\Collection ? $statusCounts->toArray() : (is_array($statusCounts) ? $statusCounts : []); @endphp

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4">
            <span class="text-[11px] text-slate-500 font-medium">Total Event</span>
            <p class="font-display font-black text-2xl tracking-tight text-white tabular-nums mt-1">{{ array_sum($statusCountsArr) }}</p>
        </div>
        <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4">
            <span class="text-[11px] text-slate-500 font-medium">Aktif</span>
            <p class="font-display font-black text-2xl tracking-tight text-emerald-400 tabular-nums mt-1">{{ $statusCountsArr['active'] ?? 0 }}</p>
        </div>
        <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4">
            <span class="text-[11px] text-slate-500 font-medium">Pending</span>
            <p class="font-display font-black text-2xl tracking-tight text-amber-400 tabular-nums mt-1">{{ $statusCountsArr['pending'] ?? 0 }}</p>
        </div>
        <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4">
            <span class="text-[11px] text-slate-500 font-medium">Selesai</span>
            <p class="font-display font-black text-2xl tracking-tight text-[#ff6b00] tabular-nums mt-1">{{ $statusCountsArr['completed'] ?? 0 }}</p>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <form method="GET" action="{{ route('admin.dashboard') }}" class="flex-1 w-full sm:max-w-xs">
            <input type="hidden" name="tab" value="all_events">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari event..."
                       class="w-full pl-9 pr-4 py-2 bg-dark-card border border-slate-800/60 rounded-xl text-xs text-white placeholder-slate-500 outline-none focus:border-[#ff6b00] transition-colors">
            </div>
        </form>
        <div class="flex flex-wrap gap-1.5">
            @php $currentStatus = request('status'); $allCount = array_sum($statusCountsArr); @endphp
            <a href="{{ route('admin.dashboard', array_merge(request()->except('status', 'page'), ['tab' => 'all_events', 'status' => null])) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition
                      {{ !$currentStatus ? 'bg-[#ff6b00] text-white' : 'bg-dark-card text-slate-400 border border-slate-800/60 hover:text-white hover:border-slate-600' }}">
                Semua ({{ $allCount }})
            </a>
            @foreach(['active' => 'Aktif', 'pending' => 'Pending', 'completed' => 'Selesai'] as $val => $label)
                @if(isset($statusCountsArr[$val]) && $statusCountsArr[$val] > 0)
                <a href="{{ route('admin.dashboard', array_merge(request()->except('status', 'page'), ['tab' => 'all_events', 'status' => $val])) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition
                          {{ $currentStatus === $val ? 'bg-[#ff6b00] text-white' : 'bg-dark-card text-slate-400 border border-slate-800/60 hover:text-white hover:border-slate-600' }}">
                    {{ $label }} ({{ $statusCountsArr[$val] }})
                </a>
                @endif
            @endforeach
        </div>
    </div>

    @if($allEvents->isEmpty())
    <div class="bg-dark-card rounded-xl p-12 text-center border border-slate-800/40">
        <svg class="w-12 h-12 text-slate-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
            <line x1="16" y1="2" x2="16" y2="6"/>
            <line x1="8" y1="2" x2="8" y2="6"/>
            <line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        <p class="text-sm text-slate-400">Tidak ada event ditemukan.</p>
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($allEvents as $event)
        @php
            $statusLabel = match($event->status) {
                'active' => 'Aktif',
                'pending' => 'Pending',
                'completed' => 'Selesai',
                default => 'Draft',
            };
            $statusColors = match($event->status) {
                'active' => 'bg-emerald-500/90 text-white',
                'pending' => 'bg-amber-500/90 text-white',
                'completed' => 'bg-indigo-500/90 text-white',
                default => 'bg-slate-500/90 text-white',
            };
            $slotsLeft = $event->max_participants - $event->tickets->whereIn('status', ['pending', 'active', 'used'])->count();
        @endphp
        <div class="bg-dark-card border-2 border-[#ff6b00]/30 rounded-xl overflow-hidden flex flex-col">
            <div class="relative aspect-video bg-slate-900 overflow-hidden">
                @if($event->banner)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($event->banner) }}" class="w-full h-full object-cover" alt="{{ $event->title }}">
                @else
                    <div class="w-full h-full flex items-center justify-center text-slate-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                    </div>
                @endif
                <span class="absolute top-2 right-2 px-2 py-0.5 rounded text-[10px] font-bold {{ $statusColors }}">{{ $statusLabel }}</span>
            </div>
            <div class="p-4 space-y-3 flex-1 flex flex-col">
                <div class="font-bold text-sm text-white line-clamp-2 leading-snug">{{ $event->title }}</div>

                <div class="space-y-1.5 text-xs text-slate-400 flex-1">
                    <div class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <span class="truncate">{{ $event->location }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <span>{{ $event->event_date->translatedFormat('d F Y') }}</span>
                    </div>
                    <div class="flex items-center gap-2 pt-1">
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

                <div class="flex items-center justify-between pt-3 border-t border-slate-800/40">
                    <div class="flex items-center gap-1.5 text-[11px] text-slate-500 truncate min-w-0">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                        </svg>
                        <span class="truncate">{{ $event->organizer->organization_name ?? 'Penyelenggara/EO' }}</span>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        @if($event->status === 'active')
                        <form action="{{ route('admin.events.complete', $event) }}" method="POST">
                            @csrf
                            <button type="submit" class="p-1.5 hover:bg-[#ff6b00]/10 rounded-lg text-[#ff6b00] hover:text-[#e05e00] transition-colors" title="Tandai Selesai">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                            </button>
                        </form>
                        @endif
                        <form action="{{ route('admin.events.delete', $event) }}" method="POST" onsubmit="return confirm('Hapus event ' + @json($event->title) + ' secara permanen? Seluruh tiket peserta akan terhapus.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1.5 hover:bg-red-500/10 rounded-lg text-red-400 hover:text-red-300 transition-colors" title="Hapus Event">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @if(method_exists($allEvents, 'links'))
    <div class="px-4 py-2.5 border-t border-slate-800/60 bg-dark-card rounded-xl">
        {{ $allEvents->links() }}
    </div>
    @endif
</div>
