<div class="space-y-4 animate-in fade-in duration-300" x-data="{ modalOpen: false, modalImg: '' }">
    <x-search-form :action="route('admin.dashboard')" placeholder="Cari judul event atau permohonan..." />
    @php
        $items = $pendingEventRequests->map(fn($r) => (object)[
            'type' => 'request',
            'data' => $r,
        ])->concat($pendingEvents->map(fn($e) => (object)[
            'type' => 'event',
            'data' => $e,
        ]))->sortByDesc(fn($i) => $i->data->created_at);
    @endphp

    @if($items->isEmpty())
    <div class="bg-dark-card rounded-xl p-12 text-center border border-slate-800/40">
        <p class="text-3xl mb-3">🎉</p>
        <h3 class="text-lg font-bold text-white mb-1">Tidak Ada Persetujuan Tertunda</h3>
        <p class="text-sm text-slate-400">Semua pengajuan event telah diproses.</p>
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($items as $item)
        @php
            if ($item->type === 'request') {
                $d = $item->data;
                $title = $d->event_title;
                $org = $d->organization_name;
                $name = $d->user->name;
                $email = $d->user->email;
                $phone = $d->phone;
                $location = $d->event_location;
                $eventDate = $d->event_date;
                $price = $d->ticket_price;
                $maxPax = $d->max_participants;
                $description = $d->event_description;
                $proposal = $d->proposal_file;
                $permit = $d->permit_file;
                $banner = $d->banner;
                $createdAt = $d->created_at;
                $routeApprove = 'admin.event-requests.approve';
                $routeReject = 'admin.event-requests.reject';
                $typeLabel = 'Pengajuan Baru';
                $typeColor = 'bg-[#ff6b00]/10 text-[#ff6b00] border-[#ff6b00]/20';
            } else {
                $d = $item->data;
                $title = $d->title;
                $org = $d->organizer->organization_name ?? 'Penyelenggara/EO';
                $email = '';
                $phone = $d->organizer->phone ?? '-';
                $location = $d->location;
                $eventDate = $d->event_date;
                $price = $d->ticket_price;
                $maxPax = $d->max_participants;
                $description = $d->description;
                $proposal = $d->proposal;
                $permit = $d->permit;
                $banner = null;
                $createdAt = $d->created_at;
                $routeApprove = 'admin.events.approve';
                $routeReject = 'admin.events.reject';
                $typeLabel = 'Validasi Event';
                $typeColor = 'bg-amber-500/10 text-amber-400 border-amber-500/20';
            }
        @endphp
        <div class="bg-dark-card rounded-xl border border-slate-800/40 overflow-hidden relative flex flex-col">
            <div class="absolute top-0 right-0 w-32 h-32 bg-[#ff6b00]/5 blur-2xl rounded-full pointer-events-none"></div>

            <div class="p-5 relative z-10 flex flex-col flex-1 gap-3">
                <div class="flex items-start justify-between gap-2">
                    <span class="px-2 py-0.5 rounded text-[9px] font-bold {{ $typeColor }}">{{ $typeLabel }}</span>
                    <span class="text-[10px] text-slate-500 shrink-0">{{ $createdAt->diffForHumans() }}</span>
                </div>

                <div class="min-w-0">
                    <h3 class="font-display font-bold text-sm text-white line-clamp-2 leading-snug">{{ $title }}</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5 truncate">🏢 {{ $org }}</p>
                </div>

                <div class="space-y-1 text-[11px] text-slate-400">
                    @if($item->type === 'request')
                    <div class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <span class="truncate">{{ $name }}</span>
                    </div>
                    @endif
                    <div class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        <span class="truncate">{{ $phone }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <span class="truncate">{{ $location }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <span>{{ $eventDate->translatedFormat('d F Y') }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                        @if($price > 0)
                            <span class="font-semibold text-[#ff6b00]">Rp {{ number_format($price, 0, ',', '.') }}</span>
                        @else
                            <span class="font-semibold text-emerald-400">Gratis</span>
                        @endif
                        <span class="text-slate-600">•</span>
                        <span>{{ number_format($maxPax) }} peserta</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="bg-[#080c14] border border-slate-800 rounded-xl p-3 text-center">
                        <p class="text-[9px] text-slate-500 uppercase font-bold mb-1">Proposal</p>
                        @if($proposal)
                            <a href="{{ route('admin.download-document', ['path' => $proposal]) }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-[#ff6b00] hover:underline font-semibold mx-auto">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="7 10 12 15 17 10"/>
                                    <line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                                Unduh
                            </a>
                        @else
                            <span class="text-[11px] text-slate-600 italic">-</span>
                        @endif
                    </div>
                    <div class="bg-[#080c14] border border-slate-800 rounded-xl p-3 text-center">
                        <p class="text-[9px] text-slate-500 uppercase font-bold mb-1">{{ $item->type === 'event' ? 'Izin' : 'Surat Izin' }}</p>
                        @if($permit)
                            <a href="{{ route('admin.download-document', ['path' => $permit]) }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-[#ff6b00] hover:underline font-semibold mx-auto">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="7 10 12 15 17 10"/>
                                    <line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                                Unduh
                            </a>
                        @else
                            <span class="text-[11px] text-slate-600 italic">-</span>
                        @endif
                    </div>
                </div>

                @if($item->type === 'event' && $d->ktp_image)
                <button @click="modalImg = '{{ route('admin.download-document', ['path' => $d->ktp_image]) }}'; modalOpen = true"
                        class="text-[11px] text-[#ff6b00] hover:underline font-semibold flex items-center gap-1.5 bg-[#080c14]/50 border border-slate-800/60 rounded-xl p-2.5 text-left">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                        <circle cx="12" cy="13" r="4"/>
                    </svg>
                    <span class="flex-1">KTP Penanggung Jawab</span>
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
                @endif

                @if($item->type === 'request' && $banner)
                <div class="relative rounded-xl overflow-hidden border border-slate-800 h-16 bg-[#080c14]">
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($banner) }}" class="w-full h-full object-cover">
                </div>
                @endif

                @if($description)
                <div class="text-[11px] text-slate-400 bg-[#080c14]/50 border border-slate-800/60 rounded-xl p-3 leading-relaxed line-clamp-2">
                    {{ $description }}
                </div>
                @endif

                <div class="flex flex-col gap-2 mt-auto pt-2 border-t border-slate-800/60">
                    <form action="{{ route($routeApprove, $item->data) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition-all flex items-center justify-center gap-1.5 shadow-sm"
                                onclick="return confirm('{{ $item->type === 'request' ? "Setujui pengajuan ini? Akun pengguna akan di-upgrade menjadi Penyelenggara/EO." : "Setujui event ini?" }}')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            Setujui
                        </button>
                    </form>

                    <div x-data="{ showReject: false }" class="relative">
                        <button @click="showReject = !showReject" class="w-full py-2 bg-red-500/10 border border-red-500/20 hover:bg-red-500/20 text-red-400 font-bold rounded-xl text-xs transition-all flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="15" y1="9" x2="9" y2="15"/>
                                <line x1="9" y1="9" x2="15" y2="15"/>
                            </svg>
                            Tolak
                        </button>

                        <div x-show="showReject" class="absolute bottom-full mb-2 left-0 right-0 p-3 bg-dark-card border border-slate-800 rounded-xl shadow-xl z-20" x-transition style="display: none;">
                            <form action="{{ route($routeReject, $item->data) }}" method="POST">
                                @csrf
                                <label class="block text-[10px] font-bold text-white mb-1.5">ALASAN PENOLAKAN</label>
                                <textarea name="admin_notes" rows="2" class="w-full p-2 bg-[#080c14] text-white border border-slate-800 rounded-lg text-xs focus:border-[#ff6b00] focus:ring-[#ff6b00]/30 focus:outline-none mb-2" placeholder="Wajib diisi..."></textarea>
                                <div class="flex justify-end gap-1.5">
                                    <button type="button" @click="showReject = false" class="px-2.5 py-1 text-[10px] text-slate-400 hover:text-white">Batal</button>
                                    <button type="submit" class="px-3 py-1 text-[10px] bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition-all" onclick="return confirm('Yakin ingin menolak?')">Kirim</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <div x-show="modalOpen"
         x-cloak
         @keydown.escape.window="modalOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/85 backdrop-blur-sm p-4"
         style="display: none;">
        <div @click.away="modalOpen = false" class="relative max-w-2xl w-full">
            <button @click="modalOpen = false" class="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-[#ff6b00] text-white flex items-center justify-center z-10 shadow-lg hover:bg-[#e05e00] transition-colors text-sm font-bold">✕</button>
            <img :src="modalImg" class="w-full rounded-xl border border-slate-800/60 shadow-2xl bg-[#080c14]">
        </div>
    </div>
</div>
