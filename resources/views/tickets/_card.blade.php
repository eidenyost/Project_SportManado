<div class="bg-dark-card rounded-xl border border-slate-800/50">

    <div class="p-4 space-y-2.5">
        <div class="flex items-center justify-between gap-3">
            <span class="text-xs font-mono font-semibold text-accent">#{{ $ticket->ticket_code }}</span>

            @if($ticket->status === 'active')
            <span class="text-[11px] font-semibold text-emerald-400">Aktif</span>
            @elseif($ticket->status === 'pending')
                @if($ticket->payment && $ticket->payment->status === 'pending')
                <span class="text-[11px] font-semibold text-amber-400">Menunggu Bayar</span>
                @else
                <span class="text-[11px] font-semibold text-emerald-400">Menunggu Verifikasi</span>
                @endif
            @elseif($ticket->status === 'used')
            <span class="text-[11px] font-semibold text-slate-500">Selesai</span>
            @else
            <span class="text-[11px] font-semibold text-red-400">Dibatalkan</span>
            @endif
        </div>

        @if($ticket->event)
        <div>
            <span class="text-[10px] font-semibold text-accent">{{ strtoupper($ticket->event->category) }}</span>
            <h3 class="text-base font-bold text-white mt-0.5">{{ $ticket->event->title }}</h3>
            <p class="text-xs text-slate-400 mt-1">{{ $ticket->event->event_date->translatedFormat('d M Y, H:i') }} WITA</p>
            <p class="text-xs text-slate-500 mt-0.5 truncate">{{ $ticket->event->location }}</p>
        </div>
        @else
        <p class="text-sm text-red-400">Event telah dihapus</p>
        @endif

        <div class="flex items-center justify-between pt-2 border-t border-slate-800/50">
            @if($ticket->payment)
            <p class="text-sm font-bold text-white">Rp {{ number_format($ticket->payment->amount, 0, ',', '.') }}</p>
            @else
            <p class="text-sm font-bold text-emerald-400">GRATIS</p>
            @endif

            <div class="flex items-center gap-2">
                @php
                    $btnLabel = 'Lihat Tiket';
                    $btnClass = 'bg-accent/10 text-accent';
                    if ($ticket->status === 'pending' && $ticket->payment && $ticket->payment->status === 'pending') {
                        $btnLabel = 'Bayar Sekarang';
                        $btnClass = 'bg-accent text-white';
                    } elseif ($ticket->status === 'active') {
                        $btnLabel = 'Lihat E-Tiket';
                        $btnClass = 'bg-emerald-500/10 text-emerald-400';
                    } elseif ($ticket->status === 'used') {
                        $btnLabel = 'Lihat Detail';
                        $btnClass = 'bg-slate-700/50 text-slate-400';
                    }
                @endphp
                <a href="{{ route('tickets.show', $ticket) }}"
                   class="px-4 py-1.5 {{ $btnClass }} font-semibold text-xs rounded-xl">
                    {{ $btnLabel }}
                </a>
            </div>
        </div>
    </div>
</div>
