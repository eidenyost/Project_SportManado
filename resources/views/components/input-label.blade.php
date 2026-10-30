@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-bold text-sm text-[#cbd5e1]']) }}>
    {{ $value ?? $slot }}
</label>
