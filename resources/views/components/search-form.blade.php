@props(['action' => url()->current(), 'placeholder' => 'Cari data...'])
<form method="GET" action="{{ $action }}" class="flex w-full min-w-0 items-center gap-2" role="search">
    @foreach(request()->except(['search','page']) as $key => $value)
        @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
    @endforeach
    <label class="min-w-0 flex-1">
        <span class="sr-only">{{ $placeholder }}</span>
        <input type="search" name="search" value="{{ request('search') }}" maxlength="120" placeholder="{{ $placeholder }}"
            class="min-h-11 w-full min-w-0 rounded-xl border border-slate-700 bg-dark-card px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-orange-500 focus:outline-none">
    </label>
    <button type="submit" class="min-h-11 shrink-0 rounded-xl bg-[#ff6b00] px-4 py-2 text-sm font-semibold text-white hover:bg-orange-600">Cari</button>
</form>
