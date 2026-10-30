@extends('layouts.app')

@section('title', 'Daftar Penyelenggara/EO – SportManado')

@push('styles')
<!-- Flatpickr Datepicker CSS -->
<link rel="stylesheet" href="{{ asset('assets/flatpickr.min.css') }}">
<style>
    .file-drop-zone {
        transition: all 0.2s ease-in-out;
        border: 2px dashed rgba(255, 107, 0, 0.4);
    }
    .file-drop-zone:hover, .file-drop-zone.drag-active {
        border-color: #ff6b00;
        background-color: rgba(255, 107, 0, 0.08);
    }
    .file-drop-zone.has-file {
        border-color: #10b981;
        background-color: rgba(16,185,129,0.1);
        border-style: solid;
    }
</style>
@endpush

@section('content')
<main class="pt-10 pb-20 px-4 md:px-8 max-w-4xl mx-auto">
    <!-- Welcome Hero Section -->
    <div class="mb-12">
        <h1 class="text-headline-lg font-extrabold text-white font-display mb-4">Daftar sebagai Penyelenggara/EO</h1>
        <p class="text-body-lg text-slate-400 max-w-2xl">
            Bergabunglah dengan ekosistem olahraga terbesar di Manado. Kelola event komunitas, pendaftaran, dan pembayaran dalam satu dashboard profesional.
        </p>
    </div>

    <!-- Alert / Notice Box -->
    <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-6 mb-6 flex items-start gap-3">
        <span class="material-symbols-outlined text-amber-400 mt-0.5">warning</span>
        <div class="text-sm text-amber-300">
            <p class="font-bold mb-1">Persyaratan Wajib Upload Dokumen & Pembuatan Event Pertama</p>
            <p class="mb-2">Demi keamanan seluruh peserta, Anda wajib melampirkan proposal resmi dan surat izin penyelenggaraan. Pada saat mendaftar, Anda juga akan langsung mengajukan event olahraga pertama Anda.</p>
            <ul class="list-disc ml-4 space-y-0.5 text-xs text-amber-400">
                <li><strong>Proposal Resmi Acara</strong> (PDF/DOCX, maks 10MB)</li>
                <li><strong>Surat Izin Penyelenggaraan Kegiatan</strong> (PDF/DOCX, maks 10MB)</li>
            </ul>
        </div>
    </div>

    <form action="{{ route('event-registration.store') }}"
          method="POST"
          enctype="multipart/form-data"
          id="reg-form"
          class="space-y-6">
        @csrf        <!-- Section 1: Informasi Organisasi -->
        <section class="bg-dark-card p-6 md:p-12 rounded-xl border border-slate-800 shadow-sm">
            <div class="flex items-center gap-3 mb-6">
                <span class="bg-accent/10 text-accent w-10 h-10 rounded-full flex items-center justify-center font-bold">1</span>
                <h2 class="text-headline-md font-semibold text-white font-display">Informasi Organisasi</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex flex-col gap-2">
                    <label class="text-label-md font-semibold text-slate-300 uppercase tracking-wider" for="organization_name">Nama Organisasi / Komunitas <span class="text-red-400">*</span></label>
                    <input class="border border-slate-800 bg-dark-bg text-white placeholder-slate-500 rounded-xl p-3 focus:ring-2 focus:ring-accent focus:border-accent outline-none transition-all @error('organization_name') border-red-500 @enderror"
                           id="organization_name"
                           name="organization_name"
                           value="{{ old('organization_name') }}"
                           placeholder="Contoh: Manado Runners Community"
                           required
                           type="text"/>
                    @error('organization_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-label-md font-semibold text-slate-300 uppercase tracking-wider" for="phone">Nomor WhatsApp <span class="text-red-400">*</span></label>
                    <input class="border border-slate-800 bg-dark-bg text-white placeholder-slate-500 rounded-xl p-3 focus:ring-2 focus:ring-accent focus:border-accent outline-none transition-all @error('phone') border-red-500 @enderror"
                           id="phone"
                           name="phone"
                           value="{{ old('phone') }}"
                           placeholder="Contoh: 081234567890"
                           required
                           type="tel"/>
                    @error('phone')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-label-md font-semibold text-slate-300 uppercase tracking-wider" for="email">Email Resmi</label>
                    <input class="border border-slate-800 bg-dark-bg/50 text-slate-400 rounded-xl p-3 outline-none cursor-not-allowed transition-all"
                           id="email"
                           name="email"
                           value="{{ auth()->user()->email }}"
                           readonly
                           type="email"/>
                    <p class="text-[10px] text-slate-500 mt-1">Terhubung secara otomatis ke email akun Anda.</p>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-label-md font-semibold text-slate-300 uppercase tracking-wider" for="password">Password Akun</label>
                    <input class="border border-slate-800 bg-dark-bg/50 text-slate-400 rounded-xl p-3 outline-none cursor-not-allowed transition-all"
                           id="password"
                           value="••••••••"
                           readonly
                           type="password"/>
                    <p class="text-[10px] text-slate-500 mt-1">Menggunakan password akun Anda saat ini.</p>
                </div>
            </div>
        </section>

        <!-- Section 2: Informasi Event Pertama -->
        <section class="bg-dark-card p-6 md:p-12 rounded-xl border border-slate-800 shadow-sm">
            <div class="flex items-center gap-3 mb-6">
                <span class="bg-accent/10 text-accent w-10 h-10 rounded-full flex items-center justify-center font-bold">2</span>
                <h2 class="text-headline-md font-semibold text-white font-display">Informasi Event Pertama</h2>
            </div>
            
            <div class="space-y-6">
                <div class="flex flex-col gap-2">
                    <label class="text-label-md font-semibold text-slate-300 uppercase tracking-wider" for="event_title">Nama Event Olahraga <span class="text-red-400">*</span></label>
                    <input class="border border-slate-800 bg-dark-bg text-white placeholder-slate-500 rounded-xl p-3 focus:ring-2 focus:ring-accent focus:border-accent outline-none transition-all @error('event_title') border-red-500 @enderror"
                           id="event_title"
                           name="event_title"
                           value="{{ old('event_title') }}"
                           placeholder="Contoh: Fun Run Komunitas Manado 2026"
                           required
                           type="text"/>
                    @error('event_title')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-label-md font-semibold text-slate-300 uppercase tracking-wider" for="event_description">Deskripsi & Ketentuan Event <span class="text-red-400">*</span></label>
                    <textarea class="border border-slate-800 bg-dark-bg text-white placeholder-slate-500 rounded-xl p-3 focus:ring-2 focus:ring-accent focus:border-accent outline-none transition-all resize-y @error('event_description') border-red-500 @enderror"
                              id="event_description"
                              name="event_description"
                              placeholder="Tulis detail event: jadwal, ketentuan kegiatan, kontak panitia, dll..."
                              required
                              rows="5">{{ old('event_description') }}</textarea>
                    @error('event_description')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex flex-col gap-2">
                        <label class="text-label-md font-semibold text-slate-300 uppercase tracking-wider" for="category">Kategori Olahraga <span class="text-red-400">*</span></label>
                        <select class="border border-slate-800 bg-dark-bg text-white rounded-xl p-3 focus:ring-2 focus:ring-accent focus:border-accent outline-none transition-all @error('category') border-red-500 @enderror"
                                id="category"
                                name="category"
                                required>
                            <option value="" class="bg-dark-card">-- Pilih Cabang --</option>
                            @foreach(['Lari & Trail','Padel / Tenis','E-Sports','Bersepeda','Futsal','Basket','Bulutangkis','Olahraga Lainnya'] as $cat)
                                <option value="{{ $cat }}" class="bg-dark-card" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                        @error('category')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-label-md font-semibold text-slate-300 uppercase tracking-wider" for="event_location">Lokasi Kegiatan / Event <span class="text-red-400">*</span></label>
                        <input class="border border-slate-800 bg-dark-bg text-white placeholder-slate-500 rounded-xl p-3 focus:ring-2 focus:ring-accent focus:border-accent outline-none transition-all @error('event_location') border-red-500 @enderror"
                               id="event_location"
                               name="event_location"
                               value="{{ old('event_location') }}"
                               placeholder="Contoh: GOR RW Monginsidi Sario, Manado"
                               required
                               type="text"/>
                        @error('event_location')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex flex-col gap-2">
                        <label class="text-label-md font-semibold text-slate-300 uppercase tracking-wider" for="event_date">Tanggal & Waktu Event <span class="text-red-400">*</span></label>
                        <input class="border border-slate-800 bg-dark-bg text-white placeholder-slate-500 rounded-xl p-3 focus:ring-2 focus:ring-accent focus:border-accent outline-none transition-all @error('event_date') border-red-500 @enderror"
                               id="event_date"
                               name="event_date"
                               value="{{ old('event_date') }}"
                               placeholder="Pilih Tanggal & Waktu"
                               required
                               readonly
                               type="text"/>
                        @error('event_date')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-label-md font-semibold text-slate-300 uppercase tracking-wider" for="registration_deadline">Batas Akhir Registrasi <span class="text-red-400">*</span></label>
                        <input class="border border-slate-800 bg-dark-bg text-white placeholder-slate-500 rounded-xl p-3 focus:ring-2 focus:ring-accent focus:border-accent outline-none transition-all @error('registration_deadline') border-red-500 @enderror"
                               id="registration_deadline"
                               name="registration_deadline"
                               value="{{ old('registration_deadline') }}"
                               placeholder="Pilih Batas Akhir Registrasi"
                               required
                               readonly
                               type="text"/>
                        @error('registration_deadline')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex flex-col gap-2">
                        <label class="text-label-md font-semibold text-slate-300 uppercase tracking-wider" for="max_participants">Maks. Kuota Peserta <span class="text-red-400">*</span></label>
                        <div class="relative">
                            <input class="w-full border border-slate-800 bg-dark-bg text-white rounded-xl p-3 pr-16 focus:ring-2 focus:ring-accent focus:border-accent outline-none transition-all @error('max_participants') border-red-500 @enderror"
                                   id="max_participants"
                                   name="max_participants"
                                   value="{{ old('max_participants', 32) }}"
                                   min="1"
                                   max="100000"
                                   required
                                   type="number"/>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-500 font-semibold">Peserta</span>
                        </div>
                        @error('max_participants')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-label-md font-semibold text-slate-300 uppercase tracking-wider" for="ticket_price">Biaya Registrasi <span class="text-red-400">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-500 font-semibold">Rp</span>
                            <input class="w-full border border-slate-800 bg-dark-bg text-white rounded-xl p-3 pl-8 focus:ring-2 focus:ring-accent focus:border-accent outline-none transition-all @error('ticket_price') border-red-500 @enderror"
                                   id="ticket_price"
                                   name="ticket_price"
                                   value="{{ old('ticket_price', 0) }}"
                                   min="0"
                                   step="1000"
                                   required
                                   type="number"/>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-1">Isi 0 jika pendaftaran gratis</p>
                        @error('ticket_price')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 3: Upload Dokumen Wajib & Media -->
        <section class="bg-dark-card p-6 md:p-12 rounded-xl border border-slate-800 shadow-sm">
            <div class="flex items-center gap-3 mb-6">
                <span class="bg-accent/10 text-accent w-10 h-10 rounded-full flex items-center justify-center font-bold">3</span>
                <h2 class="text-headline-md font-semibold text-white font-display">Upload Dokumen Wajib & Media</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Proposal Zone -->
                <div class="flex flex-col gap-3">
                    <label class="text-label-md font-semibold text-slate-300 uppercase tracking-wider">Proposal Resmi Acara <span class="text-red-400">*</span></label>
                    <div class="file-drop-zone rounded-xl p-12 flex flex-col items-center justify-center text-center cursor-pointer group"
                         id="proposal-zone"
                         onclick="document.getElementById('proposal_file').click()">
                        <div id="proposal-placeholder" class="flex flex-col items-center justify-center">
                            <span class="material-symbols-outlined text-4xl text-slate-500 mb-2 group-hover:text-accent transition-colors">description</span>
                            <p class="font-bold text-slate-300 text-sm">Klik atau seret file ke sini</p>
                            <p class="text-label-sm text-slate-500">PDF/Word, maks 10MB</p>
                        </div>
                        <div id="proposal-done" class="hidden flex flex-col items-center justify-center">
                            <span class="material-symbols-outlined text-4xl text-emerald-400 mb-2">check_circle</span>
                            <p id="proposal-filename" class="font-bold text-slate-300 text-sm truncate px-4 max-w-xs"></p>
                            <p class="text-label-sm text-slate-500">Klik untuk ganti file</p>
                        </div>
                    </div>
                    <input accept=".pdf,.doc,.docx" hidden id="proposal_file" name="proposal_file" type="file" required/>
                    @error('proposal_file')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Permit Zone -->
                <div class="flex flex-col gap-3">
                    <label class="text-label-md font-semibold text-slate-300 uppercase tracking-wider">Surat Izin Penyelenggaraan <span class="text-red-400">*</span></label>
                    <div class="file-drop-zone rounded-xl p-12 flex flex-col items-center justify-center text-center cursor-pointer group"
                         id="permit-zone"
                         onclick="document.getElementById('permit_file').click()">
                        <div id="permit-placeholder" class="flex flex-col items-center justify-center">
                            <span class="material-symbols-outlined text-4xl text-slate-500 mb-2 group-hover:text-accent transition-colors">verified_user</span>
                            <p class="font-bold text-slate-300 text-sm">Klik atau seret file ke sini</p>
                            <p class="text-label-sm text-slate-500">PDF/Word, maks 10MB</p>
                        </div>
                        <div id="permit-done" class="hidden flex flex-col items-center justify-center">
                            <span class="material-symbols-outlined text-4xl text-emerald-400 mb-2">check_circle</span>
                            <p id="permit-filename" class="font-bold text-slate-300 text-sm truncate px-4 max-w-xs"></p>
                            <p class="text-label-sm text-slate-500">Klik untuk ganti file</p>
                        </div>
                    </div>
                    <input accept=".pdf,.doc,.docx" hidden id="permit_file" name="permit_file" type="file" required/>
                    @error('permit_file')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <!-- Banner/Flyer Zone (Optional) -->
            <div class="flex flex-col gap-3 border-t border-slate-800 pt-6">
                <label class="text-label-md font-semibold text-slate-300 uppercase tracking-wider">Flyer / Banner Event <span class="text-slate-500 font-normal normal-case text-[11px]">(Opsional)</span></label>
                <div class="file-drop-zone rounded-xl p-12 flex flex-col items-center justify-center text-center cursor-pointer group"
                     id="banner-zone"
                     onclick="document.getElementById('banner').click()">
                    <div id="banner-placeholder" class="flex flex-col items-center justify-center">
                        <span class="material-symbols-outlined text-4xl text-slate-500 mb-2 group-hover:text-accent transition-colors">image</span>
                        <p class="font-bold text-slate-300 text-sm">Klik atau seret gambar ke sini</p>
                        <p class="text-label-sm text-slate-500">JPG, PNG, WebP, maks 4MB</p>
                    </div>
                    <div id="banner-preview-wrap" class="hidden w-full flex flex-col items-center justify-center">
                        <img id="banner-preview" src="" alt="Preview Banner" class="max-h-48 object-contain rounded-lg mb-2">
                        <p class="text-label-sm text-slate-500">Klik untuk ganti gambar</p>
                    </div>
                </div>
                <input accept="image/jpg,image/jpeg,image/png,image/webp" hidden id="banner" name="banner" type="file"/>
                @error('banner')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </section>

        <!-- Section 4: Deskripsi Komunitas -->
        <section class="bg-dark-card p-6 md:p-12 rounded-xl border border-slate-800 shadow-sm">
            <div class="flex items-center gap-3 mb-6">
                <span class="bg-accent/10 text-accent w-10 h-10 rounded-full flex items-center justify-center font-bold">4</span>
                <h2 class="text-headline-md font-semibold text-white font-display">Deskripsi Komunitas</h2>
            </div>
            <div class="flex flex-col gap-2">
                <label class="text-label-md font-semibold text-slate-300 uppercase tracking-wider" for="description">Tentang Organisasi</label>
                <textarea class="border border-slate-800 bg-dark-bg text-white placeholder-slate-500 rounded-xl p-3 focus:ring-2 focus:ring-accent focus:border-accent outline-none transition-all resize-none"
                          id="description"
                          name="description"
                          placeholder="Ceritakan sejarah singkat, visi, dan jenis event olahraga yang biasa Anda selenggarakan..."
                          rows="5"></textarea>
            </div>
        </section>

        <!-- Final Action & Disclaimer -->
        <div class="bg-dark-card p-6 md:p-12 rounded-xl flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-accent mt-1">info</span>
                <div>
                    <p class="font-bold text-white">Pemberitahuan</p>
                    <p class="text-label-md text-slate-400">Akun Organisasi dan Event Anda akan ditinjau oleh Admin dalam 1-3 hari kerja.</p>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                <a href="{{ route('events.index') }}"
                   class="px-6 py-4 border border-slate-800 hover:bg-slate-800 rounded-xl font-bold text-sm text-center text-slate-400 transition-all">
                    Batal
                </a>
                <button class="bg-accent hover:bg-accent-hover text-white px-12 py-4 rounded-xl font-bold text-sm shadow-md transition-all flex items-center justify-center gap-2 opacity-60 cursor-not-allowed"
                        type="submit"
                        id="submit-btn"
                        disabled>
                    Submit Pengajuan
                    <span class="material-symbols-outlined">send</span>
                </button>
            </div>
        </div>
    </form>
</main>
@endsection

@push('scripts')
<script src="{{ asset('assets/flatpickr.min.js') }}"></script>
<script>
    // ── Flatpickr date pickers ──
    const deadlinePickr = flatpickr("#registration_deadline", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        altInput: true,
        altFormat: "d-m-Y H:i",
        time_24hr: true,
        minDate: "today"
    });
    flatpickr("#event_date", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        altInput: true,
        altFormat: "d-m-Y H:i",
        time_24hr: true,
        minDate: "today",
        onChange(selectedDates) {
            if (selectedDates.length > 0) deadlinePickr.set("maxDate", selectedDates[0]);
        }
    });

    // ── Upload file feedback helper ──
    let proposalReady = false, permitReady = false;

    function handleDocUpload(inputId, zoneId, placeholderId, doneId, filenameId, flagSetter) {
        const input = document.getElementById(inputId);
        const zone  = document.getElementById(zoneId);

        input.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            // show done state
            document.getElementById(placeholderId).classList.add('hidden');
            document.getElementById(doneId).classList.remove('hidden');
            document.getElementById(filenameId).textContent = file.name;
            zone.classList.add('has-file');

            flagSetter(true);
            checkSubmitReady();
        });

        // drag-drop
        ['dragover','dragenter'].forEach(ev => zone.addEventListener(ev, e => {
            e.preventDefault();
            zone.classList.add('drag-active');
        }));
        ['dragleave','drop'].forEach(ev => zone.addEventListener(ev, e => {
            e.preventDefault();
            zone.classList.remove('drag-active');
        }));
        zone.addEventListener('drop', e => {
            const file = e.dataTransfer.files[0];
            if (file) {
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                input.dispatchEvent(new Event('change'));
            }
        });
    }

    handleDocUpload('proposal_file', 'proposal-zone', 'proposal-placeholder', 'proposal-done', 'proposal-filename', v => proposalReady = v);
    handleDocUpload('permit_file', 'permit-zone', 'permit-placeholder', 'permit-done', 'permit-filename', v => permitReady = v);

    function checkSubmitReady() {
        const btn = document.getElementById('submit-btn');
        if (proposalReady && permitReady) {
            btn.disabled = false;
            btn.classList.remove('opacity-60', 'cursor-not-allowed');
        } else {
            btn.disabled = true;
            btn.classList.add('opacity-60', 'cursor-not-allowed');
        }
    }

    // ── Banner image preview ──
    const bannerInput = document.getElementById('banner');
    bannerInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (ev) => {
            document.getElementById('banner-preview').src = ev.target.result;
            document.getElementById('banner-placeholder').classList.add('hidden');
            document.getElementById('banner-preview-wrap').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    });

    const bannerZone = document.getElementById('banner-zone');
    ['dragover','dragenter'].forEach(ev => bannerZone.addEventListener(ev, e => {
        e.preventDefault();
        bannerZone.classList.add('drag-active');
    }));
    ['dragleave','drop'].forEach(ev => bannerZone.addEventListener(ev, e => {
        e.preventDefault();
        bannerZone.classList.remove('drag-active');
    }));
    bannerZone.addEventListener('drop', e => {
        const file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            const dt = new DataTransfer();
            dt.items.add(file);
            bannerInput.files = dt.files;
            bannerInput.dispatchEvent(new Event('change'));
        }
    });

    // ── Prevent submit while loading ──
    document.getElementById('reg-form').addEventListener('submit', function() {
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.innerHTML = `<span class="material-symbols-outlined animate-spin text-sm">refresh</span> Mengunggah...`;
    });
</script>
@endpush
