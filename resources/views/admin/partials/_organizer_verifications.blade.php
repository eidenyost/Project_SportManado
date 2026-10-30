<div class="space-y-4 animate-in fade-in duration-300" x-data="{ modalOpen: false, modalImg: '' }">
    <x-search-form :action="route('admin.dashboard')" placeholder="Cari nama Penyelenggara/EO, pemilik, atau email..." />
    @if($pendingOrganizers->isEmpty())
    <div class="bg-dark-card rounded-xl p-12 text-center border border-slate-800/40">
        <p class="text-3xl mb-3">📭</p>
        <h3 class="text-lg font-bold text-white mb-1">Tidak Ada Pengajuan Penyelenggara/EO Baru</h3>
        <p class="text-sm text-slate-400">Belum ada pengguna yang mendaftar sebagai Penyelenggara/EO saat ini.</p>
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($pendingOrganizers as $org)
        <div class="bg-dark-card rounded-xl border border-slate-800/40 overflow-hidden relative flex flex-col">
            <div class="absolute top-0 right-0 w-32 h-32 bg-[#ff6b00]/5 blur-2xl rounded-full pointer-events-none"></div>

            <div class="p-5 relative z-10 flex flex-col flex-1 gap-4">
                <div class="flex items-start gap-3">
                    <div class="w-12 h-12 rounded-xl bg-[#080c14] flex items-center justify-center text-white font-bold text-sm overflow-hidden border border-slate-800 flex-shrink-0">
                        @if($org->logo)
                            <img src="{{ asset('storage/' . $org->logo) }}" alt="Logo {{ $org->organization_name }}" class="w-full h-full object-cover">
                        @else
                            {{ strtoupper(substr($org->organization_name, 0, 2)) }}
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-display font-bold text-sm text-white truncate">{{ $org->organization_name }}</h3>
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20 whitespace-nowrap shrink-0">Pending</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5 truncate">{{ $org->user->name }} ({{ $org->user->email }})</p>
                    </div>
                </div>

                <div class="space-y-1 text-[11px] text-slate-400">
                    <div class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        <span class="truncate">{{ $org->phone }}</span>
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $org->phone) }}" target="_blank" class="text-emerald-400 hover:underline font-semibold inline-flex items-center gap-0.5 shrink-0 ml-auto">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                            </svg>
                            WA
                        </a>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <span>{{ $org->created_at->translatedFormat('d F Y') }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="bg-[#080c14] border border-slate-800 rounded-xl p-3 text-center">
                        <p class="text-[9px] text-slate-500 uppercase font-bold mb-1">Foto KTP</p>
                        @if($org->ktp_path)
                            <button @click="modalImg = '{{ route('admin.download-document', ['path' => $org->ktp_path]) }}'; modalOpen = true" class="inline-flex items-center gap-1 text-[11px] text-[#ff6b00] hover:underline font-semibold mx-auto">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                Lihat
                            </button>
                        @else
                            <span class="text-[11px] text-slate-600 italic">-</span>
                        @endif
                    </div>
                    <div class="bg-[#080c14] border border-slate-800 rounded-xl p-3 text-center">
                        <p class="text-[9px] text-slate-500 uppercase font-bold mb-1">Swafoto</p>
                        @if($org->selfie_path)
                            <button @click="modalImg = '{{ route('admin.download-document', ['path' => $org->selfie_path]) }}'; modalOpen = true" class="inline-flex items-center gap-1 text-[11px] text-[#ff6b00] hover:underline font-semibold mx-auto">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                Lihat
                            </button>
                        @else
                            <span class="text-[11px] text-slate-600 italic">-</span>
                        @endif
                    </div>
                </div>

                @if($org->description)
                <div class="text-[11px] text-slate-400 bg-[#080c14]/50 border border-slate-800/60 rounded-xl p-3 leading-relaxed line-clamp-3">
                    {{ $org->description }}
                </div>
                @endif

                <div class="flex flex-col gap-2 mt-auto pt-2 border-t border-slate-800/60">
                    <form action="{{ route('admin.organizers.approve', $org->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition-all flex items-center justify-center gap-1.5 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            Setujui
                        </button>
                    </form>

                    <div x-data="{ showRejectForm: false }" class="relative">
                        <button @click="showRejectForm = !showRejectForm" class="w-full py-2 bg-red-500/10 border border-red-500/20 hover:bg-red-500/20 text-red-400 font-bold rounded-xl text-xs transition-all flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="15" y1="9" x2="9" y2="15"/>
                                <line x1="9" y1="9" x2="15" y2="15"/>
                            </svg>
                            Tolak
                        </button>

                        <div x-show="showRejectForm" class="absolute bottom-full mb-2 left-0 right-0 p-3 bg-dark-card border border-slate-800 rounded-xl shadow-xl z-20" x-transition style="display: none;">
                            <form action="{{ route('admin.organizers.reject', $org->id) }}" method="POST">
                                @csrf
                                <label class="block text-[10px] font-bold text-white mb-1.5">ALASAN PENOLAKAN</label>
                                <textarea name="admin_notes" rows="2" required class="w-full p-2 bg-[#080c14] text-white border border-slate-800 rounded-lg text-xs focus:border-[#ff6b00] focus:ring-[#ff6b00]/30 focus:outline-none mb-2" placeholder="Wajib diisi..."></textarea>
                                <div class="flex justify-end gap-1.5">
                                    <button type="button" @click="showRejectForm = false" class="px-2.5 py-1 text-[10px] text-slate-400 hover:text-white">Batal</button>
                                    <button type="submit" class="px-3 py-1 text-[10px] bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition-all">Kirim</button>
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

    @if(method_exists($pendingOrganizers, 'links'))
    <div class="px-4 py-2.5 border-t border-slate-800/60 bg-dark-card rounded-xl">
        {{ $pendingOrganizers->links() }}
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
