@extends('layouts.organizer')

@section('title', 'Scan Tiket QR – ' . $event->title)

@section('content')
<div class="max-w-6xl mx-auto space-y-5">

    <!-- Header -->
    <div>
        <h1 class="text-lg font-bold text-white">{{ $event->title }}</h1>
        <p class="text-xs text-slate-500 mt-0.5">
            {{ $event->event_date->translatedFormat('l, d F Y · H:i') }} WITA &middot;
            {{ $event->location }}
        </p>
    </div>

    <!-- Tab Bar -->
    @include('organizer.events.tabs._tab_bar', ['event' => $event, 'active' => 'scan'])

    <!-- Scan Component -->
    @include('organizer.events.tabs._scan_shared')
</div>
@endsection


