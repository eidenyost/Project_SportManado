@extends('layouts.app')

@section('title', 'Jelajahi Event Olahraga – SportManado')

@section('content')
<span hidden data-refresh-on-return></span>
<form method="GET" action="{{ route('events.index') }}" id="filterForm">
    <main class="max-w-7xl mx-auto px-4 md:px-8 py-12">
        <h1 class="text-2xl md:text-3xl font-bold text-white font-display">Event</h1>
        <p class="text-slate-400 mt-1 mb-6">Temukan event olahraga di Manado</p>

        <div class="flex flex-col sm:flex-row gap-3 mb-8">
            <div class="min-w-0 flex-1 flex items-center bg-dark-bg border border-slate-800/40 rounded-xl p-1 pl-4 focus-within:border-accent/50 transition-colors">
                <input class="min-w-0 w-full border-none focus:ring-0 bg-transparent text-sm py-2 px-3 outline-none text-white placeholder-slate-500" name="search" value="{{ request('search') }}" placeholder="Cari event..." type="text"/>
                <button type="submit" class="bg-accent text-white px-5 py-2 rounded-xl font-bold text-sm shrink-0 hover:bg-accent-hover transition-colors">Cari</button>
            </div>
        </div>

        <div aria-label="Filter kategori event" class="flex gap-2 overflow-x-auto pb-2 mb-6">
            <a href="{{ route('events.index', request()->except(['category','page'])) }}"
               class="inline-flex min-h-11 shrink-0 items-center whitespace-nowrap px-4 py-2 rounded-full text-xs font-semibold transition-colors {{ !request('category') ? 'bg-accent text-white' : 'bg-dark-bg border border-slate-800/40 text-slate-400 hover:border-slate-600' }}">
                Semua
            </a>
            @foreach($categories as $cat)
                <a href="{{ route('events.index', array_merge(request()->except(['category','page']), ['category' => $cat])) }}"
                   class="inline-flex min-h-11 shrink-0 items-center whitespace-nowrap px-4 py-2 rounded-full text-xs font-semibold transition-colors {{ request('category') === $cat ? 'bg-accent text-white' : 'bg-dark-bg border border-slate-800/40 text-slate-400 hover:border-slate-600' }}">
                    {{ $cat }}
                </a>
            @endforeach
        </div>

        <p class="text-sm text-slate-400 mb-6">
            <span class="font-bold text-white">{{ $events->total() }}</span> Event ditemukan
        </p>

        @if($events->isEmpty())
            <div class="text-center py-24 bg-dark-card rounded-xl border border-dashed border-slate-800">
                <p class="text-lg font-bold text-slate-400">Tidak ada event ditemukan</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($events as $event)
                    <a href="{{ route('events.show', $event) }}" class="group">
                        <div class="relative aspect-video bg-dark-bg rounded-md overflow-hidden mb-3">
                            @if($event->banner)
                                <img alt="{{ $event->title }}" class="w-full h-full object-cover" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($event->banner) }}"/>
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-600 bg-slate-950 text-sm select-none">SportManado</div>
                            @endif
                        </div>
                        <h3 class="font-bold text-sm text-white group-hover:text-accent transition-colors line-clamp-2">{{ $event->title }}</h3>
                        <div class="mt-1 text-xs text-slate-500">
                            {{ $event->event_date->translatedFormat('d M Y, H:i') }} WITA
                        </div>
                        <div class="mt-0.5 flex items-center gap-2 text-xs">
                            <span class="text-slate-500 truncate">{{ $event->location }}</span>
                            <span class="text-slate-600">•</span>
                            @if($event->ticket_price > 0)
                                <span class="text-accent font-semibold whitespace-nowrap">Rp {{ number_format($event->ticket_price, 0, ',', '.') }}</span>
                            @else
                                <span class="text-emerald-400 font-semibold whitespace-nowrap">Gratis</span>
                            @endif
                        </div>
                        @php
                            $slotsLeft = max(0, $event->max_participants - $event->tickets_count);
                        @endphp
                        <div class="mt-1 text-xs text-slate-500">
                            @if($slotsLeft <= 0)
                                <span class="text-red-400 font-medium">Kuota penuh</span>
                            @else
                                {{ $slotsLeft }} slot tersisa
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        @if($events->hasPages())
            <div class="mt-16 flex justify-center">
                {{ $events->appends(request()->query())->links() }}
            </div>
        @endif
    </main>
</form>
@endsection
