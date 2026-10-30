document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebar-toggle');
    const close = document.getElementById('sidebar-close');
    const overlay = document.getElementById('sidebar-overlay');
    const main = document.getElementById('main-content');
    if (!sidebar || !toggle || !overlay || !main) return;
    const desktop = window.matchMedia('(min-width: 768px)');
    let open = desktop.matches;
    toggle.setAttribute('aria-label', 'Buka menu navigasi');
    toggle.setAttribute('aria-controls', 'sidebar');
    close?.setAttribute('aria-label', 'Tutup menu navigasi');
    function update() {
        const modal = open && !desktop.matches;
        sidebar.style.transform = open ? 'translateX(0)' : 'translateX(-100%)';
        sidebar.inert = !open;
        toggle.setAttribute('aria-expanded', String(open));
        overlay.classList.toggle('hidden', !modal);
        document.body.classList.toggle('overflow-y-hidden', modal);
        main.inert = modal;
        main.style.setProperty('margin-left', desktop.matches ? 'calc(16rem + 1px)' : '0', 'important');
    }
    function dismiss() {
        open = false;
        update();
        toggle.focus();
    }
    toggle.addEventListener('click', () => {
        open = !open;
        update();
        if (open) (close || sidebar.querySelector('a,button'))?.focus();
    });
    close?.addEventListener('click', dismiss);
    overlay.addEventListener('click', dismiss);
    document.addEventListener('keydown', event => {
        if (!open || desktop.matches) return;
        if (event.key === 'Escape') { event.preventDefault(); dismiss(); }
        if (event.key === 'Tab') {
            const items = [...sidebar.querySelectorAll('a[href],button,input,select,textarea,[tabindex="0"]')]
                .filter(item => !item.disabled && item.getClientRects().length);
            const first = items[0], last = items.at(-1);
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
        }
    });
    desktop.addEventListener('change', () => { open = desktop.matches; update(); });
    update();
});
