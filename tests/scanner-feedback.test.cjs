const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const blade = fs.readFileSync(path.join(__dirname, '../resources/views/organizer/events/tabs/_scan_shared.blade.php'), 'utf8');
const script = blade.match(/<script>([\s\S]*?)<\/script>/)[1];

// Minimal DOM double enforcing the browser DOMTokenList whitespace contract.
function setup(response, { iconFailure = false } = {}) {
    const elements = new Map();
    const logs = [];
    const timers = new Map();
    let timerId = 0;
    let calls = 0;
    const document = {
        activeElement: null,
        addEventListener() {},
        getElementById(id) {
            if (!elements.has(id)) {
                const element = {
                    className: '', value: '', textContent: '', innerHTML: '', style: {}, listeners: {},
                    addEventListener(name, fn) { this.listeners[name] = fn; },
                    focus() { document.activeElement = this; },
                    blur() { document.activeElement = null; },
                };
                element.classList = {
                    add(...tokens) {
                        if (tokens.some(token => /\s/.test(token))) throw new DOMException('Token contains whitespace', 'InvalidCharacterError');
                        element.className += ' ' + tokens.join(' ');
                    },
                    remove(...tokens) { element.className = element.className.split(/\s+/).filter(token => !tokens.includes(token)).join(' '); },
                    toggle() {},
                };
                elements.set(id, element);
            }
            return elements.get(id);
        },
    };
    const window = {};
    const context = vm.createContext({
        document, window, DOMException,
        localStorage: { getItem() { return null; }, setItem() {} },
        console: { log() {}, error(...args) { logs.push(args); } },
        lucide: { createIcons() { if (iconFailure) throw new Error('Synthetic icon render failure'); } },
        requestAnimationFrame(fn) { fn(); },
        setTimeout(fn) { const id = ++timerId; timers.set(id, fn); return id; },
        clearTimeout(id) { timers.delete(id); },
        fetch: async () => { calls++; if (response instanceof Error) throw response; return response; },
    });
    vm.runInContext(script, context);
    async function settle() { for (let i = 0; i < 20; i++) await Promise.resolve(); }
    return {
        elements, logs, timers, window, settle,
        calls: () => calls,
        title: () => document.getElementById('scan-result-title').textContent,
        message: () => document.getElementById('scan-result-message').textContent,
        async manual(code = 'SYNTHETIC-01') {
            document.getElementById('manual-input').value = code;
            window.submitManual({ preventDefault() {} });
            await settle();
        },
        async enter(code = '{"ticket_code":"SYNTHETIC-01","event_id":1}') {
            window.toggleScanner();
            const input = document.getElementById('scan-input');
            input.value = code;
            input.listeners.input({ target: input });
            input.listeners.keydown({ key: 'Enter', preventDefault() {} });
            await settle();
        },
    };
}

function json(status, body) {
    return { ok: status >= 200 && status < 300, status, headers: { get() { return 'application/json'; } }, json: async () => body };
}

test('200 valid=true renders success without DOM token exception', async () => {
    const ui = setup(json(200, { valid: true }));
    await ui.manual();
    assert.equal(ui.title(), 'Tiket berhasil divalidasi');
    assert.equal(ui.message(), 'Check-in peserta berhasil dicatat.');
    assert.equal(ui.calls(), 1);
});

for (const [status, data, title] of [
    [422, { valid: false, used_at: '18/10/2026 10:00', message: 'Tiket ini sudah digunakan.' }, 'Tiket sudah digunakan'],
    [422, { valid: false, message: 'Hanya tiket aktif yang dapat di-scan.' }, 'Validasi ditolak'],
    [404, { valid: false, message: 'Tiket tidak ditemukan untuk acara ini.' }, 'Validasi ditolak'],
    [403, { valid: false, message: 'Pemindaian tiket tidak diizinkan.' }, 'Validasi ditolak'],
]) {
    test(`${status} ${data.message} renders confirmed rejection`, async () => {
        const ui = setup(json(status, data));
        await ui.manual();
        assert.equal(ui.title(), title);
        assert.equal(ui.message(), data.used_at ? 'QR Code ini telah divalidasi sebelumnya.' : data.message);
    });
}

for (const [name, response] of [
    ['HTML', { ok: false, headers: { get() { return 'text/html'; } } }],
    ['invalid JSON', { ok: true, headers: { get() { return 'application/json'; } }, json: async () => { throw new SyntaxError('invalid'); } }],
    ['null JSON', json(200, null)],
    ['wrong success shape', json(200, { valid: 'true' })],
    ['timeout', new Error('Synthetic timeout')],
]) {
    test(`${name} reports uncertainty without retry`, async () => {
        const ui = setup(response);
        await ui.manual();
        assert.equal(ui.title(), 'Hasil validasi belum dapat dipastikan');
        assert.equal(ui.calls(), 1);
    });
}

test('icon rendering failure preserves confirmed success and logs only safe diagnostic', async () => {
    const ui = setup(json(200, { valid: true }), { iconFailure: true });
    await ui.manual();
    assert.equal(ui.title(), 'Tiket berhasil divalidasi');
    assert.equal(ui.logs.length, 1);
    assert.equal(JSON.stringify(ui.logs).includes('SYNTHETIC-01'), false);
});

test('hardware input plus Enter sends once and cancels input timer', async () => {
    const ui = setup(json(200, { valid: true }));
    await ui.enter();
    assert.equal(ui.calls(), 1);
    assert.equal(ui.title(), 'Tiket berhasil divalidasi');
    assert.equal(ui.elements.get('scan-input').value, '');
});

test('overlapping submit is guarded; intentional later scan may be rejected as used', async () => {
    const data = { valid: true };
    const ui = setup(json(200, data));
    const first = ui.manual();
    const overlap = ui.manual();
    await Promise.all([first, overlap]);
    assert.equal(ui.calls(), 1);
    data.valid = false;
    data.used_at = '18/10/2026 10:00';
    data.message = 'Tiket ini sudah digunakan.';
    await ui.manual();
    assert.equal(ui.calls(), 2);
    assert.equal(ui.title(), 'Tiket sudah digunakan');
});
