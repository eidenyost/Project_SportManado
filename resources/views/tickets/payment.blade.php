@extends('layouts.app')

@section('title', 'Form Pendaftaran Event – ' . $event->title)

@section('content')
<div class="max-w-3xl mx-auto px-4 md:px-8 py-8">

    <a href="{{ route('events.show', $event) }}"
       class="text-sm text-slate-400 hover:text-accent mb-6 inline-block">
        ← Kembali ke Detail Event
    </a>

    @include('tickets._event-banner')

    <form action="{{ route('tickets.submit-payment', $event) }}"
          method="POST"
          id="payment-form">
        @csrf

        @if($errors->any())
            <div role="alert" class="mb-5 rounded-xl border border-red-800 bg-red-950/30 p-4 text-sm text-red-300">
                <p class="font-semibold">Pendaftaran belum berhasil. Periksa isian berikut:</p>
                <ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="space-y-5">

            @if($event->custom_fields && count($event->custom_fields) > 0)
            <div>
                <h3 class="text-sm font-bold text-white border-b border-slate-800 pb-2 mb-4">
                    Formulir Kebutuhan Event ({{ count($event->custom_fields) }}) <span class="text-red-400">*</span>
                </h3>

                <div class="space-y-4">
                    @foreach($event->custom_fields as $index => $field)
                        @if(empty($field['label'])) @continue @endif
                        @php
                            $label = $field['label'];
                            $required = !array_key_exists('required', $field) || $field['required'];
                            $errorKey = "custom_answers." . $index;
                            $inputClass = 'w-full px-4 py-2.5 bg-dark-bg border border-slate-700/50 rounded-xl text-white text-sm placeholder-slate-500 focus:border-accent focus:ring-1 focus:ring-accent/30 outline-none transition-colors';
                        @endphp
                        <div>
                            <label class="block font-medium text-sm text-slate-200 mb-1.5">
                                {{ $label }} @if($required)<span class="text-red-400">*</span>@endif
                            </label>

                            @if(!empty($field['image']))
                                <img src="{{ asset('storage/' . $field['image']) }}"
                                     class="w-full max-w-sm rounded-xl border border-slate-800 object-contain mb-3 cursor-zoom-in"
                                     alt="Panduan {{ $label }}"
                                     onclick="window.open(this.src, '_blank')">
                            @endif

                            @if($field['type'] === 'text')
                                <input type="text"
                                       name="custom_answers[{{ $label }}]"
                                       value="{{ old('custom_answers.' . $label) }}"
                                       placeholder="Jawaban Anda"
                                       class="{{ $inputClass }} @error($errorKey) border-red-500 @enderror"
                                       @if($required) required @endif>
                            @elseif($field['type'] === 'textarea')
                                <textarea name="custom_answers[{{ $label }}]"
                                          rows="3"
                                          placeholder="Jawaban Anda"
                                          class="{{ $inputClass }} resize-none @error($errorKey) border-red-500 @enderror"
                                           @if($required) required @endif>{{ old('custom_answers.' . $label) }}</textarea>
                            @elseif($field['type'] === 'radio')
                                <div class="space-y-2 bg-dark-bg border border-slate-800 rounded-xl p-3">
                                    @foreach($field['options'] as $option)
                                        <label class="flex items-center gap-3 text-xs text-slate-300 cursor-pointer py-1">
                                            <input type="radio"
                                                   name="custom_answers[{{ $label }}]"
                                                   value="{{ $option }}"
                                                   {{ old('custom_answers.' . $label) === $option ? 'checked' : '' }}
                                                   class="text-accent focus:ring-accent border-slate-600 bg-dark-bg"
                                                   @if($required) required @endif>
                                            <span>{{ $option }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif

                            @error($errorKey)
                                <p class="text-xs text-red-400 mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div>
                <h3 class="text-sm font-bold text-white border-b border-slate-800 pb-2 mb-4">
                    Catatan <span class="text-slate-500 text-xs font-normal">(opsional)</span>
                </h3>
                <textarea name="notes" rows="3" maxlength="2000"
                          placeholder="Keterangan tambahan atau permintaan khusus"
                          class="w-full px-4 py-2.5 bg-dark-bg border border-slate-700/50 rounded-xl text-white text-sm placeholder-slate-500 focus:border-accent focus:ring-1 focus:ring-accent/30 outline-none transition-colors resize-none">{{ old('notes') }}</textarea>
                @error('notes')<p class="text-xs text-red-400 mt-1.5">{{ $message }}</p>@enderror
            </div>

            <button type="submit" id="submit-btn"
                    class="w-full bg-accent text-white py-3 rounded-xl font-bold text-sm">
                {{ $event->ticket_price > 0 ? 'Lanjut ke Pembayaran' : ($event->auto_approve ? 'Daftar dan Terbitkan Tiket' : 'Kirim Pendaftaran') }}
            </button>

            <p class="text-xs text-slate-500 text-center">
                {{ $event->ticket_price > 0 ? 'Setelah mendaftar, lanjutkan pembayaran agar tiket aktif.' : ($event->auto_approve ? 'Tiket dan QR Code langsung tersedia setelah pendaftaran berhasil.' : 'Tiket akan aktif setelah disetujui panitia.') }}
            </p>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    <script>
        var f = document.getElementById('payment-form');
        var b = document.getElementById('submit-btn');
        if (f && b) {
            f.addEventListener('submit', function(){
                b.disabled = true;
                b.textContent = 'Mengirim…';
            });
        }
    </script>
@endpush
