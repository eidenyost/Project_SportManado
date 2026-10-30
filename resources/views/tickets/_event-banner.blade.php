<div class="bg-dark-card rounded-xl p-4 mb-6 flex flex-col sm:flex-row gap-4 items-start sm:items-center">
    @if($event->banner)
        <img src="{{ asset('storage/' . $event->banner) }}"
             class="w-full sm:w-16 h-16 object-cover rounded-xl flex-shrink-0" alt="{{ $event->title }}">
    @else
        <div class="w-16 h-16 rounded-xl bg-dark-bg flex items-center justify-center flex-shrink-0">
            <span class="text-white/30 text-xl">🏟</span>
        </div>
    @endif
    <div class="w-full sm:w-auto flex-1 min-w-0">
        <span class="text-xs font-semibold text-accent">{{ strtoupper($event->category) }}</span>
        <h2 class="text-base font-bold text-white mt-0.5 truncate">{{ $event->title }}</h2>
        <p class="text-xs text-slate-400 mt-0.5">{{ $event->event_date->translatedFormat('d M Y, H:i') }} WITA · {{ $event->location }}</p>
    </div>
    <div class="text-right flex-shrink-0">
        <p class="text-xs text-slate-500 font-medium">Biaya</p>
        @if($event->ticket_price > 0)
        <p class="text-base font-bold text-accent">Rp {{ number_format($event->ticket_price, 0, ',', '.') }}</p>
        @else
        <p class="text-base font-bold text-emerald-400">GRATIS</p>
        @endif
    </div>
</div>
