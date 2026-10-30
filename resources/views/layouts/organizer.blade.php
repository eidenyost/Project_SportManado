<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard Penyelenggara/EO') – SportManado</title>

    <!-- Local application assets -->
    @include('layouts._assets')


    <style>
        body { font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #080c14; }
        ::-webkit-scrollbar-thumb { background: #ff6b00; border-radius: 4px; }

        .sidebar-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 14px; border-radius: 10px;
            font-size: .875rem; font-weight: 500;
            color: #94a3b8; transition: all .2s ease;
            position: relative;
        }
        .sidebar-item:hover { background: rgba(255, 255, 255, 0.04); color: #fff; }
        .sidebar-item.active { background: rgba(255, 107, 0, 0.1); color: #ff6b00; }
        .sidebar-item.active::before {
            content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%);
            width: 3px; height: 20px; border-radius: 0 4px 4px 0; background: #ff6b00;
        }

        .sidebar-sub-item {
            display: flex; align-items: center; gap: 8px;
            padding: 6px 12px; border-radius: 8px;
            font-size: .8rem; font-weight: 500;
            color: #94a3b8; transition: all .2s ease;
        }
        .sidebar-sub-item:hover { background: rgba(255,255,255,.04); color: #fff; }
        .sidebar-sub-item.active { color: #ff6b00; }

        @media (min-width: 768px) {
            #sidebar { transform: translateX(0) !important; }
            #main-content { margin-left: calc(16rem + 1px) !important; }
        }
        @media (max-width: 767px) {
            #sidebar { transform: translateX(-100%); }
            #main-content { margin-left: 0 !important; }
        }
    </style>

    @stack('styles')
</head>
<body class="bg-[#080c14] text-slate-100 min-h-screen antialiased overflow-x-hidden">

<!-- ===================== SIDEBAR ===================== -->
<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-[#0d1527] border-r border-slate-800/80 flex flex-col transition-transform duration-300 ease-in-out">

    <!-- Sidebar Header -->
    <div class="flex items-center justify-between px-5 py-5 border-b border-slate-800/60 w-full">
        <div>
            <span class="text-lg font-display font-extrabold tracking-tight text-white"><span class="text-[#ff6b00]">Sport</span>Manado</span>
            <p class="text-xs text-slate-400 -mt-0.5">Panel Penyelenggara/EO</p>
        </div>
        <!-- Close button on mobile -->
        <button id="sidebar-close" class="md:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-all flex items-center justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- Organizer Info -->
    <div class="px-5 py-4 border-b border-slate-800/60">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-[#ff6b00] flex items-center justify-center text-sm font-bold text-white">
                {{ strtoupper(substr(auth()->user()->name ?? 'O', 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name ?? 'Penyelenggara/EO' }}</p>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-[#ff6b00]/10 text-[#ff6b00] border border-[#ff6b00]/20">Penyelenggara/EO</span>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <p class="px-3 mb-2 text-xs font-semibold text-slate-500">Menu</p>

        <a href="{{ route('organizer.dashboard') }}"
           class="sidebar-item {{ request()->routeIs('organizer.dashboard') ? 'active' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Dashboard
        </a>

        @php
            $eventRoutes = ['organizer.events.index', 'organizer.events.edit', 'organizer.events.manage', 'organizer.events.participants', 'organizer.events.scan', 'organizer.finances.*'];
            $isInEvent = request()->routeIs($eventRoutes);
            $cEvent = null;
            if ($isInEvent && !request()->routeIs('organizer.events.index') && !request()->routeIs('organizer.events.edit') && !request()->routeIs('organizer.events.create') && !request()->routeIs('organizer.events.store') && !request()->routeIs('organizer.events.update') && !request()->routeIs('organizer.events.destroy') && !request()->routeIs('organizer.events.manage')) {
                $cEvent = request()->route('event');
            }
        @endphp
        <a href="{{ route('organizer.events.index') }}"
            class="sidebar-item {{ $isInEvent ? 'active' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Kelola Event
        </a>

        @if($cEvent)
        <div class="ml-4 space-y-0.5 border-l border-slate-800 pl-3 mb-1">
            <p class="text-[10px] text-slate-500 font-semibold truncate max-w-[160px] px-2 py-1 leading-tight">{{ $cEvent->title }}</p>
            <a href="{{ route('organizer.events.participants', $cEvent) }}"
               class="sidebar-sub-item {{ request()->routeIs('organizer.events.participants') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                Peserta
            </a>
            @if($cEvent->acceptsCheckIn())
            <a href="{{ route('organizer.events.scan', $cEvent) }}"
               class="sidebar-sub-item {{ request()->routeIs('organizer.events.scan') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                Scan QR
            </a>
            @endif
            @if(in_array($cEvent->status, ['active','completed'], true))
            <a href="{{ route('organizer.finances.finance', $cEvent) }}"
               class="sidebar-sub-item {{ request()->routeIs('organizer.finances.finance') || request()->routeIs('organizer.finances.withdraw.*') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Keuangan
            </a>
            @endif
        </div>
        @endif

        <a href="{{ route('organizer.events.create') }}"
            class="sidebar-item {{ request()->routeIs('organizer.events.create') ? 'active' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Buat Event
        </a>

        <a href="{{ route('notifications.index') }}"
            class="sidebar-item {{ request()->routeIs('organizer.notifications.*') ? 'active' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            Notifikasi
            @php
                $unreadNotifCount = \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count();
            @endphp
            <span id="notif-badge" class="ml-auto {{ $unreadNotifCount > 0 ? '' : 'hidden' }} bg-[#ff6b00] text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center leading-tight">
                <span id="notif-count">{{ $unreadNotifCount > 99 ? '99+' : $unreadNotifCount }}</span>
            </span>
        </a>

    </nav>

    <!-- Sidebar Footer -->
    <div class="px-3 py-4 border-t border-slate-800/60">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="sidebar-item w-full text-red-500 hover:bg-red-950/30 hover:text-red-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Keluar
            </button>
        </form>
    </div>
</aside>

<!-- Sidebar Overlay (mobile) -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black/60 z-30 hidden"></div>

<!-- ===================== MAIN WRAPPER ===================== -->
<!-- margin-left diatur via CSS @media di atas, bukan JS/Tailwind class -->
<div id="main-content" class="flex flex-col min-h-screen transition-all duration-300 ease-in-out">

    <!-- Floating Mobile Sidebar Toggle -->
    <button id="sidebar-toggle" class="fixed top-4 left-4 z-30 p-2.5 bg-[#0d1527]/90 backdrop-blur border border-slate-800/80 text-slate-400 hover:text-white rounded-xl shadow-xl md:hidden flex items-center justify-center transition-all">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    <!-- Flash Alerts -->
    @if(session('success') || session('error') || session('info') || session('warning'))
    <div class="flash-alerts px-4 sm:px-6 pt-4">
        @foreach(['success' => 'emerald', 'error' => 'red', 'info' => 'blue', 'warning' => 'amber'] as $type => $color)
            @if(session($type))
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl border border-{{ $color }}-800/40 bg-{{ $color }}-950/30 text-{{ $color }}-300 mb-2 animate-fade-in" role="alert">
                <p class="text-sm font-medium">{{ session($type) }}</p>
                <button onclick="this.parentElement.remove()" class="ml-auto text-{{ $color }}-400/60 hover:text-{{ $color }}-300 transition-opacity">✕</button>
            </div>
            @endif
        @endforeach
    </div>
    @endif

    <!-- Page Content -->
    <main class="flex-1 w-full min-w-0 px-4 sm:px-6 md:px-8 py-6 pt-16 md:pt-6 animate-fade-in">
        @yield('content')
    </main>


</div>



{{-- Global Lucide init - applies to ALL Penyelenggara/EO pages --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<script defer src="{{ asset('assets/sidebar.js') }}"></script>
@include('partials.search-behavior')
@include('partials.notification-behavior')
@stack('scripts')
</body>
</html>
