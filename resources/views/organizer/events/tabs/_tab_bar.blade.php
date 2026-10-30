@props(['event', 'active'])

<div class="flex gap-1 bg-dark-card border border-slate-800/40 rounded-lg p-1">
    <a href="{{ route('organizer.events.participants', $event) }}"
       class="flex-1 py-2 px-3 rounded-lg text-xs font-semibold transition flex items-center justify-center gap-1.5
              {{ $active === 'participants' ? 'bg-[#ff6b00] text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/30' }}">
        <i data-lucide="users" class="w-3.5 h-3.5"></i>
        Peserta
    </a>
    @if($event->acceptsCheckIn())
    <a href="{{ route('organizer.events.scan', $event) }}"
       class="flex-1 py-2 px-3 rounded-lg text-xs font-semibold transition flex items-center justify-center gap-1.5
              {{ $active === 'scan' ? 'bg-[#ff6b00] text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/30' }}">
        <i data-lucide="qr-code" class="w-3.5 h-3.5"></i>
        Scan QR
    </a>
    @endif
    @if(in_array($event->status, ['active','completed'], true))
    <a href="{{ route('organizer.finances.finance', $event) }}"
       class="flex-1 py-2 px-3 rounded-lg text-xs font-semibold transition flex items-center justify-center gap-1.5
              {{ $active === 'finance' ? 'bg-[#ff6b00] text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/30' }}">
        <i data-lucide="wallet" class="w-3.5 h-3.5"></i>
        Keuangan
    </a>
    @endif
</div>
