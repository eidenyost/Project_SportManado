<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[method="GET" i] input[name="search"]').forEach(input => {
        const form = input.form;
        if (!form || form.dataset.searchReady) return;
        form.dataset.searchReady = '1';
        input.maxLength = 120;
        let timer;
        const focusKey = 'sportmanado-search-focus:' + location.pathname;
        if (sessionStorage.getItem(focusKey)) {
            sessionStorage.removeItem(focusKey);
            input.focus({preventScroll:true});
            if (input.type !== 'search') input.setSelectionRange(input.value.length, input.value.length);
        }
        form.addEventListener('submit', event => {
            event.preventDefault();
            clearTimeout(timer);
            const url = new URL(form.action || location.href);
            url.search = location.search;
            new FormData(form).forEach((value, key) => {
                if (typeof value === 'string' && value.trim()) url.searchParams.set(key, value.trim());
                else url.searchParams.delete(key);
            });
            url.searchParams.delete('page');
            if (url.href !== location.href) location.assign(url.href);
        });
        const changed = event => {
            clearTimeout(timer);
            if (event.isComposing) return;
            const search = input.value.trim();
            const current = new URL(location.href).searchParams.get('search') || '';
            if (search === current) return;
            timer = setTimeout(() => {
                sessionStorage.setItem(focusKey, '1');
                form.requestSubmit();
            }, search ? 450 : 0);
        };
        input.addEventListener('input', changed);
        input.addEventListener('search', changed);
        input.addEventListener('compositionend', changed);
    });
});
</script>
