@extends('layouts.organizer')

@section('title', 'Manajemen Peserta – ' . $event->title)

@push('styles')
<style>
    tr.ticket-row:hover td { background: rgba(255,107,0,.04); }
</style>
@endpush

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

    @if(!$event->acceptsCheckIn())
        <p class="rounded-xl border border-slate-700 p-4 text-sm text-slate-300">Arsip kegiatan: data peserta dan ekspor tetap tersedia. Perubahan peserta dan pemindaian QR telah ditutup.</p>
    @endif

    <!-- Tab Bar -->
    @include('organizer.events.tabs._tab_bar', ['event' => $event, 'active' => 'participants'])

    <!-- Stats -->
    @php
        $totalAll   = $totalAll ?? \App\Models\Ticket::where('event_id', $event->id)->count();
        $validAll   = $validAll ?? \App\Models\Ticket::where('event_id', $event->id)->whereIn('status', ['active', 'used'])->count();
        $pendingAll = $pendingAll ?? \App\Models\Ticket::where('event_id', $event->id)->where('status', 'pending')->count();
        $usedAll    = $usedAll ?? \App\Models\Ticket::where('event_id', $event->id)->where('status', 'used')->count();
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4 text-center">
            <i data-lucide="users" class="w-4 h-4 text-slate-400 mx-auto mb-1.5"></i>
            <p class="text-2xl font-extrabold text-white">{{ $totalAll }}</p>
            <p class="text-[10px] text-slate-500 mt-0.5 font-medium uppercase tracking-wider">Total Peserta</p>
        </div>
        <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4 text-center">
            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400 mx-auto mb-1.5"></i>
            <p class="text-2xl font-extrabold text-white">{{ $validAll }}</p>
            <p class="text-[10px] text-slate-500 mt-0.5 font-medium uppercase tracking-wider">Tiket Disetujui</p>
        </div>
        <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4 text-center">
            <i data-lucide="clock" class="w-4 h-4 text-amber-400 mx-auto mb-1.5"></i>
            <p class="text-2xl font-extrabold text-white">{{ $pendingAll }}</p>
            <p class="text-[10px] text-slate-500 mt-0.5 font-medium uppercase tracking-wider">Pending</p>
        </div>
        <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4 text-center">
            <i data-lucide="log-in" class="w-4 h-4 text-sky-400 mx-auto mb-1.5"></i>
            <p class="text-2xl font-extrabold text-white">{{ $usedAll }}</p>
            <p class="text-[10px] text-slate-500 mt-0.5 font-medium uppercase tracking-wider">Hadir</p>
        </div>
    </div>

    <x-search-form :action="route('organizer.events.participants', $event)" placeholder="Cari nama peserta, email, atau kode tiket..." />
    <p class="text-xs text-slate-400">Menampilkan {{ $tickets->firstItem() ?? 0 }}–{{ $tickets->lastItem() ?? 0 }} dari {{ $tickets->total() }} hasil. Ringkasan di atas mencakup seluruh peserta event.</p>

    <!-- Filter + Export -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <nav aria-label="Filter status peserta" class="flex min-w-0 max-w-full gap-2 overflow-x-auto pb-1">
            @foreach([
                '' => 'Semua',
                'pending' => 'Pending',
                'active' => 'Aktif',
                'used' => 'Hadir',
                'cancelled' => 'Dibatalkan',
            ] as $f => $label)
            <a href="{{ route('organizer.events.participants', array_merge(['event'=>$event], request()->except(['filter','page']), ['filter'=>$f])) }}"
               aria-current="{{ request('filter','') === $f ? 'page' : 'false' }}" class="inline-flex min-h-11 shrink-0 items-center gap-1.5 whitespace-nowrap px-3 py-2 text-xs font-semibold rounded-lg transition-all
                   {{ request('filter', '') === $f
                       ? 'bg-[#ff6b00] text-white shadow-sm'
                       : 'text-slate-400 border border-slate-800 hover:bg-slate-800/40 hover:text-white' }}">
                {{ $label }} <span class="text-[10px] opacity-75">{{ $f === '' ? $totalAll : ($statusCounts[$f] ?? 0) }}</span>
            </a>
            @endforeach
        </nav>
         <a href="{{ route('organizer.events.participants.export', array_merge(['event'=>$event],request()->except('page'))) }}"
            class="px-3 py-1.5 text-[10px] font-bold text-white bg-slate-700 hover:bg-slate-600 rounded-lg transition flex-shrink-0">
            Export CSV
        </a>
    </div>

    <!-- Table -->
    <div class="bg-dark-card border border-slate-800/40 rounded-xl overflow-hidden">
        @if($tickets->isEmpty())
        <div class="py-16 text-center">
            <i data-lucide="users" class="w-8 h-8 text-slate-600 mx-auto mb-3"></i>
            <p class="text-slate-400 font-semibold text-sm">Tidak ada peserta yang cocok dengan filter.</p>
        </div>
        @else
        <p class="px-4 pt-3 text-xs text-slate-500 sm:hidden">Geser tabel ke samping untuk melihat kolom Aksi.</p>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead>
                    <tr class="text-left border-b border-slate-800 text-slate-500 text-[10px] font-bold bg-[#080c14] uppercase tracking-wider">
                        <th class="px-3 py-3 w-6"></th>
                        <th class="px-4 py-3 w-8">#</th>
                        <th class="px-4 py-3">Peserta</th>
                        <th class="px-4 py-3">Kode Tiket</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Biaya</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @foreach($tickets as $ticket)
                    <tr class="ticket-row transition-colors cursor-pointer" onclick="toggleDetail({{ $ticket->id }})">
                        <td class="px-3 py-3.5 text-center">
                            <i data-lucide="chevron-down" class="chevron-icon w-3.5 h-3.5 text-slate-500 transition-transform duration-200"></i>
                        </td>
                        <td class="px-4 py-3.5 text-slate-500 font-mono text-[10px]">
                            {{ ($tickets->currentPage() - 1) * $tickets->perPage() + $loop->iteration }}
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-lg bg-slate-800 flex items-center justify-center text-[10px] font-bold text-slate-300 flex-shrink-0 uppercase">
                                    {{ strtoupper(substr($ticket->participant_name ?? '?', 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-white text-xs truncate">{{ $ticket->participant_name ?? '–' }}</p>
                                    <p class="text-[10px] text-slate-500 truncate">{{ $ticket->participant_email ?? '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3.5">
                            <code class="whitespace-nowrap font-mono text-[10px] text-[#ff6b00] bg-[#ff6b00]/10 px-1.5 py-0.5 rounded border border-[#ff6b00]/20">
                                {{ $ticket->ticket_code }}
                            </code>
                        </td>
                        <td class="px-4 py-3.5">
                            @php
                                $sClass = match($ticket->status) {
                                    'active'    => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                    'used'      => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
                                    'pending'   => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                    'cancelled' => 'bg-red-500/10 text-red-400 border-red-500/20',
                                    default     => 'bg-slate-800 text-slate-400 border-slate-700',
                                };
                                $sLabel = match($ticket->status) {
                                    'active'    => 'Aktif',
                                    'used'      => 'Hadir',
                                    'pending'   => 'Pending',
                                    'cancelled' => 'Dibatalkan / Ditolak',
                                    default     => $ticket->status,
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $sClass }}">
                                {{ $sLabel }}
                            </span>
                            @if($ticket->status === 'used')
                                <span class="mt-1 block whitespace-nowrap text-[10px] text-sky-300">
                                    {{ $ticket->check_in_method === 'manual' ? 'Dicatat Penyelenggara/EO' : ($ticket->check_in_method === 'qr' ? 'Validasi QR' : 'Waktu validasi') }}:
                                    @if($ticket->used_at)
                                        <time datetime="{{ $ticket->used_at->toIso8601String() }}">{{ $ticket->used_at->timezone('Asia/Makassar')->format('d/m/Y H:i:s') }} WITA</time>
                                    @else
                                        belum tercatat
                                    @endif
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-xs font-semibold text-white">
                            @if($ticket->payment)
                                Rp{{ number_format($ticket->payment->amount, 0, ',', '.') }}
                            @else
                                <span class="text-emerald-400">Gratis</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-right">
                            <div class="flex flex-col items-end gap-2">
                            @if($event->acceptsCheckIn())
                            <a href="{{ route('organizer.events.participants.edit', [$event, $ticket]) }}" class="inline-flex min-h-11 items-center whitespace-nowrap rounded-lg border border-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">Edit Peserta</a>
                            @if($ticket->status === 'pending')
                                @if($event->ticket_price > 0 || $ticket->payment?->amount > 0)
                                    <span class="text-[10px] text-amber-500 font-semibold">Menunggu Bayar</span>
                                @else
                                    <div class="inline-flex items-center gap-1.5">
                                        <form method="POST" action="{{ route('organizer.events.participants.approve', [$event, $ticket]) }}">
                                            @csrf
                                            <button type="submit"
                                                class="px-3 py-1.5 text-[10px] font-bold text-white bg-[#ff6b00] hover:bg-[#e05e00] rounded-lg transition">
                                                {{ $event->ticket_price > 0 ? 'Setujui & Bayar' : 'Setujui' }}
                                            </button>
                                        </form>
                                        <button type="button" onclick="openRejectModal({{ $ticket->id }})"
                                                class="px-3 py-1.5 text-[10px] font-bold text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg hover:bg-red-500/20 transition">
                                            Tolak
                                        </button>
                                    </div>
                                @endif
                            @endif
                            @else
                                <span class="text-xs text-slate-400">Arsip peserta</span>
                            @endif
                            </div>
                        </td>
                    </tr>
                    <tr class="hidden" id="detail-{{ $ticket->id }}">
                        <td colspan="7" class="px-8 py-5 bg-[#080c14]/40 border-t border-slate-800/40">
                            @php $answers = $ticket->custom_answers ?? []; @endphp
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-3 text-xs">
                                @if($answers)
                                    @foreach($answers as $label => $value)
                                    <div>
                                        <span class="text-slate-500">{{ $label }}</span>
                                        <p class="text-white font-medium mt-0.5">{{ $value }}</p>
                                    </div>
                                    @endforeach
                                @endif
                                @if(filled($ticket->notes))
                                <div class="min-w-0 sm:col-span-2">
                                    <span class="text-slate-500">Catatan Pendaftaran</span>
                                    <p class="mt-0.5 whitespace-pre-wrap break-words text-white">{{ $ticket->notes }}</p>
                                </div>
                                @endif
                                @if($ticket->status === 'used')
                                <div>
                                    <span class="text-slate-500">{{ $ticket->check_in_method === 'manual' ? 'Waktu Kehadiran Manual' : 'Waktu Validasi' }}</span>
                                    <p class="mt-0.5 text-white">{{ $ticket->used_at ? $ticket->used_at->timezone('Asia/Makassar')->format('d/m/Y H:i:s').' WITA' : 'Belum tercatat' }}</p>
                                </div>
                                @endif
                                @if($ticket->status === 'cancelled' && $ticket->cancellation_reason)
                                <div class="min-w-0 sm:col-span-2">
                                    <span class="text-slate-500">Alasan Pembatalan / Penolakan</span>
                                    <p class="mt-0.5 whitespace-pre-wrap break-words text-white">{{ $ticket->cancellation_reason }}</p>
                                    @if($ticket->cancelled_at)<p class="mt-1 text-slate-400">{{ $ticket->cancelled_at->timezone('Asia/Makassar')->format('d/m/Y H:i:s') }} WITA</p>@endif
                                </div>
                                @endif
                                <div>
                                    <span class="text-slate-500">Tanggal Daftar</span>
                                    <p class="text-white font-medium mt-0.5">{{ $ticket->created_at->translatedFormat('d F Y, H:i') }} WITA</p>
                                </div>
                                @if($ticket->payment)
                                <div>
                                    <span class="text-slate-500">Status Pembayaran</span>
                                    <p class="text-white font-medium mt-0.5">
                                        @if($ticket->payment->status === 'confirmed')
                                            <span class="text-emerald-400">Lunas</span>
                                        @elseif($ticket->payment->status === 'pending')
                                            <span class="text-amber-400">Menunggu</span>
                                        @else
                                            {{ $ticket->payment->status }}
                                        @endif
                                    </p>
                                </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($tickets->hasPages())
        <div class="px-5 py-3 border-t border-slate-800/80 flex justify-center bg-[#080c14]">
            {{ $tickets->appends(request()->query())->links() }}
        </div>
        @endif
        @endif
    </div>
</div>

<!-- Reject Modal -->
<div id="reject-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="bg-dark-card rounded-xl w-full max-w-md p-6 border border-slate-800">
        <h3 class="text-base font-bold text-white mb-1">Tolak Pendaftaran</h3>
        <p class="text-xs text-slate-400 mb-4">Masukkan alasan penolakan agar peserta tahu.</p>

        <form id="reject-form" method="POST" action="">
            @csrf
            <textarea name="notes" rows="3" required
                      placeholder="Contoh: Data tidak lengkap atau bukti transfer tidak valid."
                      class="w-full px-4 py-3 bg-[#080c14] border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#ff6b00] resize-none mb-4"></textarea>
            <div class="flex gap-3">
                <button type="submit"
                        class="flex-1 py-2.5 rounded-xl text-xs font-bold text-white bg-red-600 hover:bg-red-700 transition">
                    Tolak
                </button>
                <button type="button" onclick="closeRejectModal()"
                        class="flex-1 py-2.5 rounded-xl text-xs font-semibold text-slate-400 border border-slate-800 hover:bg-slate-800/30 hover:text-white transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const rejectRoute = "{{ route('organizer.events.participants.reject', [$event, '__TICKET__']) }}";

    function toggleDetail(ticketId) {
        const detail = document.getElementById('detail-' + ticketId);
        const row = detail.previousElementSibling;
        const chevron = row.querySelector('.chevron-icon');
        detail.classList.toggle('hidden');
        if (chevron) {
            chevron.style.transform = detail.classList.contains('hidden') ? '' : 'rotate(180deg)';
        }
    }

    function openRejectModal(ticketId) {
        document.getElementById('reject-form').action = rejectRoute.replace('__TICKET__', ticketId);
        document.getElementById('reject-modal').classList.remove('hidden');
        document.getElementById('reject-modal').classList.add('flex');
    }

    function closeRejectModal() {
        document.getElementById('reject-modal').classList.add('hidden');
        document.getElementById('reject-modal').classList.remove('flex');
    }

    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('reject-modal');
        if (modal) modal.addEventListener('click', function(e) {
            if (e.target === this) closeRejectModal();
        });
        document.querySelectorAll('.ticket-row td:last-child').forEach(td => {
            td.addEventListener('click', e => e.stopPropagation());
        });
    });
</script>
@endpush
