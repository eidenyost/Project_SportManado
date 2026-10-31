@extends('layouts.organizer')

@section('title', 'Dashboard Penyelenggara/EO – SportManado')
@section('page-title', 'Dashboard Penyelenggara/EO')

@section('content')
<span hidden data-refresh-on-return></span>
<div class="space-y-6">

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3" aria-label="Ringkasan saldo">
        <div class="min-w-0 rounded-xl border border-slate-700 bg-dark-card p-4"><p class="text-xs text-slate-400">Saldo tersedia</p><p class="mt-2 break-words text-xl font-bold text-orange-400">Rp {{ number_format($availableBalance,0,',','.') }}</p></div>
        <div class="min-w-0 rounded-xl border border-slate-700 bg-dark-card p-4"><p class="text-xs text-slate-400">Menunggu pencairan</p><p class="mt-2 break-words text-xl font-bold text-amber-400">Rp {{ number_format($pendingWithdrawal,0,',','.') }}</p></div>
        <div class="min-w-0 rounded-xl border border-slate-700 bg-dark-card p-4"><p class="text-xs text-slate-400">Sudah dicairkan</p><p class="mt-2 break-words text-xl font-bold text-emerald-400">Rp {{ number_format($withdrawn,0,',','.') }}</p></div>
    </div>
    <!-- Hero -->
    <div class="bg-dark-card border border-slate-800/40 rounded-xl p-5 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-lg font-bold text-white">Halo, {{ $organizer->organization_name }}!</h1>
                <p class="text-sm text-slate-400 mt-1">{{ now()->translatedFormat('l, d F Y') }}</p>
            </div>
            <a href="{{ route('organizer.events.create') }}" class="bg-[#ff6b00] hover:bg-[#e05e00] text-white px-5 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0">
                Buat Event Baru
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card bg-dark-card border border-slate-800/40 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs text-slate-500 font-medium">Total Event</span>
                <i data-lucide="calendar" class="w-4 h-4 text-[#ff6b00]"></i>
            </div>
            <p class="font-display font-black text-3xl tracking-tight text-white tabular-nums leading-none" data-count="{{ $totalEvents }}">{{ $totalEvents }}</p>
        </div>
        <div class="stat-card bg-dark-card border border-slate-800/40 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs text-slate-500 font-medium">Peserta Disetujui</span>
                <i data-lucide="users" class="w-4 h-4 text-[#ff6b00]"></i>
            </div>
            <p class="font-display font-black text-3xl tracking-tight text-white tabular-nums leading-none" data-count="{{ $totalParticipants }}">{{ $totalParticipants }}</p>
        </div>
        <div class="stat-card bg-dark-card border border-slate-800/40 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs text-slate-500 font-medium">Pendapatan</span>
                <i data-lucide="wallet" class="w-4 h-4 text-[#ff6b00]"></i>
            </div>
            <p class="font-display font-black text-3xl tracking-tight text-white tabular-nums leading-none" data-count="{{ $totalRevenue }}" data-currency="true">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
        </div>
        <div class="stat-card bg-dark-card border border-slate-800/40 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs text-slate-500 font-medium">Event Pending</span>
                <i data-lucide="clock" class="w-4 h-4 text-[#ff6b00]"></i>
            </div>
            <p class="font-display font-black text-3xl tracking-tight text-white tabular-nums leading-none" data-count="{{ $pendingEventsCount }}">{{ $pendingEventsCount }}</p>
        </div>
    </div>





    <!-- Recent Activity -->
    <div class="bg-dark-card border border-slate-800/40 rounded-xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-800/60">
            <div class="flex items-center gap-2">
                <i data-lucide="history" class="w-4 h-4 text-slate-500"></i>
                <h2 class="text-sm font-bold text-white">Pendaftaran Terbaru</h2>
            </div>
            @if($recentTickets->isNotEmpty())
            <a href="{{ route('organizer.events.index') }}" class="text-[11px] text-[#ff6b00] hover:text-[#e05e00] font-semibold transition-colors">Kelola Event</a>
            @endif
        </div>

        @if($recentTickets->isEmpty())
        <div class="py-12 text-center">
            <i data-lucide="users" class="w-10 h-10 text-slate-700 mx-auto mb-3"></i>
            <p class="text-sm text-slate-500 font-medium">Belum ada pendaftar</p>
            <p class="text-xs text-slate-600 mt-1">Peserta akan muncul di sini setelah mendaftar event Anda.</p>
        </div>
        @else
        <div class="divide-y divide-slate-800/40">
            @foreach($recentTickets as $ticket)
            <div class="flex items-center gap-4 px-5 py-3.5 hover:bg-white/[0.02] transition-colors">
                <div class="w-9 h-9 rounded-full bg-[#ff6b00] flex items-center justify-center text-xs font-bold text-white shrink-0 uppercase">
                    {{ strtoupper(substr($ticket->participant_name ?? '?', 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-white truncate">{{ $ticket->participant_name ?? '–' }}</p>
                    <p class="text-xs text-slate-500 truncate">{{ $ticket->event->title ?? '–' }}</p>
                </div>
                <div class="hidden sm:block text-xs text-slate-500 text-right">
                    <p>{{ $ticket->created_at->diffForHumans() }}</p>
                </div>
                <div>
                    @php
                        $badgeClass = match($ticket->status) {
                            'active' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                            'used' => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
                            'pending' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                            'cancelled' => 'bg-red-500/10 text-red-400 border-red-500/20',
                            default => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
                        };
                        $badgeLabel = match($ticket->status) {
                            'active' => $ticket->payment && $ticket->payment->status === 'confirmed' ? 'Lunas' : 'Aktif',
                            'used' => 'Hadir',
                            'pending' => $ticket->payment ? 'Menunggu Bayar' : 'Pending',
                            'cancelled' => 'Ditolak',
                            default => $ticket->status,
                        };
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $badgeClass }}">{{ $badgeLabel }}</span>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();

    const formatRupiah = (num) => {
        if (num >= 1000000000) return 'Rp ' + (num / 1000000000).toFixed(1) + 'M';
        if (num >= 1000000) return 'Rp ' + (num / 1000000).toFixed(1) + 'JT';
        return 'Rp ' + num.toLocaleString('id-ID');
    };

    const cards = document.querySelectorAll('.stat-card [data-count]');
    if (!cards.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const el = entry.target;
            observer.unobserve(el);
            const target = parseInt(el.dataset.count);
            const isCurrency = el.dataset.currency === 'true';
            const duration = 800;
            const start = performance.now();

            if (target === 0) {
                return;
            }

            function update(now) {
                const progress = Math.min((now - start) / duration, 1);
                const ease = 1 - Math.pow(1 - progress, 3);
                const current = Math.round(ease * target);

                el.textContent = isCurrency
                    ? (current >= 1000000 ? formatRupiah(current) : 'Rp ' + current.toLocaleString('id-ID'))
                    : current.toLocaleString('id-ID');

                if (progress < 1) requestAnimationFrame(update);
            }
            requestAnimationFrame(update);
        });
    }, { threshold: 0.3 });

    cards.forEach(el => observer.observe(el));
});
</script>
@endpush
