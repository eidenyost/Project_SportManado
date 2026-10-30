<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-[#ff6b00] border border-transparent rounded-xl font-bold text-xs text-white uppercase tracking-widest hover:bg-[#e05e00] focus:bg-[#e05e00] active:bg-[#e05e00] focus:outline-none focus:ring-2 focus:ring-[#ff6b00] focus:ring-offset-2 focus:ring-offset-[#0d1527] transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
