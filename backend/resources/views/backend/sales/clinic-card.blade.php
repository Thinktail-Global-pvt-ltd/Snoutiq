<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $clinicName }} • Booking Card</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background: #dfeaff;
            padding: 32px 18px 48px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
        }
        .page-shell {
            width: 420px;
            max-width: 100%;
        }
        .download-btn {
            position: sticky;
            top: 10px;
            width: 100%;
            border: none;
            border-radius: 999px;
            padding: 0.95rem 1.4rem;
            background: linear-gradient(135deg, #4d7bff, #6c6cff);
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 15px 35px rgba(77, 123, 255, 0.35);
            margin-bottom: 22px;
        }
        .qr-card {
            position: relative;
            width: 100%;
            background: #f4f8ff;
            border-radius: 36px;
            padding: 40px 32px 38px;
            box-shadow: 0 22px 40px rgba(70, 86, 128, 0.18);
            text-align: center;
            overflow: hidden;
        }
        .qr-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 36px;
            border: 12px solid #3172ff;
            pointer-events: none;
        }
        .qr-card > * {
            position: relative;
            z-index: 1;
        }
        .brand-pill {
            background: #fff;
            color: #3172ff;
            border-radius: 999px;
            font-weight: 800;
            padding: 6px 28px;
            display: inline-block;
            letter-spacing: 0.05em;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }
        .hero-text {
            color: #fff;
            background: #0f48d5;
            border-radius: 999px;
            display: inline-block;
            padding: 6px 24px;
            font-weight: 600;
            letter-spacing: 0.04em;
            margin-bottom: 22px;
        }
        .clinic-title {
            color: #0d1d4d;
            font-weight: 800;
            font-size: 1.5rem;
            text-transform: uppercase;
            margin-bottom: 22px;
            letter-spacing: 0.05em;
        }
        .qr-wrapper {
            width: 260px;
            height: 260px;
            margin: 0 auto 28px;
            background: #fff;
            border-radius: 32px;
            padding: 22px;
            box-shadow: inset 0 0 0 3px #d5ddff;
        }
        .qr-code {
            width: 100%;
            height: 100%;
            border-radius: 20px;
            border: 2px solid #e4e9ff;
            padding: 10px;
            background: #fff;
        }
        .qr-code img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        .clinic-id-label {
            font-size: 0.85rem;
            color: #5b668c;
            letter-spacing: 0.25em;
        }
        .clinic-id-value {
            font-weight: 800;
            font-size: 1.35rem;
            color: #0d48d5;
            letter-spacing: 0.2em;
            margin: 6px 0 26px;
        }
        .feature-grid {
            display: flex;
            justify-content: space-between;
            gap: 16px;
        }
        .feature-item {
            flex: 1;
            text-align: center;
        }
        .feature-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 10px;
            border: 2px solid #0f48d5;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
        }
        .feature-item svg {
            width: 34px;
            height: 34px;
            stroke: #0f48d5;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .feature-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #0f1b36;
            letter-spacing: 0.04em;
        }
    </style>
</head>
<body>
<div class="page-shell">
    <button class="download-btn" onclick="downloadCard()">Download booking card</button>
    <div class="qr-card" id="bookingCard">
        <div class="brand-pill">SNOUTIQ</div>
        <div class="hero-text">Scan To Download App!</div>
        <div class="clinic-title">{{ $clinicName }}</div>

        <div class="qr-wrapper">
            <div class="qr-code">
                <img src="{{ $qrDataUri }}" alt="QR for {{ $clinicName }}">
            </div>
        </div>

        <div class="clinic-id-label">CLINIC-ID</div>
        <div class="clinic-id-value">{{ $referralCode }}</div>

        <div class="feature-grid">
            <div class="feature-item">
                <div class="feature-icon">
                    <svg viewBox="0 0 48 48" fill="none" stroke-width="2.5">
                        <rect x="13" y="4" width="22" height="40" rx="4"></rect>
                        <polyline points="19,18 24,23 29,18"></polyline>
                        <line x1="24" y1="23" x2="24" y2="31"></line>
                    </svg>
                </div>
                <div class="feature-label">DOWNLOAD APP</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon">
                    <svg viewBox="0 0 48 48" fill="none" stroke-width="2.5">
                        <circle cx="16" cy="14" r="6"></circle>
                        <path d="M10 30c2-4 10-4 12 0"></path>
                        <circle cx="34" cy="18" r="6"></circle>
                        <path d="M28 34c2-4 10-4 12 0"></path>
                        <path d="M28 26l4 4"></path>
                    </svg>
                </div>
                <div class="feature-label">VIDEO CONSULT</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon">
                    <svg viewBox="0 0 48 48" fill="none" stroke-width="2.5">
                        <rect x="10" y="10" width="28" height="28" rx="6"></rect>
                        <path d="M19 19h10v10H19z"></path>
                        <path d="M24 12v6"></path>
                        <path d="M24 30v6"></path>
                        <path d="M12 24h6"></path>
                        <path d="M30 24h6"></path>
                    </svg>
                </div>
                <div class="feature-label">AI SYMPTOM CHECK</div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
    function downloadCard() {
        const node = document.getElementById('bookingCard');
        html2canvas(node, {scale: 2}).then(canvas => {
            const link = document.createElement('a');
            link.download = '{{ \Illuminate\Support\Str::slug($clinicName) }}-booking-card.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        });
    }
</script>
</body>
</html>
