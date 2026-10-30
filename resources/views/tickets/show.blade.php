@extends('layouts.app')

@section('title', 'E-Tiket #' . $ticket->ticket_code . ' – SportManado')

@section('content')
<div class="max-w-3xl mx-auto px-4 md:px-8 py-8">

    @if(!empty($paymentError))
        <div role="alert" class="mb-4 rounded-xl border border-amber-800 bg-amber-950/30 p-4 text-sm text-amber-300">{{ $paymentError }}</div>
    @endif

    @if($ticket->status === 'cancelled' && $ticket->cancellation_reason)
        <div role="status" class="mb-4 rounded-xl border border-red-800 bg-red-950/30 p-4 text-sm text-red-300">
            <p class="font-semibold">Kepesertaan dibatalkan</p>
            <p class="mt-2 whitespace-pre-wrap break-words">{{ $ticket->cancellation_reason }}</p>
            @if($ticket->cancelled_at)<p class="mt-2 text-xs">{{ $ticket->cancelled_at->timezone('Asia/Makassar')->format('d/m/Y H:i:s') }} WITA</p>@endif
        </div>
    @endif

    <div class="mb-6">
        <a href="{{ route('tickets.index') }}"
           class="text-sm text-slate-400 hover:text-accent">
            ← Kembali ke Tiket Saya
        </a>
    </div>

    <div class="bg-dark-card rounded-xl p-6">
        <span class="text-xs font-semibold text-accent">{{ strtoupper($ticket->event->category ?? 'OLAHRAGA') }}</span>
        <h2 class="text-xl font-bold text-white mt-0.5">{{ $ticket->event->title ?? 'Event Olahraga' }}</h2>
        <p class="text-xs text-slate-400 mt-2">Penyelenggara/EO: <strong class="text-white">{{ $ticket->event->organizer->organization_name ?? 'Penyelenggara/EO' }}</strong></p>

        <div class="border-t border-slate-800/60 my-5"></div>

        <div class="ticket-details space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="text-slate-500">Nama Peserta</span>
                <span class="text-white font-semibold">{{ $ticket->participant_name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Email</span>
                <span class="text-slate-300">{{ $ticket->participant_email }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Tanggal Event</span>
                <span class="text-white font-semibold">{{ $ticket->event->event_date->translatedFormat('d F Y, H:i') }} WITA</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Lokasi</span>
                <span class="text-white font-semibold text-right max-w-[60%]">{{ $ticket->event->location }}</span>
            </div>

            @if($ticket->payment)
            <div class="flex justify-between">
                <span class="text-slate-500">Biaya</span>
                <span class="text-accent font-bold">Rp {{ number_format($ticket->payment->amount, 0, ',', '.') }}</span>
            </div>
            @endif

            <div class="flex justify-between items-center pt-1">
                <span class="text-slate-500">Status</span>
                @if($ticket->status === 'active')
                <span class="text-emerald-400 font-semibold text-xs">Aktif</span>
                @elseif($ticket->status === 'pending' && $ticket->payment && $ticket->payment->status === 'pending')
                <span class="text-amber-400 font-semibold text-xs">Menunggu Pembayaran</span>
                @elseif($ticket->status === 'pending')
                <span class="text-emerald-400 font-semibold text-xs">Menunggu Persetujuan Penyelenggara/EO</span>
                @elseif($ticket->status === 'used')
                <span class="text-slate-400 font-semibold text-xs">Selesai pada {{ $ticket->used_at?->translatedFormat('d M Y, H:i') }} WITA</span>
                @else
                <span class="text-red-400 font-semibold text-xs">Dibatalkan</span>
                @endif
            </div>

            @if($ticket->custom_answers)
            <div class="border-t border-slate-800/60 pt-4 mt-4">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Data Pendaftaran</p>
                @foreach($ticket->custom_answers as $label => $value)
                <div class="flex justify-between py-1">
                    <span class="text-slate-500">{{ $label }}</span>
                    <span class="text-white font-medium text-right max-w-[60%]">{{ $value }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        @if(filled($ticket->notes))
            <div class="mt-5 border-t border-slate-800 pt-4">
                <p class="text-xs font-bold text-slate-400">Catatan Pendaftaran</p>
                <p class="mt-2 whitespace-pre-wrap break-words text-sm text-slate-200">{{ $ticket->notes }}</p>
            </div>
        @endif

        @if($ticket->status === 'active')
        <div class="border-t border-slate-800/60 my-5"></div>

        <div class="text-center">
            <div class="ticket-qr-wrap bg-dark-card p-4 rounded-xl inline-block mx-auto">
                @if($ticket->qr_code_path)
                    <img src="{{ asset('storage/' . $ticket->qr_code_path) }}" alt="QR Code" class="ticket-qr">
                @else
                    <div class="ticket-qr bg-dark-bg border border-slate-700/50 rounded-xl flex flex-col items-center justify-center text-slate-500">
                        <span class="material-symbols-outlined text-4xl mb-2">qr_code_scanner</span>
                        <p class="text-xs">QR Code akan tersedia</p>
                        <p class="text-xs">setelah tiket aktif</p>
                    </div>
                @endif
            </div>

            <p class="text-base font-mono font-bold tracking-widest text-white mt-3">{{ $ticket->ticket_code }}</p>
            <p class="mt-3 text-xs text-slate-400">Jaga QR ini. Tunjukkan ke panitia di lapangan.</p>
        </div>
        @endif

        @if($ticket->status === 'pending' && $ticket->payment && $ticket->payment->status === 'pending')
            @if($ticket->payment->reservation_expires_at)
                <p class="mb-4 break-words text-sm text-amber-300">Batas pembayaran: {{ $ticket->payment->reservation_expires_at->timezone('Asia/Makassar')->format('d/m/Y H:i') }} WITA. Status pembayaran akan diperiksa sebelum kuota dilepas.</p>
            @endif

        <div class="border-t border-slate-800/60 my-5"></div>
        <div class="bg-dark-bg border border-slate-700/50 rounded-xl p-4">
            @if($demoMode)
                <div class="flex justify-between items-center mt-3 mb-4">
                    <span class="text-sm text-slate-500">Total Bayar</span>
                    <span class="text-lg font-bold text-accent">Rp{{ number_format($ticket->payment->amount, 0, ',', '.') }}</span>
                </div>
                <form action="{{ route('tickets.demo-pay', $ticket) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="w-full bg-accent text-white py-3 rounded-xl font-bold text-sm">
                        Konfirmasi Pembayaran
                    </button>
                </form>
            @elseif($snapToken)
                <p class="text-xs text-slate-400">Pembayaran diproses oleh Midtrans</p>
                <div class="flex justify-between items-center mt-3 mb-4">
                    <span class="text-sm text-slate-500">Total Bayar</span>
                    <span class="text-lg font-bold text-accent">Rp{{ number_format($ticket->payment->amount, 0, ',', '.') }}</span>
                </div>
                <button id="pay-button"
                        class="w-full bg-accent text-white py-3 rounded-xl font-bold text-sm">
                    Bayar Sekarang
                </button>
            @else
                <p class="text-xs text-slate-400">Menunggu konfigurasi pembayaran. Silakan coba lagi nanti.</p>
            @endif
            <p class="text-xs text-slate-500 text-center mt-2">Kode Tiket: <span class="font-mono text-accent">{{ $ticket->ticket_code }}</span></p>
        </div>
        @endif

        @if($ticket->payment && $ticket->payment->notes)
        <div class="mt-4 text-xs text-slate-400">
            <strong class="text-white">Catatan:</strong> {{ $ticket->payment->notes }}
        </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
@if($snapToken)
    <script src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
    <script>
        document.getElementById('pay-button').onclick = function(){
            snap.pay('{{ $snapToken }}', {
                onSuccess: function(result){
                    window.location.href = "{{ route('tickets.check-payment', $ticket) }}";
                },
                onPending: function(result){
                    window.location.href = "{{ route('tickets.check-payment', $ticket) }}";
                },
                onError: function(result){
                    alert("Pembayaran gagal!");
                }
            });
        };
    </script>
@endif
@endpush
