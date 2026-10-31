<x-app-layout>
    <span hidden data-refresh-on-return></span>
    <!-- ===================== HERO SECTION ===================== -->
    <section class="relative min-h-[400px] flex items-center pt-20 pb-24 overflow-hidden">
        <!-- Background Image -->
        <div class="absolute inset-0 z-0 pointer-events-none">
            <img class="w-full h-full object-cover opacity-15" alt="Manado City" src="{{ asset('images/manado-city.jpg') }}"/>
            <div class="absolute inset-0 bg-gradient-to-t from-dark-bg to-dark-bg/30"></div>
        </div>
        
        <div class="relative z-10 w-full px-4 md:px-8 max-w-7xl mx-auto text-white">
            <div class="max-w-4xl space-y-5">
                <h1 class="text-[36px] leading-[1.1] md:text-6xl font-black font-display tracking-tight text-white">
                    Selamat Datang, <br>
                    <span class="text-accent font-display">{{ Auth::user()->name }}!</span>
                </h1>
                
                <p class="max-w-2xl text-sm text-slate-400 leading-relaxed md:text-base">
                    Jelajahi event olahraga terbaru dan kelola semua tiket Anda dengan mudah.
                </p>
                
                <div class="flex flex-wrap gap-3 pt-2">
                    <a href="{{ route('events.index') }}" class="px-5 py-2.5 bg-accent hover:bg-accent-hover text-white font-bold rounded-lg transition-all duration-200 text-sm">
                        Temukan Event
                    </a>
                    <a href="{{ route('tickets.index') }}" class="px-5 py-2.5 bg-slate-900 border border-slate-800 hover:border-slate-600 text-white font-semibold rounded-lg transition-all duration-200 text-sm">
                        Lihat Tiket
                    </a>
                </div>
            </div>
        </div>
    </section>
    <!-- ===================== MY TICKETS SUMMARY ===================== -->
    @if($stats['events_joined'] > 0)
    <section class="py-12 bg-dark-bg border-b border-slate-900/60">
        <div class="px-4 md:px-8 max-w-7xl mx-auto">
            <div class="flex items-end justify-between mb-6">
                <h2 class="text-xl md:text-2xl font-bold font-display text-white tracking-tight">Aktivitas Saya</h2>
                <a href="{{ route('tickets.index') }}" class="text-sm text-slate-400 hover:text-accent transition-colors">
                    Semua Tiket →
                </a>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4">
                    <p class="text-[11px] text-slate-500 font-medium">Total Event Diikuti</p>
                    <p class="text-2xl font-black text-white tabular-nums mt-1">{{ $stats['events_joined'] }}</p>
                </div>
                <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4">
                    <p class="text-[11px] text-slate-500 font-medium">Tiket Aktif</p>
                    <p class="text-2xl font-black text-emerald-400 tabular-nums mt-1">{{ $stats['active_tickets'] }}</p>
                </div>
                <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4">
                    <p class="text-[11px] text-slate-500 font-medium">Menunggu Bayar</p>
                    <p class="text-2xl font-black text-amber-400 tabular-nums mt-1">{{ $stats['pending_payment'] }}</p>
                </div>
                <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4">
                    <p class="text-[11px] text-slate-500 font-medium">Akan Datang</p>
                    <p class="text-2xl font-black text-accent tabular-nums mt-1">{{ $upcomingEvents->count() }}</p>
                </div>
            </div>

            @if($upcomingEvents->isNotEmpty())
            <div>
                <p class="text-xs text-slate-500 mb-3 font-semibold uppercase tracking-wider">Event Mendatang</p>
                <div class="space-y-2">
                    @foreach($upcomingEvents as $ticket)
                    <a href="{{ route('tickets.show', $ticket) }}"
                       class="flex items-center justify-between bg-dark-card border border-slate-800/40 rounded-xl px-4 py-3 hover:border-accent/30 transition-colors group">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-white group-hover:text-accent transition-colors truncate">{{ $ticket->event->title }}</p>
                            <p class="text-xs text-slate-500">{{ $ticket->event->event_date->translatedFormat('d M Y, H:i') }} WITA</p>
                        </div>
                        <span class="text-xs text-slate-400 ml-3 shrink-0">{{ ucfirst($ticket->status) }}</span>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </section>
    @endif

    <!-- ===================== LATEST REGISTERED EVENTS GRID ===================== -->
    <section class="py-24 bg-dark-bg">
        <div class="px-4 md:px-8 max-w-7xl mx-auto">
            <div class="flex items-end justify-between mb-10">
                <h2 class="text-2xl md:text-3xl font-bold font-display text-white tracking-tight">Event Terbaru</h2>
                <a class="text-sm text-slate-400 hover:text-accent transition-colors" href="{{ url('/events') }}">
                    Lihat Semua →
                </a>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                @forelse($latestEvents as $event)
                    <a href="{{ route('events.show', $event) }}" class="group block">
                        <div class="bg-dark-card rounded-xl overflow-hidden border border-slate-800/40 flex flex-col transition-all duration-200 group-hover:border-accent/30 group-hover:shadow-lg group-hover:shadow-accent/5">
                            <div class="relative h-44 bg-slate-900 overflow-hidden">
                                @if($event->banner)
                                    <img class="w-full h-full object-cover transition duration-300 group-hover:opacity-80" alt="{{ $event->title }}" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($event->banner) }}"/>
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-slate-600 bg-slate-950 text-xs select-none">
                                        SportManado
                                    </div>
                                @endif
                            </div>

                            <div class="p-4 flex-1 flex flex-col justify-between space-y-2">
                                <div class="space-y-1">
                                    <div class="text-[10px] text-slate-500">
                                        {{ $event->event_date->translatedFormat('d M Y') }}
                                    </div>
                                    <h3 class="font-display font-bold text-sm text-white leading-snug group-hover:text-accent transition-colors">{{ $event->title }}</h3>
                                    @if($event->organizer)
                                        <div class="text-[10px] text-slate-500 truncate">
                                            {{ $event->organizer->organization_name }}
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="flex items-center justify-between pt-2 text-[10px]">
                                    <span class="text-slate-500">{{ $event->ticket_price > 0 ? 'Rp ' . number_format($event->ticket_price, 0, ',', '.') : 'Gratis' }}</span>
                                    @if($event->isFull())
                                        <span class="text-red-400">Penuh</span>
                                    @else
                                        <span class="text-emerald-400">Sisa {{ max(0, $event->max_participants - $event->tickets_count) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full flex flex-col items-center justify-center py-16 bg-dark-card border border-dashed border-slate-800 rounded-xl">
                        <p class="text-slate-500 font-bold">Belum ada event terdaftar</p>
                        <p class="text-sm text-slate-600 mt-1">Nantikan event olahraga komunitas berikutnya di Manado!</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

</x-app-layout>
