<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Draft Clinic • SnoutIQ</title>
    <style>
        :root {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #0f172a;
            background: #f8fafc;
        }
        body { margin: 0; padding: 0; }
        header { padding: 1.5rem 2rem; background: #1d4ed8; color: #fff; }
        main { max-width: 720px; margin: 2rem auto; padding: 0 1.5rem 3rem; }
        h1 { font-size: 1.9rem; margin-bottom: 0.5rem; }
        p.lead { margin: 0; opacity: 0.85; }
        form { background: #fff; border-radius: 1rem; box-shadow: 0 20px 45px -25px rgba(15, 23, 42, 0.45); padding: 2rem; display: grid; gap: 1.25rem; }
        .field { display: grid; gap: 0.4rem; }
        label { font-weight: 600; font-size: 0.95rem; }
        input, textarea, select { border: 1px solid #cbd5f5; border-radius: 0.75rem; padding: 0.8rem 1rem; font-size: 1rem; background: #f8fafc; }
        textarea { resize: vertical; min-height: 90px; }
        .actions { display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; }
        button { cursor: pointer; border-radius: 0.8rem; border: none; font-weight: 700; font-size: 1rem; padding: 0.8rem 1.6rem; }
        button[type="submit"] { background: linear-gradient(90deg, #2563eb, #0ea5e9); color: #fff; }
        button[disabled] { opacity: 0.65; cursor: not-allowed; }
        .note { font-size: 0.9rem; color: #475569; }
        .result { margin-top: 1.5rem; border-radius: 0.75rem; padding: 1.5rem; background: #ecfeff; border: 1px solid #bae6fd; display: none; }
        .result.success { display: block; }
        .result h2 { margin-top: 0; }
        .result a { color: #0369a1; font-weight: 600; word-break: break-all; }
        .error { color: #dc2626; font-size: 0.85rem; }
    </style>
</head>
<body>
<header>
    <h1>Create Draft Clinic</h1>
    <p class="lead">Sales can pre-create a clinic page, grab the QR, and share it with the vet before onboarding completes.</p>
</header>
<main>
    <form id="draft-clinic-form" autocomplete="off">
        @csrf
        <div class="field">
            <label for="name">Clinic / Vet Name</label>
            <input id="name" name="name" placeholder="Happy Tails Clinic" />
        </div>
        <div class="field">
            <label for="mobile">Phone Number</label>
            <input id="mobile" name="mobile" placeholder="10-digit phone" />
        </div>
        <div class="field">
            <label for="city">City</label>
            <input id="city" name="city" placeholder="Gurugram" />
        </div>
        <div class="field">
            <label for="area">Area / Locality</label>
            <input id="area" name="area" placeholder="DLF Phase 2" />
        </div>
        <div class="field">
            <label for="address">Address (optional)</label>
            <textarea id="address" name="address" placeholder="Building / street details"></textarea>
        </div>
        <div class="field">
            <label for="expires">Draft Expiry</label>
            <select id="expires" name="draft_expires_in_days">
                <option value="30">30 days</option>
                <option value="45">45 days</option>
                <option value="60" selected>60 days</option>
                <option value="90">90 days</option>
            </select>
        </div>
        <div class="field">
            <label for="notes">Internal Notes (optional)</label>
            <textarea id="notes" name="notes" placeholder="Any context for onboarding"></textarea>
            <span class="note">Notes are not public – they help the onboarding team follow up.</span>
        </div>
        <div class="actions">
            <button type="submit">Generate Draft</button>
            <span id="status-label" class="note"></span>
        </div>
        <div id="form-error" class="error"></div>
    </form>

    <section id="result" class="result">
        <h2>Draft Clinic Ready ✅</h2>
        <p><strong>Public page:</strong> <a id="public-url" target="_blank" rel="noopener"></a></p>
        <p><strong>Claim link:</strong> <a id="claim-url" target="_blank" rel="noopener"></a></p>
        <p>
            <strong>QR Code:</strong>
            <span class="note">Download &amp; print/share with the vet.</span>
        </p>
        <img id="qr-image" alt="Clinic QR code" style="display:none;max-width:220px;border-radius:0.5rem;border:1px solid #bae6fd;" />
    </section>
</main>

<script>
(() => {
    const form = document.getElementById('draft-clinic-form');
    const statusLabel = document.getElementById('status-label');
    const errorBox = document.getElementById('form-error');
    const resultBox = document.getElementById('result');
    const publicUrlEl = document.getElementById('public-url');
    const claimUrlEl = document.getElementById('claim-url');
    const qrImageEl = document.getElementById('qr-image');
    const apiEndpoint = @json($apiEndpoint);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        statusLabel.textContent = 'Saving draft…';
        errorBox.textContent = '';
        resultBox.classList.remove('success');

        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());

        Object.keys(payload).forEach((key) => {
            if (typeof payload[key] === 'string') {
                payload[key] = payload[key].trim();
                if (payload[key] === '') {
                    delete payload[key];
                }
            }
        });

        try {
            const response = await fetch(apiEndpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.message || 'Unable to create draft clinic.');
            }

            const data = await response.json();
            statusLabel.textContent = 'Draft created. Share the links below.';
            publicUrlEl.textContent = data.public_url;
            publicUrlEl.href = data.public_url;
            claimUrlEl.textContent = data.claim_url;
            claimUrlEl.href = data.claim_url;
            const imgSrc = data.qr_png_data_uri || data.qr_png_url;
            if (imgSrc) {
                qrImageEl.src = imgSrc;
                qrImageEl.style.display = 'block';
            } else {
                qrImageEl.removeAttribute('src');
                qrImageEl.style.display = 'none';
            }

            resultBox.classList.add('success');
            form.reset();
        } catch (err) {
            statusLabel.textContent = '';
            errorBox.textContent = err.message;
        }
    });
})();
</script>
</body>
</html>
