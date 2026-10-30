<div class="space-y-4 animate-in fade-in duration-300">
    @php $rc = $roleCounts instanceof \Illuminate\Support\Collection ? $roleCounts->toArray() : (is_array($roleCounts) ? $roleCounts : []); @endphp

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4">
            <span class="text-[11px] text-slate-500 font-medium">Total Pengguna</span>
            <p class="font-display font-black text-2xl tracking-tight text-white tabular-nums mt-1">{{ array_sum($rc) }}</p>
        </div>
        <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4">
            <span class="text-[11px] text-slate-500 font-medium">Peserta</span>
            <p class="font-display font-black text-2xl tracking-tight text-emerald-400 tabular-nums mt-1">{{ $rc['user'] ?? 0 }}</p>
        </div>
        <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4">
            <span class="text-[11px] text-slate-500 font-medium">Penyelenggara/EO</span>
            <p class="font-display font-black text-2xl tracking-tight text-amber-400 tabular-nums mt-1">{{ $rc['organizer'] ?? 0 }}</p>
        </div>
        <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4">
            <span class="text-[11px] text-slate-500 font-medium">Admin</span>
            <p class="font-display font-black text-2xl tracking-tight text-[#ff6b00] tabular-nums mt-1">{{ $rc['admin'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Filter + Search -->
    <div class="bg-dark-card rounded-xl border border-slate-800/40 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800/60 flex flex-col md:flex-row justify-between items-center gap-4 bg-[#080c14]">
            <div class="flex flex-wrap gap-1.5">
                @php $currentRole = request('role'); @endphp
                <a href="{{ route('admin.dashboard', array_merge(request()->except('role', 'page'), ['tab' => 'manage_users', 'role' => null])) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition
                          {{ !$currentRole ? 'bg-[#ff6b00] text-white' : 'bg-dark-card text-slate-400 border border-slate-800/60 hover:text-white hover:border-slate-600' }}">
                    Semua ({{ array_sum($rc) }})
                </a>
                @foreach(['user' => 'Peserta', 'organizer' => 'Penyelenggara/EO', 'admin' => 'Admin'] as $val => $label)
                    @if(isset($rc[$val]) && $rc[$val] > 0)
                    <a href="{{ route('admin.dashboard', array_merge(request()->except('role', 'page'), ['tab' => 'manage_users', 'role' => $val])) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition
                              {{ $currentRole === $val ? 'bg-[#ff6b00] text-white' : 'bg-dark-card text-slate-400 border border-slate-800/60 hover:text-white hover:border-slate-600' }}">
                        {{ $label }} ({{ $rc[$val] }})
                    </a>
                    @endif
                @endforeach
            </div>

            <form method="GET" action="{{ route('admin.dashboard') }}" class="relative w-full md:w-72">
                <input type="hidden" name="tab" value="manage_users">
                @if($currentRole)
                <input type="hidden" name="role" value="{{ $currentRole }}">
                @endif
                <svg class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nama atau email..."
                       class="w-full pl-9 pr-4 py-2 bg-[#080c14] rounded-xl border border-slate-800/60 outline-none focus:border-[#ff6b00] text-xs text-white placeholder-slate-500 transition-colors">
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-[#080c14] border-b border-slate-800/60 text-slate-300 uppercase tracking-wider font-bold">
                    <tr>
                        <th class="p-4 font-bold">Pengguna</th>
                        <th class="p-4 font-bold">Email</th>
                        <th class="p-4 font-bold">Role</th>
                        <th class="p-4 font-bold">Ubah Hak Peran</th>
                        <th class="p-4 font-bold text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40 text-slate-200">
                    @forelse($allUsers as $u)
                    <tr class="hover:bg-slate-800/20 transition-colors">
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-[#080c14] flex items-center justify-center text-sm font-bold text-white uppercase shrink-0 border border-slate-800/60">
                                    {{ strtoupper(substr($u->name, 0, 1)) }}
                                </div>
                                <div>
                                    <span class="font-bold text-white block text-xs">{{ $u->name }}</span>
                                    @if($u->id === auth()->id())
                                    <span class="text-[9px] px-1.5 py-0.5 rounded bg-[#ff6b00]/10 text-[#ff6b00] font-bold">Diri Anda</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="p-4 text-slate-400 font-mono">{{ $u->email }}</td>
                        <td class="p-4">
                            @if($u->role === 'admin')
                            <span class="px-2.5 py-0.5 text-[10px] rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20 font-bold">Admin</span>
                            @elseif($u->role === 'organizer')
                            <span class="px-2.5 py-0.5 text-[10px] rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20 font-bold">Penyelenggara/EO</span>
                            @else
                            <span class="px-2.5 py-0.5 text-[10px] rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold">Peserta</span>
                            @endif
                        </td>
                        <td class="p-4">
                            @if($u->id !== auth()->id())
                            <form action="{{ route('admin.users.update-role', $u) }}" method="POST" class="flex items-center gap-1.5" id="role-form-{{ $u->id }}">
                                @csrf
                                @method('PATCH')
                                <select name="role" class="py-1 px-2.5 bg-[#080c14] border border-slate-800 rounded-lg text-xs text-white outline-none focus:border-[#ff6b00] transition-colors cursor-pointer"
                                    onchange="if(confirm('Ubah peran ' + @json($u->name) + ' menjadi ' + (this.value === 'user' ? 'Peserta' : 'Penyelenggara/EO') + '?')) this.form.submit()">
                                    <option value="user" {{ $u->role === 'user' ? 'selected' : '' }}>Peserta</option>
                                    <option value="organizer" {{ $u->role === 'organizer' ? 'selected' : '' }}>Penyelenggara/EO</option>
                                </select>
                            </form>
                            @else
                            <span class="text-slate-400 text-xs italic">Terkunci (Diri Sendiri)</span>
                            @endif
                        </td>
                        <td class="p-4 text-right">
                            <div class="flex justify-end gap-1.5">
                                @if($u->id !== auth()->id())
                                <form action="{{ route('admin.users.delete', $u) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun ' + @json($u->name) + '? Semua data tiket dan eventnya akan terhapus otomatis.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 hover:bg-red-500/10 rounded-lg text-red-400 hover:text-red-300 transition-colors" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                    </button>
                                </form>
                                @else
                                <span class="text-slate-400 text-[10px]">–</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-slate-500">Tidak ada pengguna ditemukan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($allUsers, 'links'))
        <div class="px-4 py-2.5 border-t border-slate-800/60 bg-[#080c14]">
            {{ $allUsers->links() }}
        </div>
        @endif
    </div>
</div>
