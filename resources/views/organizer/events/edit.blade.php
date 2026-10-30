@extends('layouts.organizer')

@section('title', 'Edit Event Olahraga – ' . $event->title . ' – SportManado')
@section('page-title', 'Edit Event Olahraga')

@push('styles')
<!-- Flatpickr Datepicker CSS -->
<link rel="stylesheet" href="{{ asset('assets/flatpickr.min.css') }}">
<style>
    .flatpickr-calendar { background: #0d1527 !important; border: 1px solid rgba(255,107,0,0.2) !important; }
    .flatpickr-day { color: #f1f5f9 !important; }
    .flatpickr-day.selected { background: #ff6b00 !important; border-color: #ff6b00 !important; color: #fff !important; }
    .flatpickr-day:hover { background: rgba(255,107,0,0.15) !important; color: #ff6b00 !important; }
    .flatpickr-day.today { border-color: #ff6b00 !important; }
    .flatpickr-day.flatpickr-disabled { color: rgba(255,255,255,0.15) !important; }
    .flatpickr-months .flatpickr-month { color: #fff !important; fill: #fff !important; }
    .flatpickr-weekday { color: rgba(255,255,255,0.4) !important; }
    select option { background: #0d1527 !important; color: #fff !important; }
    .drag-over { border-color: #ff6b00 !important; background: rgba(255,107,0,.08) !important; }
</style>
@endpush

@section('content')
<div class="max-w-[1400px] mx-auto">

    <!-- Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-white">Edit Detail Event Olahraga</h2>
            <p class="text-slate-400 text-sm mt-1">Sesuaikan informasi dasar, kuota, biaya pendaftaran, atau kustomisasi formulir pendaftaran.</p>
        </div>
    </div>

    <form action="{{ route('organizer.events.update', $event) }}"
          method="POST"
          enctype="multipart/form-data"
          id="edit-event-form">
        @csrf
        @method('PATCH')

        <div class="max-w-6xl mx-auto space-y-6">

            <!-- Informasi Dasar -->
            <div class="bg-dark-card border border-slate-800/40 rounded-xl p-5">
                <p class="text-xs font-semibold uppercase text-[#ff6b00] mb-4">
                    Informasi Dasar Event
                </p>

                <!-- Title -->
                <div class="mb-5">
                    <label for="title" class="block text-sm font-semibold text-slate-300 mb-1.5 tracking-wide">Nama Event Olahraga <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title"
                           value="{{ old('title', $event->title) }}"
                           placeholder="mis. Fun Run Kemerdekaan Manado 2026"
                           class="w-full px-4 py-2.5 bg-[#080c14] border border-slate-700 focus:border-[#ff6b00] focus:ring-2 focus:ring-[#ff6b00]/25 rounded-xl text-white text-sm outline-none transition-all placeholder-slate-400 @error('title') border-red-500 @enderror"
                           maxlength="255" required>
                    @error('title')<p class="text-xs text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>

                <!-- Description -->
                <div class="mb-5">
                    <label for="description" class="block text-sm font-semibold text-slate-300 mb-1.5 tracking-wide">Deskripsi &amp; Aturan Kegiatan <span class="text-red-500">*</span></label>
                    <textarea id="description" name="description" rows="6"
                              placeholder="Tulis detail event: jadwal berkumpul, aturan kegiatan, kontak panitia, dll."
                              class="w-full px-4 py-2.5 bg-[#080c14] border border-slate-700 focus:border-[#ff6b00] focus:ring-2 focus:ring-[#ff6b00]/25 rounded-xl text-white text-sm outline-none transition-all placeholder-slate-400 resize-y @error('description') border-red-500 @enderror"
                              required>{{ old('description', $event->description) }}</textarea>
                    @error('description')<p class="text-xs text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>

                <!-- Category & Location -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
                    <div>
                        <label for="category" class="block text-sm font-semibold text-slate-300 mb-1.5 tracking-wide">Kategori Olahraga <span class="text-red-500">*</span></label>
                        <select id="category" name="category"
                                class="w-full px-4 py-2.5 bg-[#080c14] border border-slate-700 focus:border-[#ff6b00] focus:ring-2 focus:ring-[#ff6b00]/25 rounded-xl text-white text-sm outline-none transition-all placeholder-slate-400 @error('category') border-red-500 @enderror" required>
                            <option value="">-- Pilih Cabang --</option>
                            @foreach(['Futsal','Basket','Bulutangkis','Lari & Trail','Padel / Tenis','Bersepeda','E-Sports','Olahraga Lainnya'] as $cat)
                                <option value="{{ $cat }}" {{ old('category', $event->category) === $cat ? 'selected' : '' }}>
                                    {{ $cat }}
                                </option>
                            @endforeach
                        </select>
                        @error('category')<p class="text-xs text-red-500 mt-1.5">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="location" class="block text-sm font-semibold text-slate-300 mb-1.5 tracking-wide">Lokasi Pelaksanaan Event <span class="text-red-500">*</span></label>
                        <input type="text" id="location" name="location"
                               value="{{ old('location', $event->location) }}"
                               placeholder="mis. GOR RW Monginsidi Sario, Manado"
                               class="w-full px-4 py-2.5 bg-[#080c14] border border-slate-700 focus:border-[#ff6b00] focus:ring-2 focus:ring-[#ff6b00]/25 rounded-xl text-white text-sm outline-none transition-all placeholder-slate-400 @error('location') border-red-500 @enderror"
                               required>
                        @error('location')<p class="text-xs text-red-500 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <!-- Waktu & Kapasitas -->
            <div class="bg-dark-card border border-slate-800/40 rounded-xl p-5">
                <p class="text-xs font-semibold uppercase text-[#ff6b00] mb-4">
                    Waktu &amp; Kapasitas
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5 items-start">
                    <div>
                        <label for="event_date" class="block text-sm font-semibold text-slate-300 mb-1.5 tracking-wide">Mulai Event <span class="text-red-500">*</span></label>
                        <input type="text" id="event_date" name="event_date"
                               value="{{ old('event_date', $event->event_date->format('Y-m-d H:i')) }}"
                               placeholder="Pilih Tanggal &amp; Waktu"
                               class="w-full px-4 py-2.5 bg-[#080c14] border border-slate-700 focus:border-[#ff6b00] focus:ring-2 focus:ring-[#ff6b00]/25 rounded-xl text-white text-sm outline-none transition-all placeholder-slate-400 @error('event_date') border-red-500 @enderror"
                               required readonly>
                        @error('event_date')<p class="text-xs text-red-500 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div class="min-w-0">
                        <label for="event_end_at" class="block text-sm font-semibold text-slate-300 mb-1.5">Selesai Event (WITA)</label>
                        <input type="datetime-local" id="event_end_at" name="event_end_at" value="{{ old('event_end_at', $event->event_end_at?->format('Y-m-d\TH:i')) }}" class="w-full min-w-0 rounded-xl border border-slate-700 bg-[#080c14] px-4 py-2.5 text-sm text-white">
                        <p class="mt-1 text-xs text-slate-400">Kosongkan untuk selesai pada akhir hari event. Isi untuk kegiatan beberapa hari.</p>
                        @error('event_end_at')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="registration_deadline" class="block text-sm font-semibold text-slate-300 mb-1.5 tracking-wide">Batas Pendaftaran <span class="text-red-500">*</span></label>
                        <input type="text" id="registration_deadline" name="registration_deadline"
                               value="{{ old('registration_deadline', $event->registration_deadline->format('Y-m-d H:i')) }}"
                               placeholder="Batas Akhir Pendaftaran"
                               class="w-full px-4 py-2.5 bg-[#080c14] border border-slate-700 focus:border-[#ff6b00] focus:ring-2 focus:ring-[#ff6b00]/25 rounded-xl text-white text-sm outline-none transition-all placeholder-slate-400 @error('registration_deadline') border-red-500 @enderror"
                               required readonly>
                        @error('registration_deadline')<p class="text-xs text-red-500 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
                    <div>
                        <label for="max_participants" class="block text-sm font-semibold text-slate-300 mb-1.5 tracking-wide">Maks. Kuota Peserta/Tim <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="number" id="max_participants" name="max_participants"
                                   value="{{ old('max_participants', $event->max_participants) }}"
                                   min="1" max="100000"
                                   class="w-full px-4 py-2.5 bg-[#080c14] border border-slate-700 focus:border-[#ff6b00] focus:ring-2 focus:ring-[#ff6b00]/25 rounded-xl text-white text-sm outline-none transition-all placeholder-slate-400 pr-16 @error('max_participants') border-red-500 @enderror"
                                   required>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">Peserta</span>
                        </div>
                        @error('max_participants')<p class="text-xs text-red-500 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="ticket_price" class="block text-sm font-semibold text-slate-300 mb-1.5 tracking-wide">Biaya Registrasi Pendaftaran <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-500 font-semibold">Rp</span>
                            <input type="number" id="ticket_price" name="ticket_price"
                                   value="{{ old('ticket_price', $event->ticket_price) }}"
                                   min="0" step="1000"
                                   placeholder="0"
                                   class="w-full px-4 py-2.5 bg-[#080c14] border border-slate-700 focus:border-[#ff6b00] focus:ring-2 focus:ring-[#ff6b00]/25 rounded-xl text-white text-sm outline-none transition-all placeholder-slate-400 pl-8 @error('ticket_price') border-red-500 @enderror"
                                   required>
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            <p class="text-[10px] text-slate-500">Isi 0 jika pendaftaran gratis</p>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input class="sr-only peer" id="free-toggle" onchange="togglePrice(this)" type="checkbox" {{ old('ticket_price', $event->ticket_price) == 0 ? 'checked' : '' }}/>
                                <div class="w-9 h-5 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-700 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#ff6b00]"></div>
                                <span class="ml-1.5 text-[10px] font-bold text-slate-500">Gratis</span>
                            </label>
                        </div>
                        @error('ticket_price')<p class="text-xs text-red-500 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <!-- Auto-approve toggle (free events only) -->
            <div id="auto-approve-section" class="bg-dark-card border border-slate-800/40 rounded-xl p-5 {{ $event->ticket_price > 0 ? 'hidden' : '' }}">
                <label class="flex items-start gap-3 cursor-pointer">
                        <input type="hidden" name="auto_approve" value="1">
                        <input type="checkbox" name="auto_approve" value="0" {{ !$event->auto_approve ? 'checked' : '' }}
                           class="mt-0.5 w-4 h-4 rounded border-slate-700 bg-[#080c14] text-[#ff6b00] focus:ring-[#ff6b00]/25 focus:ring-2">
                    <div>
                        <p class="text-sm font-semibold text-white">Verifikasi Manual</p>
                        <p class="text-xs text-slate-400 mt-0.5">Peserta perlu disetujui sebelum tiket aktif. Cocok untuk event dengan slot terbatas.</p>
                    </div>
                </label>
                <p class="text-xs text-slate-500 mt-3 ml-7">Default: peserta langsung aktif saat mendaftar. Centang opsi ini jika ingin menyetujui satu per satu.</p>
            </div>

            <!-- Custom Fields (Google Form style) -->
            <div class="bg-dark-card border border-slate-800/40 rounded-xl p-5 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-300  flex items-center gap-2">
                            Kolom Kebutuhan Form Registrasi
                        </h3>
                        <p class="text-xs text-slate-400 mt-1">Buat isian khusus untuk formulir pendaftaran peserta (seperti ukuran jersey, kategori lomba, nomor HP darurat, dsb.).</p>
                    </div>
                    <button type="button" id="btn-add-field" class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-orange-500/10 border border-orange-500/30 hover:bg-orange-500/20 text-[#ff6b00] transition-colors flex items-center gap-1.5 whitespace-nowrap">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        Tambah Kolom
                    </button>
                </div>

                <!-- Fields container -->
                <div id="custom-fields-container" class="space-y-4">
                    <!-- Dynamic fields will be appended here -->
                </div>
            </div>

            <!-- Flyer / Banner Upload -->
            <div class="bg-dark-card border border-slate-800/40 rounded-xl p-5">
                <p class="text-xs font-semibold uppercase text-[#ff6b00] mb-4">
                    Ganti Flyer / Banner Event
                </p>

                <div class="border-2 border-dashed border-[#ff6b00]/40 hover:border-[#ff6b00] rounded-xl py-10 px-6 text-center hover:bg-[#ff6b00]/5 transition-all cursor-pointer" id="drop-zone" onclick="document.getElementById('banner').click()">
                    <div id="preview-container" class="{{ $event->banner ? '' : 'hidden' }} mb-3">
                        <img id="banner-preview" src="{{ $event->banner ? asset('storage/' . $event->banner) : 'data:image/svg+xml;charset=utf8,%3Csvg%20xmlns=\'http://www.w3.org/2000/svg\'%3E%3C/svg%3E' }}" alt="Preview" class="w-full h-36 object-cover rounded-xl">
                    </div>
                    <div id="drop-placeholder" class="{{ $event->banner ? 'hidden' : '' }}">
                        <p class="text-xs font-semibold text-slate-400">Klik / seret pamflet ke sini untuk mengganti</p>
                        <p class="text-[10px] text-slate-500 mt-1">JPG, PNG, WebP · Maks 4MB</p>
                    </div>
                </div>

                <input type="file" id="banner" name="banner"
                       accept="image/jpg,image/jpeg,image/png,image/webp"
                       class="hidden">
                @error('banner')<p class="text-xs text-red-500 mt-2">{{ $message }}</p>@enderror
            </div>

            <!-- Berkas Anti-Penipuan -->
            <div class="bg-dark-card border border-slate-800/40 rounded-xl p-5 space-y-4">
                <p class="text-xs font-semibold uppercase text-[#ff6b00] mb-4">
                    Dokumen Verifikasi Penyelenggaraan
                </p>
                <p class="text-[10px] text-orange-400 leading-relaxed bg-orange-950/20 p-3 rounded-xl border border-orange-900/40">
                    <strong>PERHATIAN:</strong> Demi menjamin keamanan dan kenyamanan pendaftar, unggah 3 dokumen verifikasi berikut dengan benar. Ketidaklengkapan berkas dapat menyebabkan pengajuan event ditolak oleh Admin.
                </p>

                <!-- Proposal -->
                <div class="space-y-2">
                    <label for="proposal" class="block text-sm font-semibold text-slate-300 mb-1.5 tracking-wide">Proposal Kegiatan (.pdf/.docx)</label>
                    @if($event->proposal)
                        <div class="mb-2 text-xs text-slate-400 flex items-center gap-2">
                            <i data-lucide="file-text" class="w-4 h-4 text-[#ff6b00]"></i>
                            <span>Berkas saat ini: <a href="{{ asset('storage/' . $event->proposal) }}" target="_blank" class="text-[#ff6b00] hover:underline font-semibold">Lihat Proposal</a></span>
                        </div>
                    @endif
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="document.getElementById('proposal').click()" class="px-4 py-2 bg-[#ff6b00]/10 border border-[#ff6b00]/20 hover:bg-[#ff6b00]/20 text-[#ff6b00] text-xs font-bold rounded-xl transition-all flex items-center gap-2">
                            <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                            Pilih Berkas
                        </button>
                        <span id="proposal-filename" class="text-xs text-slate-400">Belum ada berkas terpilih</span>
                    </div>
                    <input type="file" id="proposal" name="proposal" accept=".pdf,.docx,.doc" class="hidden" onchange="updateFileName(this, 'proposal-filename')">
                    @error('proposal')<p class="text-xs text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>

                <!-- Permit -->
                <div class="space-y-2">
                    <label for="permit" class="block text-sm font-semibold text-slate-300 mb-1.5 tracking-wide">Izin Penyelenggaraan (.pdf/.docx)</label>
                    @if($event->permit)
                        <div class="mb-2 text-xs text-slate-400 flex items-center gap-2">
                            <i data-lucide="file-text" class="w-4 h-4 text-[#ff6b00]"></i>
                            <span>Berkas saat ini: <a href="{{ asset('storage/' . $event->permit) }}" target="_blank" class="text-[#ff6b00] hover:underline font-semibold">Lihat Izin</a></span>
                        </div>
                    @endif
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="document.getElementById('permit').click()" class="px-4 py-2 bg-[#ff6b00]/10 border border-[#ff6b00]/20 hover:bg-[#ff6b00]/20 text-[#ff6b00] text-xs font-bold rounded-xl transition-all flex items-center gap-2">
                            <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                            Pilih Berkas
                        </button>
                        <span id="permit-filename" class="text-xs text-slate-400">Belum ada berkas terpilih</span>
                    </div>
                    <input type="file" id="permit" name="permit" accept=".pdf,.docx,.doc" class="hidden" onchange="updateFileName(this, 'permit-filename')">
                    @error('permit')<p class="text-xs text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>

                <!-- KTP image -->
                <div class="space-y-2">
                    <label for="ktp_image" class="block text-sm font-semibold text-slate-300 mb-1.5 tracking-wide">Foto KTP Penanggung Jawab</label>
                    @if($event->ktp_image)
                        <div class="mb-2 text-xs text-slate-400 flex items-center gap-2">
                            <i data-lucide="image" class="w-4 h-4 text-[#ff6b00]"></i>
                            <span>Berkas saat ini: <a href="{{ asset('storage/' . $event->ktp_image) }}" target="_blank" class="text-[#ff6b00] hover:underline font-semibold">Lihat KTP</a></span>
                        </div>
                    @endif
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="document.getElementById('ktp_image').click()" class="px-4 py-2 bg-[#ff6b00]/10 border border-[#ff6b00]/20 hover:bg-[#ff6b00]/20 text-[#ff6b00] text-xs font-bold rounded-xl transition-all flex items-center gap-2">
                            <i data-lucide="image" class="w-3.5 h-3.5"></i>
                            Pilih Gambar
                        </button>
                        <span id="ktp_image-filename" class="text-xs text-slate-400">Belum ada berkas terpilih</span>
                    </div>
                    <input type="file" id="ktp_image" name="ktp_image" accept="image/jpeg,image/png" class="hidden" onchange="updateFileName(this, 'ktp_image-filename')">
                    @error('ktp_image')<p class="text-xs text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>
            </div>

            <!-- Save & Status Settings / Action Buttons -->
            <div class="bg-dark-card border border-slate-800/40 rounded-xl p-5 space-y-4">
                <p class="text-xs font-semibold uppercase text-[#ff6b00] mb-4 flex items-center gap-2">
                    <i data-lucide="settings" class="w-4 h-4"></i>
                    Pengaturan &amp; Status
                </p>

                <div>
                    <label for="status" class="block text-sm font-semibold text-slate-300 mb-1.5 tracking-wide">Status Publikasi Event <span class="text-red-500">*</span></label>
                    <select id="status" name="status" class="w-full px-4 py-2.5 bg-[#080c14] border border-slate-700 focus:border-[#ff6b00] focus:ring-2 focus:ring-[#ff6b00]/25 rounded-xl text-white text-sm outline-none transition-all placeholder-slate-400" required>
                        <option value="pending" {{ old('status', $event->status) === 'pending' ? 'selected' : '' }}>Menunggu Validasi</option>
                        <option value="active" {{ old('status', $event->status) === 'active' ? 'selected' : '' }}>Aktif / Terbit</option>
                        <option value="draft" {{ old('status', $event->status) === 'draft' ? 'selected' : '' }}>Simpan sebagai Draft</option>
                        <option value="cancelled" {{ old('status', $event->status) === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                    <p class="mt-2 text-xs text-slate-400">Event dengan kepesertaan atau pembayaran berbayar tidak dapat dibatalkan atau ditarik dari publikasi karena refund belum tersedia.</p>
                    @error('status')<p class="text-xs text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-3 pt-2">
                    <button type="submit" class="w-full bg-[#ff6b00] hover:bg-[#e05e00] text-white py-3.5 px-4 rounded-xl font-bold text-sm shadow transition-all flex items-center justify-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        Simpan Perubahan
                    </button>
                    <a href="{{ route('organizer.events.index') }}"
                       class="w-full block text-center py-3.5 rounded-xl text-sm font-semibold text-slate-400 hover:text-white border border-slate-800 hover:bg-slate-800/30 transition-all">
                        Batal
                    </a>
                </div>
            </div>

        </div>
    </form>
</div>
@endsection

@push('scripts')
<!-- Flatpickr JS -->
<script src="{{ asset('assets/flatpickr.min.js') }}"></script>
<script src="{{ asset('assets/flatpickr-id.js') }}"></script>
<script>
    window.updateFileName = function(input, targetId) {
        const target = document.getElementById(targetId);
        if (input.files && input.files.length > 0) {
            target.textContent = input.files[0].name;
            target.classList.remove('text-slate-400');
            target.classList.add('text-white', 'font-medium');
        } else {
            target.textContent = 'Belum ada berkas terpilih';
            target.classList.remove('text-white', 'font-medium');
            target.classList.add('text-slate-400');
        }
    }

    // Toggle Price Input
    window.togglePrice = function(checkbox) {
        const priceInput = document.getElementById('ticket_price');
        const autoSection = document.getElementById('auto-approve-section');
        if (checkbox.checked) {
            priceInput.value = '0';
            priceInput.setAttribute('readonly', 'readonly');
            priceInput.classList.add('bg-slate-900', 'cursor-not-allowed', 'opacity-50');
            autoSection.classList.remove('hidden');
        } else {
            priceInput.removeAttribute('readonly');
            priceInput.classList.remove('bg-slate-900', 'cursor-not-allowed', 'opacity-50');
            autoSection.classList.add('hidden');
        }
    }

    // Datepicker initializations
    const deadlinePickr = flatpickr("#registration_deadline", {
        locale: "id",
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        altInput: true,
        altFormat: "d-m-Y H:i",
        time_24hr: true,
        theme: "dark"
    });

    const eventDatePickr = flatpickr("#event_date", {
        locale: "id",
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        altInput: true,
        altFormat: "d-m-Y H:i",
        time_24hr: true,
        theme: "dark",
        onChange: function(selectedDates) {
            if (selectedDates.length > 0) {
                deadlinePickr.set("maxDate", selectedDates[0]);
            }
        }
    });

    // Banner preview
    const bannerInput  = document.getElementById('banner');
    const preview      = document.getElementById('banner-preview');
    const previewWrap  = document.getElementById('preview-container');
    const placeholder  = document.getElementById('drop-placeholder');
    const dropZone     = document.getElementById('drop-zone');

    bannerInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (ev) => {
            preview.src = ev.target.result;
            previewWrap.classList.remove('hidden');
            placeholder.classList.add('hidden');
        };
        reader.readAsDataURL(file);
    });

    // Drag-and-drop banner
    ['dragover','dragenter'].forEach(ev => {
        dropZone.addEventListener(ev, e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    });
    ['dragleave','drop'].forEach(ev => {
        dropZone.addEventListener(ev, e => { e.preventDefault(); dropZone.classList.remove('drag-over'); });
    });
    dropZone.addEventListener('drop', e => {
        const file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            const dt = new DataTransfer();
            dt.items.add(file);
            bannerInput.files = dt.files;
            bannerInput.dispatchEvent(new Event('change'));
        }
    });

    // Dynamic custom fields script
    let fieldCount = 0;
    const container = document.getElementById('custom-fields-container');
    const btnAdd = document.getElementById('btn-add-field');

    function createFieldBlock(index) {
        const div = document.createElement('div');
        div.className = 'bg-slate-900/50 rounded-xl p-4 border border-slate-800 flex flex-col gap-3';
        div.id = `field-block-${index}`;
        
        div.innerHTML = `
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-2">
                <span class="text-xs font-bold text-slate-400 uppercase">Kolom Tambahan</span>
                <button type="button" class="text-slate-400 hover:text-red-500 text-xs font-bold flex items-center gap-1 transition-colors" onclick="removeFieldBlock(${index})">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Hapus
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-1.5 tracking-wide">Judul Kolom / Pertanyaan <span class="text-red-500">*</span></label>
                    <input type="text" name="custom_fields[${index}][label]" required placeholder="mis. Ukuran Jersey, Nomor HP Darurat, Kategori Lari" class="w-full px-4 py-2.5 bg-[#080c14] border border-slate-700 focus:border-[#ff6b00] focus:ring-2 focus:ring-[#ff6b00]/25 rounded-xl text-white text-sm outline-none transition-all placeholder-slate-400">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-1.5 tracking-wide">Tipe Isian <span class="text-red-500">*</span></label>
                    <select name="custom_fields[${index}][type]" class="w-full px-4 py-2.5 bg-[#080c14] border border-slate-700 focus:border-[#ff6b00] focus:ring-2 focus:ring-[#ff6b00]/25 rounded-xl text-slate-300 text-sm outline-none transition-all" onchange="toggleOptionsInput(this, ${index})">
                        <option value="text">Teks Singkat (Jawaban Singkat)</option>
                        <option value="textarea">Teks Panjang (Jawaban Paragraf)</option>
                        <option value="radio">Pilihan Ganda (Radio Buttons)</option>
                    </select>
                </div>
            </div>

            <label class="inline-flex items-center gap-2 text-xs text-slate-300">
                <input type="hidden" name="custom_fields[${index}][required]" value="0">
                <input type="checkbox" name="custom_fields[${index}][required]" value="1" checked class="rounded border-slate-700 bg-[#080c14] text-[#ff6b00]">
                Wajib diisi peserta
            </label>

            <!-- Options (hidden by default, shown for radio) -->
            <div id="options-wrap-${index}" class="hidden space-y-1">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5 tracking-wide">Opsi Pilihan <span class="text-red-500">*</span> <span class="text-slate-500 font-normal">(pisahkan dengan koma, mis: S, M, L, XL)</span></label>
                <input type="text" name="custom_fields[${index}][options]" placeholder="mis. XS, S, M, L, XL, XXL" class="w-full px-4 py-2.5 bg-[#080c14] border border-slate-700 focus:border-[#ff6b00] focus:ring-2 focus:ring-[#ff6b00]/25 rounded-xl text-white text-sm outline-none transition-all placeholder-slate-400">
            </div>

            <!-- Upload helper image -->
            <div class="space-y-1">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5 tracking-wide">Gambar Penjelas / Chart <span class="text-slate-500 font-normal">(Opsional)</span></label>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="document.getElementById('custom_fields_image_${index}').click()" class="px-4 py-2 bg-[#ff6b00]/10 border border-[#ff6b00]/20 hover:bg-[#ff6b00]/20 text-[#ff6b00] text-xs font-bold rounded-xl transition-all flex items-center gap-2">
                                <i data-lucide="image" class="w-3.5 h-3.5"></i>
                                Pilih Gambar
                    </button>
                    <span id="custom_fields_image_${index}_filename" class="text-xs text-slate-400">Belum ada gambar terpilih</span>
                </div>
                <input type="file" id="custom_fields_image_${index}" name="custom_fields[${index}][image]" accept="image/*" class="hidden" onchange="updateFileName(this, 'custom_fields_image_${index}_filename')">
            </div>
        `;
        return div;
    }

    btnAdd.addEventListener('click', () => {
        container.appendChild(createFieldBlock(fieldCount));
        
        fieldCount++;
    });

    window.removeFieldBlock = function(index) {
        const el = document.getElementById(`field-block-${index}`);
        if (el) el.remove();
    };

    window.toggleOptionsInput = function(select, index) {
        const optionsWrap = document.getElementById(`options-wrap-${index}`);
        if (select.value === 'radio') {
            optionsWrap.classList.remove('hidden');
            optionsWrap.querySelector('input').setAttribute('required', 'required');
        } else {
            optionsWrap.classList.add('hidden');
            optionsWrap.querySelector('input').removeAttribute('required');
        }
    };

    // Initialize initial values
    document.addEventListener('DOMContentLoaded', () => {
        const priceInput = document.getElementById('ticket_price');
        if (priceInput.value === '0' || priceInput.value === '') {
            document.getElementById('free-toggle').checked = true;
            togglePrice(document.getElementById('free-toggle'));
        }

        // Load existing custom fields
        @if($event->custom_fields && count($event->custom_fields) > 0)
            const existingFields = @json($event->custom_fields);
            existingFields.forEach(field => {
                const block = createFieldBlock(fieldCount);
                container.appendChild(block);
                
                block.querySelector(`input[name="custom_fields[${fieldCount}][label]"]`).value = field.label;
                block.querySelector(`select[name="custom_fields[${fieldCount}][type]"]`).value = field.type;
                block.querySelector(`input[type="checkbox"][name="custom_fields[${fieldCount}][required]"]`).checked = field.required !== false;
                
                if (field.type === 'radio') {
                    window.toggleOptionsInput(block.querySelector(`select[name="custom_fields[${fieldCount}][type]"]`), fieldCount);
                    block.querySelector(`input[name="custom_fields[${fieldCount}][options]"]`).value = field.options || '';
                }
                
                fieldCount++;
            });
            
        @endif
    });
</script>
@endpush
