@extends('layouts.app')

@section('title', 'Tiket Saya – SportManado')

@section('content')
<div class="max-w-5xl mx-auto px-4 md:px-8 py-8">

    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h1 class="text-2xl font-bold text-white">
            Tiket Saya
        </h1>
        <a href="{{ route('events.index') }}" class="inline-flex items-center justify-center px-5 py-2.5 bg-accent text-white rounded-xl font-semibold text-sm">
            Cari Event Baru
        </a>
    </div>

    <div class="mb-5"><x-search-form :action="route('tickets.index')" placeholder="Cari event, nama peserta, atau kode tiket..." /></div>
    @if($tickets->isEmpty())
    <div class="bg-dark-card rounded-xl p-10 text-center border border-slate-800/50">
        <h3 class="text-xl font-bold text-white">{{ request('search') ? 'Tidak ada tiket yang cocok' : 'Anda Belum Memiliki Tiket' }}</h3>
        <p class="text-sm text-slate-400 max-w-md mx-auto mt-2">Mulailah menjelajahi berbagai event olahraga komunitas di Kota Manado.</p>
        <a href="{{ route('events.index') }}" class="inline-flex mt-5 px-6 py-3 bg-accent text-white rounded-xl font-semibold text-sm">
            Cari Event Olahraga
        </a>
    </div>
    @else

    @php
        $upcoming = $tickets->filter(fn($t) => $t->event && $t->event->event_date?->isFuture());
        $past = $tickets->filter(fn($t) => !$t->event || !$t->event->event_date?->isFuture());
    @endphp

    @if($upcoming->isNotEmpty())
    <div class="mb-3">
        <span class="text-xs text-slate-500">Akan Datang</span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-10">
        @foreach($upcoming as $ticket)
            @include('tickets._card')
        @endforeach
    </div>
    @endif

    @if($past->isNotEmpty())
    <div class="mb-3">
        <span class="text-xs text-slate-500">Riwayat</span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($past as $ticket)
            @include('tickets._card')
        @endforeach
    </div>
    @endif

    <div class="mt-8">
        {{ $tickets->links() }}
    </div>
    @endif
</div>
@endsection
