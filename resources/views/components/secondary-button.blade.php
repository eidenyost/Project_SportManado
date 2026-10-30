<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-[#0d1527] border border-[#1e293b] rounded-xl font-bold text-xs text-slate-300 uppercase tracking-widest shadow-sm hover:bg-[#1e293b] focus:outline-none focus:ring-2 focus:ring-[#ff6b00] focus:ring-offset-2 focus:ring-offset-[#0d1527] disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
