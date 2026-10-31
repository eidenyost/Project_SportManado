@extends('layouts.app')

@section('title', $event->title . ' – SportManado')

@section('content')
<div class="max-w-4xl mx-auto px-4 md:px-8 py-10">
    <a href="{{ route('events.index') }}" class="text-sm text-slate-500 hover:text-accent transition-colors">
        ← Kembali
    </a>

    @if($event->banner)
    <div class="mt-6 mb-8 rounded-xl overflow-hidden border border-slate-800/40">
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($event->banner) }}"
             alt="{{ $event->title }}"
             class="w-full h-56 md:h-72 object-cover">
    </div>
    @endif

    <!-- Header -->
    <div class="mt-6 mb-10">
        <span class="text-xs text-accent font-semibold">{{ $event->category }}</span>
        <h1 class="text-2xl md:text-3xl font-bold text-white mt-1 font-display">{{ $event->title }}</h1>
        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-3 text-sm text-slate-400">
            <span>{{ $event->event_date->translatedFormat('d M Y') }}, {{ $event->event_date->translatedFormat('H:i') }} WITA</span>
            <span>{{ $event->location }}</span>
        </div>
        @php $daysLeft = now()->diffInDays($event->registration_deadline, false); @endphp
        @if(now()->gt($event->registration_deadline))
            <p class="mt-2 text-sm text-red-400">Pendaftaran ditutup</p>
        @elseif($daysLeft <= 3)
            <p class="mt-2 text-sm text-amber-400">{{ ceil($daysLeft) }} hari lagi</p>
        @else
            <p class="mt-2 text-sm text-emerald-400">Pendaftaran dibuka</p>
        @endif
    </div>

    <!-- Description -->
    <div class="mb-10">
        <p class="text-xs text-slate-500 mb-3">Deskripsi</p>
        <div class="text-slate-400 leading-relaxed text-sm whitespace-pre-line">
            {{ $event->description }}
        </div>
    </div>

    <!-- Organizer -->
    @if($event->organizer)
    <div class="mb-10">
        <p class="text-xs text-slate-500 mb-3">Penyelenggara/EO</p>
        <p class="text-sm text-white font-semibold">{{ $event->organizer->organization_name }}</p>
    </div>
    @endif

    <!-- Pricing & Slots -->
    <div class="mb-10">
        <p class="text-xs text-slate-500 mb-3">Biaya &amp; Kuota</p>
        <div class="flex items-baseline gap-4">
            @if($event->ticket_price > 0)
                <span class="text-xl font-bold text-white">Rp {{ number_format($event->ticket_price, 0, ',', '.') }}</span>
            @else
                <span class="text-xl font-bold text-emerald-400">Gratis</span>
            @endif
            @php $slotsLeft = max(0, $event->max_participants - $participantCount); @endphp
            <span class="text-sm {{ $slotsLeft <= 0 ? 'text-red-400' : 'text-slate-400' }}">
                {{ $slotsLeft > 0 ? $slotsLeft . ' slot tersisa' : 'penuh' }}
            </span>
        </div>
    </div>

    <!-- E-Tiket Info -->
    <div class="mb-6 p-3 bg-slate-900/50 border border-slate-800 rounded-xl">
        <p class="text-xs text-slate-400">
            <span class="text-white font-semibold">E-Tiket Digital:</span>
            Setelah pendaftaran disetujui, Anda akan menerima e-tiket dengan QR code untuk check-in di lokasi event.
        </p>
    </div>

    <!-- CTA -->
    <div>
        @guest
            <a href="{{ route('login') }}" class="inline-block bg-accent text-white px-6 py-2.5 rounded-xl font-bold text-sm hover:bg-accent-hover transition-colors">
                Masuk untuk Mendaftar
            </a>
        @else
            @if(auth()->user()->role === 'organizer' || auth()->user()->role === 'admin')
                <p class="text-sm text-slate-500">Anda login sebagai {{ auth()->user()->role === 'admin' ? 'Admin' : 'Penyelenggara/EO' }}.</p>
            @else
                @if($userTicket)
                    @if($userTicket->status === 'pending')
                        @if($userTicket->payment && $userTicket->payment->status === 'pending')
                        <p class="text-sm text-amber-400 mb-2">Menunggu pembayaran.</p>
                        @else
                        <p class="text-sm text-amber-400 mb-2">Menunggu persetujuan panitia.</p>
                        @endif
                        <a href="{{ route('tickets.show', $userTicket) }}" class="inline-block bg-white/10 text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-white/20 transition-colors">
                            Lihat Tiket
                        </a>
                    @elseif($userTicket->status === 'active' || $userTicket->status === 'used')
                        <p class="text-sm text-emerald-400 mb-2">Pendaftaran berhasil.</p>
                        <a href="{{ route('tickets.show', $userTicket) }}" class="inline-block bg-accent text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-accent-hover transition-colors">
                            Tampilkan E-Tiket
                        </a>
                    @else
                        <p class="text-sm text-red-400 mb-2">Registrasi dibatalkan.</p>
                        @if(!$isFull && !now()->gt($event->registration_deadline))
                            <a href="{{ route('tickets.register', $event) }}" class="inline-block bg-accent text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-accent-hover transition-colors">
                                Daftar Ulang
                            </a>
                        @endif
                    @endif
                @else
                    @if($isFull)
                        <p class="text-sm text-red-400">Kuota penuh.</p>
                    @elseif(now()->gt($event->registration_deadline))
                        <p class="text-sm text-red-400">Pendaftaran ditutup.</p>
                    @else
                        <a href="{{ route('tickets.register', $event) }}" class="inline-block bg-accent text-white px-6 py-2.5 rounded-xl font-bold text-sm hover:bg-accent-hover transition-colors">
                            Daftar Sekarang
                        </a>
                    @endif
                @endif
            @endif
        @endguest
    </div>
</div>
@endsection
