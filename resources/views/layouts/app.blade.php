<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $metaDescription ?? 'Platform Sistem Informasi Manajemen Event & E-Ticketing QR-Code Kegiatan Komunitas Olahraga di Kota Manado.' }}">
    <title>@yield('title', 'SportManado') – SportManado</title>

    <!-- Local application assets -->
    @include('layouts._assets')

    <style>
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #ff6b00; border-radius: 3px; }
    </style>


    @stack('styles')
</head><body class="bg-[#080c14] text-slate-100 font-body-md min-h-screen flex flex-col antialiased overflow-x-hidden">

<!-- ===================== NAVBAR ===================== -->
<header class="sticky top-0 z-50 h-20 flex items-center border-b border-slate-800/80 transition-all duration-200 bg-dark-bg/85 backdrop-blur-md shadow-xl text-white">
    @php
        $linkActive = 'text-accent border-b-2 border-accent pb-1 font-bold';
        $linkInactive = 'text-slate-300 font-medium hover:text-accent';
        $mobileActive = 'text-accent bg-slate-900/60 font-bold';
        $mobileInactive = 'text-slate-300 hover:text-white hover:bg-slate-900/40';
        $navEventRequest = auth()->check() && auth()->user()->role === 'user'
            ? \App\Models\EventRegistrationRequest::where('user_id', auth()->id())
                ->orderByDesc('created_at')
                ->first()
            : null;
    @endphp
    <nav class="flex justify-between items-center w-full px-4 md:px-8 max-w-7xl mx-auto">

        <!-- Logo -->
        <a href="{{ route('home') }}" class="flex items-center gap-2 group">
            <span class="text-headline-md font-headline-md font-extrabold tracking-tight transition-colors duration-200 text-white"><span class="text-[#ff6b00]">Sport</span>Manado</span>
        </a>

        <!-- Desktop Nav Links -->
        <div class="hidden md:flex gap-6 items-center">

            @auth
                @if(auth()->user()->role === 'user')
                    <a href="{{ route('dashboard') }}"
                       class="{{ request()->routeIs('dashboard') ? $linkActive : $linkInactive }} transition-colors duration-200 text-label-md">
                        Dashboard
                    </a>

                    <a href="{{ route('events.index') }}"
                       class="{{ request()->routeIs('events.index') || request()->routeIs('events.show') ? $linkActive : $linkInactive }} transition-colors duration-200 text-label-md">
                        Event
                    </a>

                    <a href="{{ route('tickets.index') }}"
                       class="{{ request()->routeIs('tickets.*') ? $linkActive : $linkInactive }} transition-colors duration-200 text-label-md">
                        Tiket
                    </a>

                    <a href="{{ route('pages.panduan') }}"
                       class="{{ request()->routeIs('pages.panduan') ? $linkActive : $linkInactive }} transition-colors duration-200 text-label-md">
                        Panduan
                    </a>

                    <a href="{{ route('pages.galeri') }}"
                       class="{{ request()->routeIs('pages.galeri') ? $linkActive : $linkInactive }} transition-colors duration-200 text-label-md">
                        Galeri
                    </a>
                @elseif(auth()->user()->role === 'organizer')
                    <a href="{{ route('organizer.dashboard') }}"
                       class="{{ request()->routeIs('organizer.dashboard') ? $linkActive : $linkInactive }} transition-colors duration-200 text-label-md">
                        Dashboard Komunitas
                    </a>
                    <a href="{{ route('organizer.events.index') }}"
                       class="{{ request()->routeIs('organizer.events.*') ? $linkActive : $linkInactive }} transition-colors duration-200 text-label-md">
                        Management Event
                    </a>
                @elseif(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.dashboard') }}"
                       class="{{ request()->routeIs('admin.dashboard') ? $linkActive : $linkInactive }} transition-colors duration-200 text-label-md">
                        Admin Panel
                    </a>
                @endif
            @endauth
        </div>

        <!-- Right side -->
            <div class="flex items-center gap-3">
                @auth
                    <x-notification-link />
                    <!-- User Profile Dropdown -->
                    <div class="hidden md:block relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.away="open = false"
                                class="flex items-center gap-2 bg-accent/10 text-accent px-3 py-1.5 rounded-full cursor-pointer hover:bg-accent/20 transition-all border border-accent/20 focus:outline-none">
                            <span class="text-label-md font-bold hidden lg:inline-block max-w-[160px] truncate text-accent">{{ auth()->user()->name }}</span>
                            <div class="w-8 h-8 shrink-0 rounded-full bg-accent flex items-center justify-center text-xs font-bold text-white overflow-hidden">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <svg class="w-4 h-4 text-accent transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Dropdown Panel -->
                        <div x-show="open" x-transition class="absolute right-0 mt-2 w-52 rounded-xl bg-dark-card border border-slate-800 shadow-2xl overflow-hidden z-50">
                            <div class="px-4 py-3 border-b border-slate-800">
                                <p class="text-xs text-slate-500">Masuk sebagai</p>
                                <p class="text-sm font-semibold text-slate-300 truncate">{{ auth()->user()->email }}</p>
                            </div>
                            <div class="py-1">
                                @if(auth()->user()->role === 'organizer')
                                    <a href="{{ route('organizer.dashboard') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-300 hover:bg-white/5 transition-all">
                                        <span class="material-symbols-outlined text-sm">dashboard</span> Dashboard Penyelenggara/EO
                                    </a>
                                @elseif(auth()->user()->role === 'admin')
                                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-300 hover:bg-white/5 transition-all">
                                        <span class="material-symbols-outlined text-sm">admin_panel_settings</span> Admin Panel
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-red-400 hover:bg-red-500/10 transition-all text-left">
                                        <span class="material-symbols-outlined text-sm">logout</span> Keluar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="hidden md:flex bg-accent hover:bg-accent-hover text-white font-bold rounded-xl transition-all px-5 py-2 items-center gap-1.5 text-label-md">
                        <span>Masuk</span>
                        <span class="material-symbols-outlined text-sm">login</span>
                    </a>
                @endauth

                <!-- Mobile menu button -->
                <button id="mobile-menu-btn" class="md:hidden p-2 rounded-lg transition-all text-slate-400 hover:text-accent hover:bg-white/5">
                <svg class="w-5 h-5" id="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </nav>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden md:hidden absolute top-full left-0 w-full z-40 border-t shadow-lg border-slate-800 bg-dark-card text-white max-h-[calc(100vh-5rem)] overflow-y-auto">
        <div class="px-4 py-3 space-y-1">
            @auth
                <div class="flex items-center gap-3 px-3 py-3 border-b border-slate-800/60 mb-2">
                    <div class="w-10 h-10 rounded-full bg-accent flex items-center justify-center text-sm font-bold text-white shrink-0">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>

                @if(auth()->user()->role === 'user')
                    <a href="{{ route('dashboard') }}"
                       class="block px-3 py-2.5 rounded-lg text-label-md transition-all {{ request()->routeIs('dashboard') ? $mobileActive : $mobileInactive }}">
                        Dashboard
                    </a>
                    <a href="{{ route('events.index') }}"
                       class="block px-3 py-2.5 rounded-lg text-label-md transition-all {{ request()->routeIs('events.index') || request()->routeIs('events.show') ? $mobileActive : $mobileInactive }}">
                        Event
                    </a>
                    <a href="{{ route('tickets.index') }}"
                       class="block px-3 py-2.5 rounded-lg text-label-md transition-all {{ request()->routeIs('tickets.*') ? $mobileActive : $mobileInactive }}">
                        Tiket
                    </a>
                    <a href="{{ route('pages.panduan') }}"
                       class="block px-3 py-2.5 rounded-lg text-label-md transition-all {{ request()->routeIs('pages.panduan') ? $mobileActive : $mobileInactive }}">
                        Panduan
                    </a>
                    <a href="{{ route('pages.galeri') }}"
                       class="block px-3 py-2.5 rounded-lg text-label-md transition-all {{ request()->routeIs('pages.galeri') ? $mobileActive : $mobileInactive }}">
                        Galeri
                    </a>
                @elseif(auth()->user()->role === 'organizer')
                    <a href="{{ route('organizer.dashboard') }}"
                       class="block px-3 py-2.5 rounded-lg text-label-md transition-all {{ request()->routeIs('organizer.dashboard') ? $mobileActive : $mobileInactive }}">
                        Dashboard Komunitas
                    </a>
                    <a href="{{ route('organizer.events.index') }}"
                       class="block px-3 py-2.5 rounded-lg text-label-md transition-all {{ request()->routeIs('organizer.events.*') ? $mobileActive : $mobileInactive }}">
                        Management Event
                    </a>

                @elseif(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.dashboard') }}"
                       class="block px-3 py-2.5 rounded-lg text-label-md transition-all {{ request()->routeIs('admin.dashboard') ? $mobileActive : $mobileInactive }}">
                        Admin Panel
                    </a>
                @endif

                <div class="pt-2 mt-2 border-t border-slate-800/60">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 w-full px-3 py-2.5 rounded-lg text-label-md text-red-400 hover:bg-red-500/10 transition-all">
                            <span class="material-symbols-outlined text-sm">logout</span>
                            Keluar
                        </button>
                    </form>
                </div>
            @else
                <div class="pt-2 pb-1 border-t border-slate-100 mt-2">
                    <a href="{{ route('login') }}" class="flex items-center justify-center gap-2 w-full py-2.5 px-4 bg-[#FF6B00] hover:bg-[#e05e00] text-white font-bold rounded-xl shadow-md transition-all text-label-md">
                        <span>Masuk</span>
                        <span class="material-symbols-outlined text-sm">login</span>
                    </a>
                </div>
            @endauth
        </div>
    </div>
</header>

<!-- ===================== FLASH ALERTS ===================== -->
@if(session('success') || session('error') || session('info') || session('warning'))
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
    @foreach(['success' => ['bg-emerald-500/10 border-emerald-500/20 text-emerald-400', '✓'], 'error' => ['bg-red-500/10 border-red-500/20 text-red-400', '✕'], 'info' => ['bg-blue-500/10 border-blue-500/20 text-blue-400', 'ℹ'], 'warning' => ['bg-amber-500/10 border-amber-500/20 text-amber-400', '⚠']] as $type => $cfg)
        @if(session($type))
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl border {{ $cfg[0] }} mb-3 transition-all" role="alert">
            <span class="text-lg leading-none">{{ $cfg[1] }}</span>
            <p class="text-sm font-medium">{{ session($type) }}</p>
            <button onclick="this.parentElement.remove()" class="ml-auto text-current opacity-60 hover:opacity-100 transition-opacity">✕</button>
        </div>
        @endif
    @endforeach
</div>
@endif

<!-- ===================== MAIN CONTENT ===================== -->
<main class="flex-1">
    {{ $slot ?? '' }}
    @yield('content')
</main>

<!-- ===================== FOOTER ===================== -->
<footer class="w-full py-8 px-4 md:px-8 mt-auto border-t border-slate-900 bg-slate-950 text-slate-300 transition-all duration-200">
    <div class="max-w-7xl mx-auto space-y-4 text-sm">
        <!-- Bottom Footer Details -->
        <div class="flex flex-col md:flex-row justify-between items-center gap-2 text-xs text-slate-500">
            <span>Platform Event Olahraga di Manado</span>
        </div>
    </div>
</footer>

<!-- Alpine.js for dropdown -->


<script>
    // Mobile menu toggle
    const btn = document.getElementById('mobile-menu-btn');
    const menu = document.getElementById('mobile-menu');
    if (btn && menu) {
        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
            if (menu.classList.contains('hidden')) {
                menu.scrollTop = 0;
            }
        });
    }
</script>

@include('partials.search-behavior')
@include('partials.notification-behavior')
@stack('scripts')
</body>
</html>
