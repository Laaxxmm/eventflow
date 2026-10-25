import { Html5Qrcode } from 'html5-qrcode';

const scanBtn = document.getElementById('start-scan');
const stopBtn = document.getElementById('stop-scan');
const box = document.getElementById('scanner-box');
const live = document.getElementById('checkin-live');
const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

let scanner = null;
let locked = false;

function showLive(ok, duplicate, message) {
    if (!live) return;
    live.classList.remove('hidden', 'bg-green-50', 'bg-amber-50', 'bg-red-50', 'text-green-900', 'text-amber-900', 'text-red-800');
    if (!ok) {
        live.classList.add('bg-red-50', 'text-red-800');
    } else if (duplicate) {
        live.classList.add('bg-amber-50', 'text-amber-900');
    } else {
        live.classList.add('bg-green-50', 'text-green-900');
    }
    live.textContent = message;
}

async function submitPayload(payload) {
    const response = await fetch('/checkin', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify({ payload }),
    });
    const data = await response.json().catch(() => ({ ok: false, message: 'Check-in failed.' }));
    showLive(Boolean(data.ok), Boolean(data.duplicate), data.message || 'Checked in.');
    locked = false;
}

scanBtn?.addEventListener('click', async () => {
    box?.classList.remove('hidden');
    scanner = new Html5Qrcode('qr-reader');
    try {
        locked = false;
        await scanner.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 230, height: 230 } },
            async (text) => {
                if (locked) return;
                locked = true;
                try {
                    await scanner.stop();
                } catch (e) {
                    /* camera already stopped */
                }
                box?.classList.add('hidden');
                await submitPayload(text);
            },
            () => {},
        );
    } catch (e) {
        box?.classList.add('hidden');
        showLive(false, false, 'Could not start the camera. Enter the pass number instead.');
    }
});

stopBtn?.addEventListener('click', async () => {
    if (scanner) {
        try { await scanner.stop(); } catch (e) { /* already stopped */ }
        try { scanner.clear(); } catch (e) { /* nothing to clear */ }
    }
    box?.classList.add('hidden');
});
