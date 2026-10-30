@extends('layouts.organizer')

@section('title', 'Event Saya – SportManado')

@section('content')
<div class="space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-slate-800/60">
        <div>
            <h1 class="text-lg font-bold text-white">Event Saya</h1>
            <p class="text-xs text-slate-500 mt-0.5">{{ $events->total() }} event ditemukan</p>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <form method="GET" action="{{ route('organizer.events.index') }}" class="flex-1 w-full sm:max-w-xs">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari event..."
                       class="w-full pl-9 pr-4 py-2 bg-dark-card border border-slate-800/60 rounded-xl text-xs text-white placeholder-slate-500 outline-none focus:border-[#ff6b00] transition-colors">
            </div>
        </form>
        <div class="flex flex-wrap gap-1.5">
            @php
                $currentStatus = request('status');
                $allCount = array_sum($statusCounts->toArray());
            @endphp
            <a href="{{ route('organizer.events.index', array_merge(request()->except('status', 'page'), ['status' => null])) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition
                      {{ !$currentStatus ? 'bg-[#ff6b00] text-white' : 'bg-dark-card text-slate-400 border border-slate-800/60 hover:text-white hover:border-slate-600' }}">
                Semua ({{ $allCount }})
            </a>
            @foreach(['active' => 'Aktif', 'pending' => 'Pending', 'rejected' => 'Ditolak', 'cancelled' => 'Batal', 'completed' => 'Selesai'] as $val => $label)
                @if(isset($statusCounts[$val]) && $statusCounts[$val] > 0)
                    <a href="{{ route('organizer.events.index', array_merge(request()->except('status', 'page'), ['status' => $val])) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition
                              {{ $currentStatus === $val ? 'bg-[#ff6b00] text-white' : 'bg-dark-card text-slate-400 border border-slate-800/60 hover:text-white hover:border-slate-600' }}">
                        {{ $label }} ({{ $statusCounts[$val] }})
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    <!-- Events Grid -->
    @if($events->isEmpty())
        <div class="text-center py-20">
            <i data-lucide="calendar" class="w-12 h-12 text-slate-700 mx-auto mb-4"></i>
            <p class="text-sm text-slate-500">Belum ada event</p>
            <a href="{{ route('organizer.events.create') }}" class="text-[#ff6b00] text-xs font-semibold hover:underline mt-2 inline-block">+ Buat Event</a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($events as $event)
            @php
                $statusLabel = match($event->status) {
                    'active' => 'Aktif',
                    'completed' => 'Selesai',
                    'pending' => 'Pending',
                    'cancelled' => 'Batal',
                    'rejected' => 'Ditolak',
                    default => 'Draft',
                };
                $statusColors = match($event->status) {
                    'active' => 'bg-emerald-500/90 text-white',
                    'pending' => 'bg-amber-500/90 text-white',
                    'cancelled', 'rejected' => 'bg-red-500/90 text-white',
                    default => 'bg-slate-500/90 text-white',
                };
                $slotsLeft = max(0, $event->max_participants - $event->tickets_count);
                $eventLink = in_array($event->status, ['active','completed','cancelled'], true) ? route('organizer.events.manage', $event) : route('organizer.events.edit', $event);
                $actionLabel = $event->status === 'active' ? 'Kelola' : (in_array($event->status, ['completed','cancelled'], true) ? 'Lihat Arsip' : 'Edit');
            @endphp
            @php $borderClass = match($event->status) { 'active' => 'border-emerald-500/20', 'pending' => 'border-amber-500/20', 'rejected', 'cancelled' => 'border-red-500/10', default => 'border-slate-800/40', }; @endphp
<div class="relative bg-dark-card border-2 {{ $borderClass }} rounded-xl overflow-hidden">
                <!-- Banner -->
                <a href="{{ $eventLink }}" class="relative block aspect-video bg-slate-900 overflow-hidden">
                    @if($event->banner)
                        <img src="{{ asset('storage/' . $event->banner) }}" class="w-full h-full object-cover" alt="{{ $event->title }}">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-slate-600"><i data-lucide="image" class="w-6 h-6"></i></div>
                    @endif
                    <span class="absolute top-2 right-2 px-2 py-0.5 rounded text-[10px] font-bold {{ $statusColors }}">{{ $statusLabel }}</span>
                </a>
                <!-- Body -->
                <div class="p-4 space-y-3">
                    <a href="{{ $eventLink }}" class="font-bold text-sm text-white line-clamp-2 block leading-snug">
                        {{ $event->title }}
                    </a>
                    <div class="flex items-center gap-3 text-xs text-slate-400">
                        <span class="inline-flex items-center gap-1">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 shrink-0"></i>
                            {{ $event->event_date->translatedFormat('d F Y') }}
                        </span>
                        <span class="inline-flex items-center gap-1 truncate">
                            <i data-lucide="map-pin" class="w-3.5 h-3.5 shrink-0"></i>
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
                        <a href="{{ $eventLink }}"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-sm
                                   @if($event->status === 'active') bg-[#ff6b00] hover:bg-[#e05e00] text-white
                                   @else border border-slate-700 hover:bg-slate-800 text-slate-200 @endif">
                            {{ $actionLabel }}
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif

    @if(method_exists($events, 'links'))
    <div class="px-4 py-2.5 border-t border-slate-800/60 bg-dark-card rounded-xl">
        {{ $events->links() }}
    </div>
    @endif

</div>
@endsection
