<style>
.mono { font-family: 'Courier New', Courier, monospace; }
/* Portal visual feedback */
#scan-portal.scan-success { border-color: rgb(52 211 153 / 0.5); box-shadow: 0 0 0 1px rgb(52 211 153 / 0.15); }
#scan-portal.scan-error   { border-color: rgb(248 113 113 / 0.5); box-shadow: 0 0 0 1px rgb(248 113 113 / 0.15); }
</style>

<div class="w-full max-w-2xl mx-auto space-y-5" data-scan-root>

    <!-- Toast notification -->
    <div id="toast"
         class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 hidden items-start gap-4 w-full max-w-lg rounded-2xl px-6 py-5 border shadow-lg transition-all duration-300"
         style="bottom:calc(1.5rem + env(safe-area-inset-bottom, 0px))"
         role="alert">
        <div id="toast-icon" class="flex-shrink-0 mt-0.5"></div>
        <div class="flex-1 min-w-0">
            <p id="toast-title" class="text-base font-bold"></p>
            <p id="toast-message" class="text-sm mt-1"></p>
        </div>
        <button type="button" onclick="this.parentElement.classList.add('hidden')" class="text-current/60 hover:text-current flex-shrink-0 text-xl font-bold leading-none">&times;</button>
    </div>

    <!-- HARDWARE SCAN -->
    <div id="scan-portal"
         class="relative flex flex-col items-center gap-4 overflow-hidden p-8 border border-slate-800/40 rounded-xl bg-dark-card select-none transition-all duration-200">

        <input type="text" id="scan-input" autocomplete="off"
               aria-label="Scanner input" tabindex="0"
               class="absolute inset-0 z-[1] w-full h-full opacity-0 pointer-events-none cursor-pointer border-none outline-none bg-transparent text-transparent caret-transparent text-base">

        <!-- DISCONNECTED STATE -->
        <div id="hw-state-disconnected" class="relative z-[2] flex flex-col items-center gap-4 w-full">
            <div class="text-center">
                <p class="text-sm font-bold text-slate-400">Scanner Tidak Terhubung</p>
                <p class="text-xs text-slate-600 mt-1">Colok scanner USB, lalu klik tombol di bawah</p>
            </div>
            <button type="button" id="scanner-connect-btn"
                    class="px-6 py-2.5 rounded-xl text-sm font-bold text-white bg-[#ff6b00] hover:bg-[#e05e00] cursor-pointer border-none transition-all"
                    onclick="toggleScanner()">
                Hubungkan Scanner
            </button>
        </div>

        <!-- CONNECTED STATE -->
        <div id="hw-state-connected" class="relative z-[2] hidden flex-col items-center gap-5 w-full">
            <div class="flex items-center justify-center gap-1.5">
                <i id="icon-success" data-lucide="check-circle" class="w-4 h-4 text-emerald-500 hidden flex-shrink-0"></i>
                <i id="icon-error" data-lucide="x-circle" class="w-4 h-4 text-red-500 hidden flex-shrink-0"></i>
                <p id="scan-status-label" class="text-sm font-bold text-emerald-400">Scanner siap. Pindai QR sekarang.</p>
            </div>

            <div class="text-center space-y-1">
                <div class="flex items-center justify-center gap-0.5">
                    <span id="input-display" class="mono text-base text-slate-300 tracking-widest">
                        Siap menerima scan&hellip;
                    </span>
                    <span id="input-cursor" class="w-px h-5 bg-[#ff6b00] opacity-0"></span>
                </div>
                <p id="scan-mode-label" class="text-xs text-slate-600"></p>
            </div>

            <div class="flex justify-center w-full">
                <button type="button" id="scanner-disconnect-btn"
                        class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-xs font-bold text-red-400 border border-red-500/40 cursor-pointer hover:border-red-400 hover:text-red-300 transition-all mt-1"
                        onclick="toggleScanner()">
                    Putuskan Koneksi
                </button>
            </div>
        </div>
    </div>

    <!-- Persistent result: the toast supplements this feedback but does not replace it. -->
    <section id="scan-result"
             class="hidden rounded-xl border px-4 py-3"
             role="status"
             aria-live="assertive"
             aria-atomic="true">
        <p id="scan-result-title" class="text-sm font-bold"></p>
        <p id="scan-result-message" class="mt-1 text-sm"></p>
    </section>

    <!-- Manual Input -->
    <div class="border-t border-slate-800/40 pt-5">
        <form id="manual-form" class="max-w-md mx-auto" onsubmit="submitManual(event)">
            <div class="flex items-center gap-2 mb-3">
                <i data-lucide="keyboard" class="w-4 h-4 text-[#ff6b00]"></i>
                <p class="text-xs font-semibold text-slate-400">Ketik Kode Tiket Manual</p>
            </div>
            <div class="flex gap-2">
                <div class="relative flex-1">
                    <input type="text" id="manual-input"
                           placeholder="SM-ABCD12EF"
                           maxlength="20"
                           autocomplete="off"
                           class="w-full mono px-4 py-2.5 bg-[#080c14] border border-slate-800 rounded-xl text-sm text-white
                                  placeholder-slate-500 uppercase tracking-widest focus:outline-none
                                  focus:ring-2 focus:ring-[#ff6b00] focus:border-transparent transition">
                    <button type="button" id="manual-clear"
                            class="absolute right-3 top-1/2 -translate-y-1/2 hidden text-slate-500 hover:text-white text-lg leading-none">&times;</button>
                </div>
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-[#ff6b00] hover:bg-[#e05e00] flex-shrink-0">
                    Validasi
                </button>
            </div>
            <div id="manual-result" class="mt-2 hidden items-center gap-2 text-xs"></div>
        </form>
    </div>
</div>

<script>
(function() {
    const VALIDATE_URL   = "{{ route('organizer.events.validate-qr', $event) }}";
    const CSRF_TOKEN     = "{{ csrf_token() }}";

    let scanBuffer  = '';
    let scanTimeout = null;
    let isProcessing = false;
    let hardwareFocusActive = false;
    let scannerConnected = false;
    let lastScanTime = 0;
    let toastTimer = null;
    let keyTimes = [];

    const scanInput    = document.getElementById('scan-input');
    const scanPortal   = document.getElementById('scan-portal');
    const inputDisplay = document.getElementById('input-display');
    const inputCursor  = document.getElementById('input-cursor');
    const hwDisconnected = document.getElementById('hw-state-disconnected');
    const hwConnected = document.getElementById('hw-state-connected');
    const scanModeLabel = document.getElementById('scan-mode-label');
    const manualInput   = document.getElementById('manual-input');
    const manualClear   = document.getElementById('manual-clear');
    const manualResult  = document.getElementById('manual-result');

    function showPersistentResult(type, title, message) {
        const result = document.getElementById('scan-result');
        const resultTitle = document.getElementById('scan-result-title');
        const resultMessage = document.getElementById('scan-result-message');
        if (!result || !resultTitle || !resultMessage) return;

        result.className = 'rounded-xl border px-4 py-3';
        result.className += ' ' + (
            type === 'success'
                ? 'border-emerald-800/70 bg-emerald-950/50 text-emerald-200'
                : 'border-red-800/70 bg-red-950/50 text-red-200'
        );
        resultTitle.textContent = title;
        resultMessage.textContent = message;
        result.classList.remove('hidden');

        if (manualResult) {
            manualResult.className = `mt-2 flex items-center gap-2 text-xs ${type === 'success' ? 'text-emerald-400' : 'text-red-400'}`;
            manualResult.textContent = `${title}: ${message}`;
        }
    }

    /* ── Toast ── */
    function showToast(type, title, message) {
        const toast = document.getElementById('toast');
        const icon = document.getElementById('toast-icon');
        const t = document.getElementById('toast-title');
        const m = document.getElementById('toast-message');

        clearTimeout(toastTimer);
        toast.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 z-50 flex items-start gap-4 w-full max-w-lg rounded-2xl px-6 py-5 border shadow-lg transition-all duration-300';
        toast.style.bottom = 'calc(1.5rem + env(safe-area-inset-bottom, 0px))';

        if (type === 'success') {
            toast.className += ' bg-emerald-950 border-emerald-800 text-emerald-200';
            icon.innerHTML = '<i data-lucide="check-circle" class="w-5 h-5 text-emerald-400"></i>';
        } else {
            toast.className += ' bg-red-950 border-red-800 text-red-200';
            icon.innerHTML = '<i data-lucide="x-circle" class="w-5 h-5 text-red-400"></i>';
        }
        t.textContent = title;
        m.textContent = message;
        toast.classList.remove('hidden');
        showPersistentResult(type, title, message);

        if (typeof lucide !== 'undefined') {
            lucide.createIcons({ nodes: [icon] });
        }

        const duration = type === 'success' ? 3000 : 8000;
        toastTimer = setTimeout(() => toast.classList.add('hidden'), duration);
    }

    /* ── Code Extractor ── */
    function extractTicketCode(raw) {
        if (!raw || typeof raw !== 'string') return null;
        let cleaned = raw.replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g, '').trim();
        cleaned = cleaned.replace(/^(\]C1|\]d2|\]Q1|\]J1|\]e0)/, '').trim();
        cleaned = cleaned.replace(/[\r\n]+$/, '').trim();
        if (!cleaned) return null;
        try {
            const parsed = JSON.parse(cleaned);
            if (parsed && parsed.ticket_code) return String(parsed.ticket_code).trim().toUpperCase();
        } catch (e) {}
        const code = cleaned.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
        return code.length > 0 ? code : null;
    }

    /* ── Hardware ── */
    function scanDebug(...args) {
        try { if (localStorage.getItem('scan_debug')) console.log('[SCAN]', ...args); } catch (_) {}
    }

    function connectScanner() {
        scannerConnected = true;
        hardwareFocusActive = true;
        if (hwDisconnected) hwDisconnected.classList.add('hidden');
        if (hwConnected) hwConnected.classList.remove('hidden');
        if (scanPortal) setPortalState('connected');
        if (inputDisplay) inputDisplay.textContent = 'Siap menerima scan\u2026';
        if (scanModeLabel) scanModeLabel.textContent = 'Keyboard Wedge';
        focusScanInput();
        scanDebug('scanner connected');
    }

    function disconnectScanner() {
        scannerConnected = false;
        hardwareFocusActive = false;
        scanBuffer = '';
        scanInput.value = '';
        clearTimeout(scanTimeout);
        if (inputDisplay) inputDisplay.textContent = 'Siap menerima scan\u2026';
        if (inputCursor) inputCursor.style.opacity = '0';
        if (hwConnected) hwConnected.classList.add('hidden');
        if (hwDisconnected) hwDisconnected.classList.remove('hidden');
        if (scanPortal) setPortalState('disconnected');
        if (scanInput) scanInput.blur();
        scanDebug('scanner disconnected');
    }

    function toggleScanner() {
        if (scannerConnected) {
            disconnectScanner();
        } else {
            connectScanner();
        }
    }

    window.toggleScanner = toggleScanner;
    window.disconnectScanner = disconnectScanner;



    function startHardwareFocus() {
        if (!scannerConnected) {
            scanDebug('startHardwareFocus skipped - not connected');
            return;
        }
        hardwareFocusActive = true;
        focusScanInput();
        scanDebug('hardware focus active');
    }

    function stopHardwareFocus() {
        hardwareFocusActive = false;
        if (scanInput) scanInput.blur();
    }

    function focusScanInput() {
        if (scannerConnected && hardwareFocusActive && scanInput) {
            scanInput.focus({ preventScroll: true });
            if (document.activeElement !== scanInput) {
                requestAnimationFrame(() => scanInput.focus({ preventScroll: true }));
            }
        }
    }

    window.focusScanInput = focusScanInput;

    document.addEventListener('click', (e) => {
        if (scannerConnected && hardwareFocusActive && !e.target.closest('#manual-form') && !e.target.closest('#manual-input')) {
            focusScanInput();
        }
    });

    if (scanInput) {
        scanInput.addEventListener('keydown', (e) => {
            if (['Shift','Control','Alt','Meta','CapsLock','Tab','Escape'].includes(e.key)) return;

            keyTimes.push(Date.now());
            if (keyTimes.length > 20) keyTimes.shift();
            if (!scannerConnected && keyTimes.length >= 8) {
                const timeWindow = keyTimes[keyTimes.length - 1] - keyTimes[keyTimes.length - 8];
                if (timeWindow < 600) {
                    scanDebug('auto-detect: keyboard wedge pattern detected');
                    connectScanner();
                }
            }

            if (!scannerConnected) return;
            if (!hardwareFocusActive) { scanDebug('keydown ignored (focus inactive):', e.key); return; }

            if (e.key === 'Enter' || e.key === '\r' || e.key === '\n' || e.key === 'Return') {
                e.preventDefault();
                const now = Date.now();
                if (now - lastScanTime < 1000) {
                    scanBuffer = '';
                    scanInput.value = '';
                    return;
                }
                lastScanTime = now;
                clearTimeout(scanTimeout);
                const raw = scanInput.value;
                scanBuffer = '';
                scanInput.value = '';
                const code = extractTicketCode(raw);
                if (code) {
                    if (inputDisplay) inputDisplay.textContent = 'Memproses\u2026';
                    processCode(code);
                } else {
                    if (inputDisplay) inputDisplay.textContent = 'Kode tidak valid. Coba lagi\u2026';
                    setTimeout(() => { if (inputDisplay) inputDisplay.textContent = 'Siap menerima scan\u2026'; }, 2000);
                }
                return;
            }

            if (inputDisplay) inputDisplay.textContent = scanInput.value.toUpperCase() || 'Siap menerima scan\u2026';
            if (inputCursor) inputCursor.style.opacity = '1';
            clearTimeout(scanTimeout);
            scanTimeout = setTimeout(() => {
                scanBuffer = '';
                scanInput.value = '';
                if (inputDisplay) inputDisplay.textContent = 'Siap menerima scan\u2026';
                if (inputCursor) inputCursor.style.opacity = '0';
            }, 3000);
        });

        scanInput.addEventListener('input', (e) => {
            if (!scannerConnected) return;
            const val = e.target.value;
            if (!val) {
                scanBuffer = '';
                if (inputDisplay) inputDisplay.textContent = 'Siap menerima scan\u2026';
                if (inputCursor) inputCursor.style.opacity = '0';
                return;
            }
            scanBuffer = val;
            if (inputDisplay) inputDisplay.textContent = val.toUpperCase();
            if (inputCursor) inputCursor.style.opacity = '1';
            clearTimeout(scanTimeout);
            const captured = val;
            scanTimeout = setTimeout(() => {
                scanBuffer = '';
                scanInput.value = '';
                const code = extractTicketCode(captured);
                if (code && !isProcessing) {
                    if (inputDisplay) inputDisplay.textContent = 'Memproses\u2026';
                    processCode(code);
                } else {
                    if (inputDisplay) inputDisplay.textContent = 'Siap menerima scan\u2026';
                    if (inputCursor) inputCursor.style.opacity = '0';
                }
            }, 500);
        });

        scanInput.addEventListener('focus', () => {
            if (!hardwareFocusActive) return;
            if (scanPortal && scannerConnected) setPortalState('connected');
            if (inputCursor) inputCursor.style.opacity = '1';
        });

        scanInput.addEventListener('blur', () => {
            if (scanPortal && scannerConnected) setPortalState('connected');
            if (inputCursor) inputCursor.style.opacity = '0';
        });
    }

    /* ── Manual Form ── */
    function submitManual(e) {
        e.preventDefault();
        const rawInput = document.getElementById('manual-input');
        if (!rawInput) return;
        const code = extractTicketCode(rawInput.value.trim());
        if (!code) {
            showToast('error', 'Kode tiket tidak valid', 'Masukkan kode tiket yang dapat dibaca lalu coba lagi.');
            rawInput.focus();
            return;
        }
        rawInput.value = '';
        processCode(code);
        if (scannerConnected) focusScanInput();
    }

    window.submitManual = submitManual;

    /* ── Manual Input Enhancements ── */
    if (manualInput && manualClear) {
        manualInput.addEventListener('input', () => {
            manualClear.classList.toggle('hidden', !manualInput.value);
        });
        manualClear.addEventListener('click', () => {
            manualInput.value = '';
            manualInput.focus();
            manualClear.classList.add('hidden');
        });
    }

    if (manualInput) {
        manualInput.addEventListener('focus', () => {
            if (scannerConnected) stopHardwareFocus();
        });
    }

    /* ── Validator ── */
    async function processCode(code) {
        if (isProcessing) return;
        isProcessing = true;
        let outcome = { valid: null, message: 'Validasi belum dapat dipastikan. Silakan periksa koneksi dan coba lagi.' };
        try {
            const res = await fetch(VALIDATE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
                body: JSON.stringify({ ticket_code: code }),
            });
            const contentType = res.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                const data = await res.json();
                if (data && typeof data === 'object' && !Array.isArray(data)
                    && ((res.ok && data.valid === true)
                        || (data.valid === false && typeof data.message === 'string'))) {
                    outcome = data;
                }
            }
        } catch (_) {
            // Transport/JSON failure leaves the server outcome uncertain; never retry automatically.
        }

        try {
            handleResult(code, outcome.valid === true, outcome);
        } catch (_) {
            // A rendering error cannot invalidate an already confirmed server result.
            console.error('[SCAN] Feedback rendering failed.');
        } finally {
            isProcessing = false;
            if (inputDisplay) inputDisplay.textContent = 'Siap menerima scan\u2026';
            if (scannerConnected) {
                requestAnimationFrame(() => focusScanInput());
                requestAnimationFrame(() => focusScanInput());
            }
        }
    }

    function handleResult(code, ok, data) {
        let title;
        let message;
        if (ok) {
            title = 'Tiket berhasil divalidasi';
            message = 'Check-in peserta berhasil dicatat.';
        } else if (data.valid === null) {
            title = 'Hasil validasi belum dapat dipastikan';
            message = data.message;
        } else if (data.used_at || data.message === 'Tiket ini sudah digunakan.') {
            title = 'Tiket sudah digunakan';
            message = 'QR Code ini telah divalidasi sebelumnya.';
        } else {
            title = 'Validasi ditolak';
            message = data.message;
        }
        // Commit the primary text feedback before optional portal/audio/icon effects.
        showPersistentResult(ok ? 'success' : 'error', title, message);
        setPortalState(ok ? 'success' : 'error');
        playBeep(ok);
        showToast(ok ? 'success' : 'error', title, message);

        setTimeout(() => {
            if (scannerConnected) setPortalState('connected');
            else setPortalState('disconnected');
        }, 3000);
    }

    function setPortalState(state) {
        if (!scanPortal) return;
        const label = document.getElementById('scan-status-label');
        const iconOk = document.getElementById('icon-success');
        const iconErr = document.getElementById('icon-error');

        scanPortal.classList.remove('scan-success', 'scan-error', 'border-emerald-500/30', 'border-red-500/30');
        if (iconOk) iconOk.classList.add('hidden');
        if (iconErr) iconErr.classList.add('hidden');

        if (state === 'success') {
            scanPortal.classList.add('scan-success');
            if (iconOk) iconOk.classList.remove('hidden');
            if (label) label.textContent = 'Tiket Valid';
        } else if (state === 'error') {
            scanPortal.classList.add('scan-error');
            if (iconErr) iconErr.classList.remove('hidden');
            if (label) label.textContent = 'Tiket Ditolak';
        } else if (state === 'connected') {
            if (label) label.textContent = 'Scanner siap. Pindai QR sekarang.';
        } else if (state === 'disconnected') {
            if (label) label.textContent = 'Siap Menerima Scan Hardware';
        } else if (state === 'listening') {
            if (label) label.textContent = 'Memvalidasi\u2026';
        }
    }

    function playBeep(success) {
        try {
            const ctx  = new (window.AudioContext || window.webkitAudioContext)();
            const osc  = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            if (success) {
                osc.frequency.value = 880;
                gain.gain.setValueAtTime(.3, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(.0001, ctx.currentTime + .35);
                osc.type = 'sine';
            } else {
                osc.frequency.value = 200;
                gain.gain.setValueAtTime(.4, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(.0001, ctx.currentTime + .45);
                osc.type = 'sawtooth';
            }
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + .5);
        } catch (_) {}
    }

})();
</script>
