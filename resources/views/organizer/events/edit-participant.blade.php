@extends('layouts.organizer')

@section('title', 'Edit Peserta – ' . $event->title)

@section('content')
<div class="max-w-3xl mx-auto space-y-5">
    <a href="{{ route('organizer.events.participants', $event) }}" class="inline-flex min-h-11 items-center text-sm text-slate-400 hover:text-white">← Kembali ke Daftar Peserta</a>
    <div>
        <h1 class="text-xl font-bold text-white">Edit Peserta</h1>
        <p class="mt-1 break-words text-sm text-slate-400">{{ $event->title }} · {{ $ticket->ticket_code }}</p>
    </div>
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-red-800 bg-red-950/30 p-4 text-sm text-red-300">
            <p class="font-semibold">Perubahan belum disimpan:</p>
            <ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
        </div>
    @endif
    @if($ticket->status === 'cancelled')
        <div role="status" class="rounded-xl border border-red-800 bg-red-950/30 p-4 text-sm text-red-300">
            <p class="font-semibold">Kepesertaan dibatalkan / ditolak</p>
            @if($ticket->cancelled_at)<p class="mt-1">{{ $ticket->cancelled_at->timezone('Asia/Makassar')->format('d/m/Y H:i:s') }} WITA</p>@endif
            <p class="mt-2 whitespace-pre-wrap break-words">{{ $ticket->cancellation_reason ?: 'Alasan belum tercatat.' }}</p>
        </div>
    @endif
    <form method="POST" action="{{ route('organizer.events.participants.update', [$event, $ticket]) }}" class="space-y-5 rounded-xl border border-slate-800 bg-dark-card p-4 sm:p-6" id="edit-participant-form">
        @csrf
        @method('PATCH')
        <p class="text-sm leading-relaxed text-slate-400">Perubahan ini berlaku untuk pendaftaran pada kegiatan ini. Email dan password akun login peserta tetap sama.</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="min-w-0">
                <label for="participant-name" class="block text-sm font-medium text-slate-300">Nama peserta</label>
                <input id="participant-name" name="participant_name" type="text" required maxlength="255" value="{{ old('participant_name', $ticket->participant_name) }}" class="mt-2 w-full rounded-lg border-slate-700 bg-dark-bg text-white">
            </div>
            <div class="min-w-0">
                <label for="participant-email" class="block text-sm font-medium text-slate-300">Email pendaftaran</label>
                <input id="participant-email" name="participant_email" type="email" required maxlength="255" value="{{ old('participant_email', $ticket->participant_email) }}" class="mt-2 w-full rounded-lg border-slate-700 bg-dark-bg text-white">
            </div>
        </div>
        <div>
            <label for="participant-status" class="block text-sm font-medium text-slate-300">Status peserta / tiket</label>
            <select id="participant-status" name="status" required class="mt-2 w-full rounded-lg border-slate-700 bg-dark-bg text-white">
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $ticket->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @if($ticket->status === 'used')
                <p class="mt-2 text-xs leading-relaxed text-slate-400">Tiket sudah digunakan. Data peserta masih bisa diperbaiki; status hadir dan waktu validasi dipertahankan.</p>
            @elseif($isPaidRegistration)
                <p class="mt-2 text-xs leading-relaxed text-slate-400">Tiket berbayar hanya dapat diaktifkan atau dicatat hadir setelah pembayaran terkonfirmasi. Mengubah status peserta tidak mengubah pembayaran.</p>
            @endif
            @if($ticket->used_at)
                <p class="mt-2 text-sm text-sky-300">{{ $ticket->check_in_method === 'manual' ? 'Kehadiran dicatat Penyelenggara/EO' : ($ticket->check_in_method === 'qr' ? 'Validasi QR berhasil' : 'Waktu validasi') }}:
                    <time datetime="{{ $ticket->used_at->toIso8601String() }}">{{ $ticket->used_at->timezone('Asia/Makassar')->format('d/m/Y H:i:s') }} WITA</time>
                </p>
            @endif
        </div>
        @if(!$isPaidRegistration && $ticket->status !== 'cancelled' && $ticket->status !== 'used')
            <div id="edit-cancellation-section" class="hidden">
                <label for="edit-cancellation-reason" class="block text-sm font-medium text-slate-300">Alasan pembatalan</label>
                <textarea id="edit-cancellation-reason" name="cancellation_reason" rows="3" maxlength="1000" class="mt-2 w-full rounded-lg border-slate-700 bg-dark-bg text-white">{{ old('cancellation_reason') }}</textarea>
                <p class="mt-2 text-xs text-slate-400">Riwayat pendaftaran tetap tersimpan setelah pembatalan.</p>
            </div>
        @endif
        @php
            $answers = old('custom_answers', $ticket->custom_answers ?? []);
        @endphp
        @foreach($event->custom_fields ?? [] as $index => $field)
            @php
                $label = $field['label'] ?? '';
                $answer = is_array($answers) ? ($answers[$label] ?? '') : '';
                $answer = is_string($answer) ? $answer : '';
                $required = !array_key_exists('required', $field) || filter_var($field['required'], FILTER_VALIDATE_BOOLEAN);
            @endphp
            @if($label)
            <div>
                <label for="answer-{{ $index }}" class="block text-sm font-medium text-slate-300">{{ $label }} @if($required)<span class="text-red-400">*</span>@endif</label>
                @if(($field['type'] ?? 'text') === 'radio')
                    <select id="answer-{{ $index }}" name="custom_answers[{{ $label }}]" @required($required) class="mt-2 w-full rounded-lg border-slate-700 bg-dark-bg text-white">
                        <option value="">Pilih jawaban</option>
                        @foreach($field['options'] ?? [] as $option)<option value="{{ $option }}" @selected($answer === $option)>{{ $option }}</option>@endforeach
                    </select>
                @elseif(($field['type'] ?? 'text') === 'textarea')
                    <textarea id="answer-{{ $index }}" name="custom_answers[{{ $label }}]" rows="3" maxlength="2000" @required($required) class="mt-2 w-full rounded-lg border-slate-700 bg-dark-bg text-white">{{ $answer }}</textarea>
                @else
                    <input id="answer-{{ $index }}" name="custom_answers[{{ $label }}]" value="{{ $answer }}" maxlength="500" @required($required) class="mt-2 w-full rounded-lg border-slate-700 bg-dark-bg text-white">
                @endif
            </div>
            @endif
        @endforeach
        <div>
            <label for="participant-notes" class="block text-sm font-medium text-slate-300">Catatan pendaftaran</label>
            <textarea id="participant-notes" name="notes" rows="3" maxlength="2000" class="mt-2 w-full rounded-lg border-slate-700 bg-dark-bg text-white">{{ old('notes', $ticket->notes) }}</textarea>
        </div>
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <a href="{{ route('organizer.events.participants', $event) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-700 px-5 py-3 text-sm text-slate-300">Kembali</a>
            <button type="submit" class="min-h-11 rounded-lg bg-accent px-5 py-3 text-sm font-bold text-white">Simpan Perubahan</button>
        </div>
    </form>
    @if($isPaidRegistration)
        <section class="rounded-xl border border-slate-800 bg-dark-card p-4 sm:p-6">
            <h2 class="text-base font-bold text-slate-200">Pembatalan Kepesertaan</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-400">Kepesertaan berbayar tidak dapat dibatalkan karena pengembalian dana belum tersedia.</p>
        </section>
    @elseif($ticket->status !== 'used' && $ticket->status !== 'cancelled' && !$ticket->used_at)
        <section class="space-y-3 rounded-xl border border-red-900 bg-dark-card p-4 sm:p-6" aria-labelledby="cancel-title">
            <h2 id="cancel-title" class="text-base font-bold text-red-400">Batalkan Kepesertaan</h2>
            <p class="text-sm leading-relaxed text-slate-400">Peserta akan dikeluarkan dari daftar aktif dan tiketnya tidak dapat digunakan. Akun peserta serta riwayat pendaftaran tetap tersimpan.</p>
            <form id="cancel-participant-form" method="POST" action="{{ route('organizer.events.participants.cancel', [$event,$ticket]) }}" class="space-y-3">
                @csrf
                <label for="cancel-reason" class="block text-sm font-medium text-slate-300">Alasan pembatalan <span class="text-red-400">*</span></label>
                <textarea id="cancel-reason" name="cancellation_reason" rows="3" maxlength="1000" required class="w-full rounded-lg border-slate-700 bg-dark-bg text-white" placeholder="Contoh: Peserta berhalangan hadir">{{ old('cancellation_reason') }}</textarea>
                <button type="submit" class="min-h-11 w-full sm:w-auto rounded-lg bg-red-600 px-5 py-3 text-sm font-bold text-white hover:bg-red-700">Batalkan Kepesertaan</button>
            </form>
        </section>
    @elseif($ticket->status === 'used' || $ticket->used_at)
        <p class="rounded-xl border border-slate-800 p-4 text-sm text-slate-400">Pembatalan dinonaktifkan karena peserta sudah tercatat hadir. Waktu validasi tetap disimpan.</p>
    @endif
</div>
@endsection

@push('scripts')
<script>
const cancellationSection = document.getElementById('edit-cancellation-section');
const cancellationReason = document.getElementById('edit-cancellation-reason');
function updateCancellationInput() {
    const cancelling = document.getElementById('participant-status').value === 'cancelled';
    if (cancellationSection) cancellationSection.classList.toggle('hidden', !cancelling);
    if (cancellationReason) cancellationReason.required = cancelling;
}
document.getElementById('participant-status').addEventListener('change', updateCancellationInput);
updateCancellationInput();
document.getElementById('cancel-participant-form')?.addEventListener('submit', function (event) {
    if (!window.confirm('Batalkan kepesertaan ini? Tiket tidak dapat digunakan dan kuota akan dilepas.')) event.preventDefault();
});
document.getElementById('edit-participant-form').addEventListener('submit', function (event) {
    if (@js($ticket->status !== 'used') && document.getElementById('participant-status').value === 'used'
        && !window.confirm('Catat peserta sebagai hadir sekarang? Waktu akan disimpan dan tiket tidak dapat digunakan kembali.')) {
        event.preventDefault();
    }
});
</script>
@endpush
