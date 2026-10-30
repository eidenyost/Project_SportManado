<div class="space-y-4 animate-in fade-in duration-300">
    @php $pc = $paymentCounts instanceof \Illuminate\Support\Collection ? $paymentCounts->toArray() : (is_array($paymentCounts) ? $paymentCounts : []); @endphp

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4">
            <span class="text-[11px] text-slate-500 font-medium">Total Transaksi</span>
            <p class="font-display font-black text-2xl tracking-tight text-white tabular-nums mt-1">{{ array_sum($pc) }}</p>
        </div>
        <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4">
            <span class="text-[11px] text-slate-500 font-medium">Pending</span>
            <p class="font-display font-black text-2xl tracking-tight text-amber-400 tabular-nums mt-1">{{ $pc['pending'] ?? 0 }}</p>
        </div>
        <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4">
            <span class="text-[11px] text-slate-500 font-medium">Dikonfirmasi</span>
            <p class="font-display font-black text-2xl tracking-tight text-emerald-400 tabular-nums mt-1">{{ $pc['confirmed'] ?? 0 }}</p>
        </div>
        <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4">
            <span class="text-[11px] text-slate-500 font-medium">Ditolak</span>
            <p class="font-display font-black text-2xl tracking-tight text-red-400 tabular-nums mt-1">{{ $pc['rejected'] ?? 0 }}</p>
        </div>
    </div>

    <nav aria-label="Filter pembayaran" class="flex gap-2 overflow-x-auto pb-1">
        @foreach([''=>'Semua','pending'=>'Pending','confirmed'=>'Lunas','rejected'=>'Dibatalkan'] as $value=>$label)
        <a class="inline-flex min-h-11 shrink-0 items-center rounded-lg border border-slate-700 px-3 py-2 text-xs text-white" href="{{ route('admin.dashboard',array_merge(request()->except(['status','page']),['tab'=>'payment_monitoring','status'=>$value])) }}">{{ $label }}</a>
        @endforeach
    </nav>
    <div class="bg-dark-card rounded-xl border border-slate-800/40 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800/60 flex flex-col md:flex-row justify-between items-center gap-4 bg-[#080c14]">
            <form method="GET" action="{{ route('admin.dashboard') }}" class="relative w-full md:w-72 ml-auto">
                <input type="hidden" name="tab" value="payment_monitoring">
                <svg class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nama, kode tiket, event..."
                       class="w-full pl-9 pr-4 py-2 bg-[#080c14] rounded-xl border border-slate-800/60 outline-none focus:border-[#ff6b00] text-xs text-white placeholder-slate-500 transition-colors">
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-800/60 bg-[#080c14] text-slate-300 font-bold uppercase tracking-wider">
                        <th class="p-4">Kode Tiket</th>
                        <th class="p-4">Event</th>
                        <th class="p-4">Pembayar</th>
                        <th class="p-4">Jumlah</th>
                        <th class="p-4">Metode</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-200">
                    @forelse($payments as $p)
                    <tr class="hover:bg-slate-800/20 transition-colors border-b border-slate-800/50">
                        <td class="p-4 font-mono text-[#ff6b00] font-bold">
                            {{ $p->ticket->ticket_code ?? 'N/A' }}
                        </td>
                        <td class="p-4 font-bold text-white max-w-xs truncate">
                            {{ $p->ticket->event->title ?? 'Event Dihapus' }}
                        </td>
                        <td class="p-4">
                            <p class="font-bold text-white text-xs">{{ $p->ticket?->participant_name ?? $p->user?->name ?? 'Peserta' }}</p>
                            <p class="text-[9px] text-slate-400 font-mono">{{ $p->user->email ?? '' }}</p>
                        </td>
                        <td class="p-4 text-emerald-400 font-bold font-mono">
                            Rp {{ number_format($p->amount, 0, ',', '.') }}
                        </td>
                        <td class="p-4 text-slate-400 uppercase font-bold text-[10px]">
                            {{ ucwords(str_replace('_', ' ', $p->method)) }}
                        </td>
                        <td class="p-4">
                            @php
                                $badgeClass = match($p->status) {
                                    'confirmed' => 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
                                    'rejected'  => 'bg-red-500/10 border-red-500/20 text-red-400',
                                    default     => 'bg-amber-500/10 border-amber-500/20 text-amber-400'
                                };
                                $badgeLabel = match($p->status) {
                                    'confirmed' => 'Dikonfirmasi',
                                    'rejected'  => 'Ditolak',
                                    default     => 'Pending'
                                };
                            @endphp
                            <span class="px-2.5 py-0.5 border text-[10px] rounded-full font-bold {{ $badgeClass }}">{{ $badgeLabel }}</span>
                        </td>
                        <td class="p-4 text-right">
                            @if($p->status !== 'pending')
                            <span class="text-emerald-400 font-bold text-[10px]">Selesai</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-400">Belum ada transaksi pembayaran tiket.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($payments, 'links'))
        <div class="px-4 py-2.5 border-t border-slate-800/60 bg-[#080c14]">
            {{ $payments->links() }}
        </div>
        @endif
    </div>
</div>
