import { Html5Qrcode } from 'html5-qrcode';

// Check-in camera (checkin/index.blade.php). Fills the same three result cards the server renders:
// [data-result="ok|dup|bad"] with data-t-title/line1/line2 templates (:name, :pass, :time, :message).
const root = document.querySelector('[data-checkin]');
const box = document.getElementById('scanner-box');
const startBtn = document.getElementById('start-scan');
const stopBtn = document.getElementById('stop-scan');
const nextBtn = document.getElementById('scan-next');
const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

let scanner = null;
let locked = false;

function showResult(state, values, title = null) {
    document.querySelectorAll('[data-result]').forEach((card) => {
        const on = card.dataset.result === state;
        card.classList.toggle('hidden', !on);
        if (!on) return;
        const fill = (t) => (t || '').replace(/:name|:pass|:time|:message/g, (k) => values[k.slice(1)] ?? '');
        card.querySelector('[data-field="title"]').textContent = title ?? fill(card.dataset.tTitle);
        card.querySelector('[data-field="line1"]').textContent = fill(card.dataset.tLine1);
        card.querySelector('[data-field="line2"]').textContent = fill(card.dataset.tLine2);
    });
}

function setScanning(on) {
    box?.toggleAttribute('data-scanning', on);
    startBtn?.classList.toggle('hidden', on);
    stopBtn?.classList.toggle('hidden', !on);
}

async function stop() {
    if (scanner) {
        try { await scanner.stop(); } catch (e) { /* already stopped */ }
        try { scanner.clear(); } catch (e) { /* nothing to clear */ }
        scanner = null;
    }
    setScanning(false);
}

async function submitPayload(payload) {
    let data;
    try {
        const response = await fetch('/checkin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ payload }),
        });
        data = await response.json();
    } catch (e) {
        data = { ok: false, message: root?.dataset.failed };
    }
    const values = { name: data.participant_name, pass: data.pass_no, time: data.checked_in_at, message: data.message };
    showResult(!data.ok ? 'bad' : data.duplicate ? 'dup' : 'ok', values);
    nextBtn?.classList.remove('hidden');
    locked = false;
}

async function start() {
    await stop();
    setScanning(true);
    scanner = new Html5Qrcode('qr-reader');
    locked = false;
    try {
        await scanner.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 210, height: 210 } },
            async (text) => {
                if (locked) return;
                locked = true;
                await stop();
                await submitPayload(text);
            },
            () => {},
        );
    } catch (e) {
        await stop();
        showResult('bad', { message: root?.dataset.cameraError }, root?.dataset.cameraTitle);
    }
}

startBtn?.addEventListener('click', start);
nextBtn?.addEventListener('click', start);
stopBtn?.addEventListener('click', stop);
document.querySelector('[data-tab="type"]')?.addEventListener('click', stop);
