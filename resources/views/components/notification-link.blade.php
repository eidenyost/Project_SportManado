@auth
@php $notificationCount = \App\Models\Notification::where('user_id', auth()->id())->where('is_read',false)->count(); @endphp
<a href="{{ route('notifications.index') }}" aria-label="Buka notifikasi" class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-300 hover:bg-slate-800">
    <svg aria-hidden="true" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 00-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
    <span class="hidden lg:inline">Notifikasi</span>
    <span data-notification-count class="rounded-full bg-orange-600 px-2 py-0.5 text-xs text-white {{ $notificationCount ? '' : 'hidden' }}">{{ $notificationCount > 99 ? '99+' : $notificationCount }}</span>
</a>
@endauth
