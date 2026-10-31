@auth
<script>
(() => {
    let controller, timer;
    let generation = 0;
    window.syncNotificationCount = count => {
        if (!Number.isInteger(count) || count < 0) return;
        document.querySelectorAll('[data-notification-count], #notif-count').forEach(node => {
            node.textContent = count > 99 ? '99+' : String(count);
            if (node.hasAttribute('data-notification-count')) node.classList.toggle('hidden',count===0);
        });
        document.querySelector('#notif-badge')?.classList.toggle('hidden',count===0);
        document.querySelectorAll('[data-unread-total]').forEach(node => node.textContent=String(count));
    };
    const poll = async () => {
        clearTimeout(timer);
        if (document.hidden) return;
        controller?.abort();
        controller = new AbortController();
        const current = ++generation;
        try {
            const response = await fetch('{{ route("notifications.unread-count") }}', {headers:{Accept:'application/json'},signal:controller.signal});
            if (response.ok && current === generation) window.syncNotificationCount((await response.json()).count);
        } catch (_) {} finally { timer=setTimeout(poll,30000); }
    };
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
    window.addEventListener('pageshow', event => { if (event.persisted) poll(); });
    window.addEventListener('focus', poll);
    window.addEventListener('notifications:updated', event => {
        ++generation; controller?.abort(); window.syncNotificationCount(event.detail.count);
    });
    window.addEventListener('pagehide', () => { clearTimeout(timer); controller?.abort(); });
    poll();
})();
</script>
@endauth
