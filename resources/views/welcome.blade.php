@extends('layouts.app')

@section('title', 'SportManado – Portal E-Tiket & Manajemen Event Olahraga Manado')

@section('content')

{{-- ════════════════════════════════════════════════════
     HERO SECTION
     ════════════════════════════════════════════════════ --}}
<section class="relative min-h-[400px] flex items-center pt-20 pb-16 overflow-hidden">

    <!-- Background Image -->
    <div class="absolute inset-0 z-0 pointer-events-none">
        <img class="w-full h-full object-cover opacity-15" alt="Kota Manado di senja hari" src="{{ asset('images/manado-city.jpg') }}"/>
        <div class="absolute inset-0 bg-gradient-to-t from-dark-bg to-dark-bg/30"></div>
    </div>
    
    <div class="relative z-10 w-full px-4 md:px-8 max-w-7xl mx-auto text-white">
        <div class="max-w-4xl space-y-6">
            <h1 class="text-[36px] leading-[1.1] md:text-6xl font-black font-display tracking-tight text-white">
                SATUKAN ENERGI OLAHRAGA <br>
                <span class="text-[#ff6b00] font-display">KOMUNITAS KOTA MANADO</span>
            </h1>
            
            <p class="max-w-2xl text-sm text-slate-400 leading-relaxed font-body md:text-base">
                Temukan dan ikuti event olahraga di Manado dengan mudah. SportManado menghubungkan Peserta dengan Penyelenggara/EO melalui informasi event, pendaftaran online, dan e-tiket berbasis kode QR.
            </p>
            
            <div class="flex flex-wrap gap-4 pt-2">
                <a href="{{ route('events.index') }}" class="px-7 py-3.5 bg-[#ff6b00] hover:bg-[#e05e00] text-white font-bold rounded-xl shadow-md transition-colors flex items-center gap-2 text-sm">
                    <span>Temukan Event</span>
                </a>
                @auth
                    @if(auth()->user()->role === 'organizer')
                        <a href="{{ route('organizer.events.create') }}" class="px-7 py-3.5 bg-slate-900 border border-slate-700 hover:border-slate-500 text-white font-bold rounded-xl transition-colors flex items-center gap-2 text-sm">
                            <span>Daftarkan Event</span>
                        </a>
                    @elseif(auth()->user()->role === 'admin')
                        <a href="{{ route('admin.dashboard') }}" class="px-7 py-3.5 bg-slate-900 border border-slate-700 hover:border-slate-500 text-white font-bold rounded-xl transition-colors flex items-center gap-2 text-sm">
                            <span>Admin Panel</span>
                        </a>
                    @else
                        <a href="{{ route('event-registration.create') }}" class="px-7 py-3.5 bg-slate-900 border border-slate-700 hover:border-slate-500 text-white font-bold rounded-xl transition-colors flex items-center gap-2 text-sm">
                            <span>Daftarkan Event</span>
                        </a>
                    @endif
                @else
                    <a href="{{ route('register.form', ['role' => 'organizer']) }}" class="px-7 py-3.5 bg-slate-900 border border-slate-700 hover:border-slate-500 text-white font-bold rounded-xl transition-colors flex items-center gap-2 text-sm">
                        <span>Daftarkan Event</span>
                    </a>
                @endauth
            </div>
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════════
     LATEST REGISTERED EVENTS GRID
     ════════════════════════════════════════════════════ --}}
<section class="py-16 bg-dark-bg">
    <div class="px-4 md:px-8 max-w-7xl mx-auto">
        <div class="flex items-end justify-between mb-10">
            <h2 class="text-2xl md:text-3xl font-bold font-display text-white tracking-tight">Event Terbaru</h2>
            <a class="text-sm text-slate-400 hover:text-accent transition-colors" href="{{ route('events.index') }}">
                Lihat Semua →
            </a>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            @forelse($events as $event)
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

{{-- ════════════════════════════════════════════════════
     GALLERY SECTION
     ════════════════════════════════════════════════════ --}}
<section class="py-16 bg-dark-bg border-t border-slate-900/60">
    <div class="px-4 md:px-8 max-w-7xl mx-auto">
        <div class="flex items-end justify-between mb-10">
            <h2 class="text-2xl md:text-3xl font-bold font-display text-white tracking-tight">Dokumentasi Event</h2>
            <a class="text-sm text-slate-400 hover:text-accent transition-colors" href="{{ route('pages.galeri') }}">
                Lihat Semua →
            </a>
        </div>

        @if($galleryPhotos->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 bg-dark-card border border-dashed border-slate-800 rounded-xl">
                <p class="text-slate-500 font-bold">Belum ada dokumentasi foto yang ditambahkan.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5"
                 x-data="{ lightbox: null }"
                 @keydown.window.escape="lightbox = null">
                @foreach($galleryPhotos as $photo)
                    <div class="bg-dark-card rounded-xl overflow-hidden border border-slate-800/40 cursor-pointer"
                         @click="lightbox = '{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo->image_path) }}'">
                        <div class="relative h-52 bg-slate-950 overflow-hidden">
                            <img class="w-full h-full object-cover transition duration-300 hover:opacity-80"
                                 src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo->image_path) }}"
                                 alt="Foto galeri">
                        </div>
                    </div>
                @endforeach

                <template x-teleport="body">
                    <div x-show="lightbox"
                         x-transition.opacity.duration.200ms
                         class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4"
                         @click="lightbox = null">
                        <button class="absolute top-5 right-5 text-white/60 hover:text-white text-3xl leading-none" @click="lightbox = null">&times;</button>
                        <div class="max-w-5xl max-h-[90vh]" @click.stop>
                            <img :src="lightbox" alt="Foto galeri" class="max-w-full max-h-[90vh] object-contain rounded-lg">
                        </div>
                    </div>
                </template>
            </div>
        @endif
    </div>
</section>
@endsection
