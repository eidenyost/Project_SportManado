(() => {
    const ticket = document.querySelector('[data-ticket-live]');
    const registration = document.querySelector('[data-registration-state-url]');
    let timer, controller, busy = false, leaving = false, lastSync = 0;
    let errors = 0, stopped = false, dirty = false, hiddenAt = Date.now();
    const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    const message = text => { const node = ticket?.querySelector('[data-live-message]'); if (node) node.textContent = text; };
    const pending = () => ticket?.dataset.ticketStatus === 'pending';
    const apply = data => {
        if (!ticket || leaving) return;
        const changed = data.ticket_status !== ticket.dataset.ticketStatus
            || (data.payment_status || '') !== ticket.dataset.paymentStatus;
        if (!changed) return;
        const url = new URL(data.ticket_url, window.location.href);
        if (url.origin !== window.location.origin) return;
        leaving = true;
        clearTimeout(timer);
        // Close only after our server has observed a final state. Closing Snap
        // itself must never mark a payment paid or cancel the reservation.
        window.snap?.hide?.();
        window.location.replace(url.href);
    };
    const check = async (reconcile = false) => {
        clearTimeout(timer);
        if (!ticket || stopped || leaving || busy || document.hidden) return;
        busy = true;
        controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 12000);
        try {
            const sync = reconcile && pending() && ticket.dataset.paymentStatus === 'pending'
                && Date.now() - lastSync >= 20000;
            if (sync) lastSync = Date.now();
            const response = await fetch(sync ? ticket.dataset.syncUrl : ticket.dataset.statusUrl, {
                method: sync ? 'POST' : 'GET', credentials: 'same-origin', cache: 'no-store',
                headers: { ...headers, ...(sync ? { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' } : {}) },
                signal: controller.signal,
            });
            if ([401, 403, 404, 419].includes(response.status) || response.redirected) {
                stopped = true;
                message('Sesi perlu diperiksa kembali. Muat ulang halaman untuk melanjutkan.');
                return;
            }
            if (!response.ok) throw new Error('status unavailable');
            apply(await response.json());
            errors = 0;
            if (!leaving) message(pending() ? 'Status diperbarui otomatis. Tiket akan tampil setelah pembayaran atau persetujuan diterima.' : '');
        } catch (_) {
            errors++;
            message('Belum dapat memperbarui status. Sistem akan mencoba kembali.');
        } finally {
            clearTimeout(timeout);
            busy = false;
            if (!stopped && !leaving && pending() && !document.hidden) {
                timer = setTimeout(() => check(true), Math.min(30000, 5000 * (errors + 1)));
            }
        }
    };
    const checkRegistration = async () => {
        if (!registration || document.hidden || leaving) return;
        try {
            const response = await fetch(registration.dataset.registrationStateUrl, { headers, cache: 'no-store', credentials: 'same-origin' });
            if (!response.ok || response.redirected) return;
            const data = await response.json();
            if (data.ticket_url) {
                const url = new URL(data.ticket_url, window.location.href);
                if (url.origin === window.location.origin) { leaving = true; window.location.replace(url.href); }
            }
        } catch (_) { /* Keep unsent form answers on temporary network failure. */ }
    };
    const returnPage = document.querySelector('[data-refresh-on-return]');
    document.addEventListener('input', event => { if (event.target.closest('form')) dirty = true; });
    const resume = (fromHistory = false) => {
        if (returnPage && !dirty && !leaving && (fromHistory || Date.now() - hiddenAt > 15000)) {
            leaving = true; window.location.reload(); return;
        }
        stopped = false;
        check(true);
        checkRegistration();
    };
    window.addEventListener('sportmanado:payment-result', () => check(true));
    window.addEventListener('pageshow', event => { if (event.persisted) resume(true); });
    window.addEventListener('focus', () => { if (!document.hidden) resume(); });
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) { hiddenAt = Date.now(); clearTimeout(timer); controller?.abort(); }
        else resume();
    });
    window.addEventListener('pagehide', () => { stopped = true; clearTimeout(timer); controller?.abort(); });
    if (pending()) check();
    checkRegistration();
})();
