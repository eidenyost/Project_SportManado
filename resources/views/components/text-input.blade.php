@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-[#1e293b] bg-[#080c14] text-gray-300 focus:border-[#ff6b00] focus:ring-[#ff6b00] rounded-xl shadow-sm']) }}>
