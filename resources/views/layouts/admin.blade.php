<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel Kontrol Admin') – SportManado</title>

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
            <p class="text-xs text-slate-400 -mt-0.5">Panel Admin</p>
        </div>
        <button id="sidebar-close" class="md:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-all flex items-center justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- Admin Info -->
    <div class="px-5 py-4 border-b border-slate-800/60">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-[#ff6b00] flex items-center justify-center text-sm font-bold text-white">
                {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name ?? 'Admin' }}</p>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-slate-700 text-slate-200">Admin</span>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <p class="px-3 mb-2 text-xs font-semibold text-slate-500">Menu</p>

        <a href="{{ route('admin.dashboard', ['tab' => 'overview']) }}"
           class="sidebar-item {{ $activeTab === 'overview' ? 'active' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            Dashboard
        </a>

        <a href="{{ route('admin.dashboard', ['tab' => 'organizer_verifications']) }}"
           class="sidebar-item relative {{ $activeTab === 'organizer_verifications' ? 'active' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Verifikasi Penyelenggara/EO
                <span data-badge="eo" class="absolute right-3 px-1.5 py-0.5 text-[9px] rounded-full bg-amber-500/20 text-amber-300 font-bold border border-amber-500/30 {{ $totalPendingOrganizers > 0 ? '' : 'hidden' }}">
                    {{ $totalPendingOrganizers }}
                </span>
        </a>

        <a href="{{ route('admin.dashboard', ['tab' => 'pending_validation']) }}"
           class="sidebar-item relative {{ $activeTab === 'pending_validation' ? 'active' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Persetujuan Event
            @php $totalPendingApprovals = $totalPendingEvents + $totalPendingRequests; @endphp
                <span data-badge="approvals" class="absolute right-3 px-1.5 py-0.5 text-[9px] rounded-full bg-amber-500/20 text-amber-300 font-bold border border-amber-500/30 {{ $totalPendingApprovals > 0 ? '' : 'hidden' }}">
                    {{ $totalPendingApprovals }}
                </span>
        </a>

        <a href="{{ route('admin.dashboard', ['tab' => 'all_events']) }}"
           class="sidebar-item {{ $activeTab === 'all_events' ? 'active' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Semua Event
        </a>

        <a href="{{ route('admin.dashboard', ['tab' => 'gallery']) }}"
           class="sidebar-item {{ $activeTab === 'gallery' ? 'active' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
            </svg>
            Galeri
        </a>

        <a href="{{ route('admin.dashboard', ['tab' => 'manage_users']) }}"
           class="sidebar-item {{ $activeTab === 'manage_users' ? 'active' : '' }}">
             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <circle cx="9" cy="7" r="4"/>
                 <path d="M10 15H6a4 4 0 0 0-4 4v1"/>
                 <circle cx="18" cy="15" r="3"/>
                 <path d="M21.7 13.4l-.9-.3"/>
                 <path d="M17.2 13.4l-.9-.3"/>
                 <path d="M16 15.5l-.5-.8"/>
                 <path d="M20 15.5l-.5-.8"/>
                 <path d="M16 17.5l-.5.8"/>
                 <path d="M20 17.5l-.5.8"/>
                 <path d="M18 18V11"/>
             </svg>
             Kelola Pengguna
        </a>

        <a href="{{ route('admin.dashboard', ['tab' => 'payment_monitoring']) }}"
           class="sidebar-item {{ $activeTab === 'payment_monitoring' ? 'active' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                <line x1="1" y1="10" x2="23" y2="10"/>
            </svg>
            Monitor Transaksi
        </a>

        <a href="{{ route('admin.dashboard', ['tab' => 'withdrawal_requests']) }}"
           class="sidebar-item {{ $activeTab === 'withdrawal_requests' ? 'active' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Verifikasi Penarikan
        </a>
        @php $adminUnread=\App\Models\Notification::where('user_id',auth()->id())->where('is_read',false)->count(); @endphp
        <a href="{{ route('notifications.index') }}" class="sidebar-item relative {{ $activeTab === 'notifications' ? 'active' : '' }}" aria-current="{{ $activeTab === 'notifications' ? 'page' : 'false' }}">
            <svg class="w-5 h-5 shrink-0" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 00-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
            Notifikasi
            <span data-notification-count class="ml-auto rounded-full bg-orange-600 px-2 py-0.5 text-xs text-white {{ $adminUnread ? '' : 'hidden' }}">{{ $adminUnread > 99 ? '99+' : $adminUnread }}</span>
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
<div id="main-content" class="flex flex-col min-h-screen transition-all duration-300 ease-in-out">

    <!-- Floating Mobile Sidebar Toggle -->
    <button id="sidebar-toggle" class="fixed top-4 left-4 z-30 p-2.5 bg-[#0d1527]/90 backdrop-blur border border-slate-800/80 text-slate-400 hover:text-white rounded-xl shadow-xl md:hidden flex items-center justify-center transition-all">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    <!-- Flash Alerts -->
    @if(session('success') || session('error') || session('info') || session('warning'))
    <div class="flash-alerts px-4 sm:px-6 pt-4">
        @foreach(['success' => 'bg-emerald-950/20 border-emerald-900/40 text-emerald-400', 'error' => 'bg-red-950/20 border-red-900/40 text-red-400', 'info' => 'bg-blue-950/20 border-blue-900/40 text-blue-400', 'warning' => 'bg-amber-950/20 border-amber-900/40 text-amber-400'] as $type => $cls)
            @if(session($type))
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl border {{ $cls }} mb-2 animate-fade-in" role="alert">
                <p class="text-sm font-medium">{{ session($type) }}</p>
                <button onclick="this.parentElement.remove()" class="ml-auto opacity-60 hover:opacity-100 transition-opacity">✕</button>
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


<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) lucide.createIcons();
        // Auto-poll pending counts every 30s
        let pollTimer;
        function pollPendingCounts() {
            fetch('{{ route('admin.pending-counts') }}')
                .then(r => r.json())
                .then(data => {
                    const eoBadge = document.querySelector('[data-badge="eo"]');
                    const apBadge = document.querySelector('[data-badge="approvals"]');
                    if (eoBadge) {
                        eoBadge.textContent = data.totalPendingOrganizers;
                        eoBadge.classList.toggle('hidden', data.totalPendingOrganizers < 1);
                    }
                    if (apBadge) {
                        const total = data.totalPendingEvents + data.totalPendingRequests;
                        apBadge.textContent = total;
                        apBadge.classList.toggle('hidden', total < 1);
                    }
                })
                .catch(() => {});
        }
        pollTimer = setInterval(pollPendingCounts, 30000);
    });
</script>

<script defer src="{{ asset('assets/sidebar.js') }}"></script>
@include('partials.search-behavior')
@include('partials.notification-behavior')
@stack('scripts')
</body>
</html>
