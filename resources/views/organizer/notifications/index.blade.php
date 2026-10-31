@extends(auth()->user()->isAdmin() ? 'layouts.admin' : (auth()->user()->isOrganizer() ? 'layouts.organizer' : 'layouts.app'))

@section('title', 'Notifikasi – SportManado')

@section('content')
<span hidden data-refresh-on-return></span>
<div class="max-w-6xl mx-auto space-y-5 px-4 py-6 sm:px-6">

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-lg font-bold text-white">Notifikasi</h1>
        <p class="text-xs text-slate-400"><span data-unread-total>{{ $totalUnread }}</span> belum dibaca</p>
        @if($totalUnread > 0)
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button type="submit" class="text-xs text-[#ff6b00] hover:text-[#e05e00] font-semibold transition-colors flex items-center gap-1">
                <i data-lucide="check-check" class="w-3.5 h-3.5"></i>
                Tandai Semua Dibaca
            </button>
        </form>
        @endif
    </div>
    <p class="text-sm text-slate-400 -mt-3">Pembaruan pendaftaran, tiket, kegiatan dan transaksi akun Anda.</p>

    <x-search-form :action="url()->current()" placeholder="Cari notifikasi..." />
    <nav aria-label="Filter notifikasi" class="flex gap-2">
        @foreach([''=>'Semua', 'unread'=>'Belum dibaca'] as $value=>$label)
        <a class="inline-flex min-h-11 items-center rounded-lg border border-slate-700 px-3 py-2 text-xs text-white" href="{{ url()->current().'?'.http_build_query(array_merge(request()->except(['filter','page']),['filter'=>$value])) }}">{{ $label }}</a>
        @endforeach
    </nav>
    <!-- Cards -->
    @if($notifications->isEmpty())
    <div class="bg-dark-card border border-slate-800/40 rounded-xl py-16 text-center">
        <i data-lucide="bell-off" class="w-10 h-10 text-slate-700 mx-auto mb-3"></i>
        <p class="text-sm text-slate-500 font-medium">Belum ada notifikasi</p>
        <p class="text-xs text-slate-600 mt-1">Pembaruan kegiatan dan transaksi Anda akan muncul di sini.</p>
    </div>
    @else
    <div class="space-y-4">
        @foreach($notifications as $notif)
        @php
            $iconMap = [
                'event_approved'      => ['icon' => 'calendar-check', 'bg' => 'emerald'],
                'event_rejected'      => ['icon' => 'calendar-x',    'bg' => 'red'],
                'event_completed'     => ['icon' => 'flag',          'bg' => 'blue'],
                'organizer_approved'  => ['icon' => 'user-check',    'bg' => 'emerald'],
                'organizer_rejected'  => ['icon' => 'user-x',        'bg' => 'red'],
                'withdrawal_approved' => ['icon' => 'wallet',        'bg' => 'emerald'],
                'withdrawal_rejected' => ['icon' => 'wallet',        'bg' => 'red'],
            ];
            $meta = $iconMap[$notif->type] ?? ['icon' => 'bell', 'bg' => 'slate'];
            $bgClass = match($meta['bg']) {
                'emerald' => 'bg-emerald-500/20 text-emerald-400',
                'red'     => 'bg-red-500/20 text-red-400',
                'blue'    => 'bg-blue-500/20 text-blue-400',
                default   => 'bg-slate-500/20 text-slate-400',
            };
        @endphp
        <div class="notif-item bg-dark-card border rounded-xl p-5 @if(!$notif->is_read) bg-[#ff6b00]/[0.03] border-t-2 border-t-[#ff6b00] border-slate-800/40 @else border-slate-800/40 @endif"
             data-id="{{ $notif->id }}"
             data-read="{{ $notif->is_read ? '1' : '0' }}">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 {{ $bgClass }}">
                    <i data-lucide="{{ $meta['icon'] }}" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-bold @if(!$notif->is_read) text-white @else text-slate-200 @endif notif-title">{{ $notif->title }}</p>
                            <p class="text-sm text-slate-400 mt-1.5 leading-relaxed break-words">{{ $notif->message }}</p>
                            <p class="text-xs text-slate-500 mt-2">{{ $notif->created_at->diffForHumans() }}</p>
                            @if($notif->destination)<a class="mt-2 inline-flex min-h-11 items-center text-xs font-semibold text-orange-400" href="{{ $notif->destination }}">Lihat detail</a>@endif
                        </div>
                        @if(!$notif->is_read)
                        <button type="button"
                                class="mark-read-btn min-w-11 min-h-11 rounded-lg flex items-center justify-center text-slate-500 hover:text-white hover:bg-slate-800/60 transition-colors shrink-0"
                                title="Tandai dibaca"
                                data-id="{{ $notif->id }}">
                            <i data-lucide="check" class="w-4 h-4"></i>
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if(method_exists($notifications, 'hasPages') && $notifications->hasPages())
    <div class="flex justify-center pt-2">
        {{ $notifications->links() }}
    </div>
    @endif
    @endif

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.mark-read-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.id;
            const item = btn.closest('.notif-item');

            try {
                const res = await fetch('{{ route("notifications.read", ":id") }}'.replace(':id', id), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });

                if (!res.ok) return;
                const result = await res.json();
                if (!result.success || !Number.isInteger(result.count)) return;
                window.dispatchEvent(new CustomEvent('notifications:updated',{detail:{count:result.count}}));

                item.dataset.read = '1';
                item.classList.remove('border-t-2', 'border-t-[#ff6b00]', 'border-slate-800/40');
                item.classList.add('border-slate-800/40');
                btn.remove();

                const title = item.querySelector('.notif-title');
                if (title) title.classList.remove('text-white', 'font-bold');
                if (title) title.classList.add('text-slate-200');

                if (new URL(location.href).searchParams.get('filter') === 'unread') location.reload();
            } catch (_) {}
        });
    });
});
</script>
@endpush
