<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $clinicName }} • Booking Card</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Mono:wght@700&display=swap');
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
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 40px rgba(77, 123, 255, 0.45);
        }
        button.download-btn {
            cursor: pointer;
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
            font-family: 'Space Mono', 'Fira Code', 'JetBrains Mono', 'SFMono-Regular', 'Consolas', monospace;
            font-variant-numeric: tabular-nums;
            font-feature-settings: "zero" 1;
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
            width: 76px;
            height: 76px;
            margin: 0 auto 10px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: linear-gradient(145deg, #fefeff, #e0e9ff);
            box-shadow: inset 0 0 0 1px rgba(15, 24, 60, 0.06), 0 16px 30px rgba(16, 33, 82, 0.15);
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .feature-icon:hover {
            transform: translateY(-2px);
            box-shadow: inset 0 0 0 1px rgba(15, 24, 60, 0.08), 0 20px 36px rgba(16, 33, 82, 0.2);
        }
        .feature-item svg {
            width: 44px;
            height: 44px;
            display: block;
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
                    <svg viewBox="0 0 48 48" aria-hidden="true">
                        <defs>
                            <linearGradient id="videoGradientSurface" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#ffd480" />
                                <stop offset="100%" stop-color="#ff4f7d" />
                            </linearGradient>
                            <linearGradient id="videoGradientBubble" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#fff7eb" />
                                <stop offset="100%" stop-color="#ffd4c2" />
                            </linearGradient>
                            <linearGradient id="videoGradientCamera" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#4b7bff" />
                                <stop offset="100%" stop-color="#1a2791" />
                            </linearGradient>
                        </defs>
                        <path d="M7.5 14.5h25c4.4 0 8 3.5 8 8s-3.6 8-8 8H29l-5 4.8c-.9.8-2.2.1-2.2-1v-3.8H7.5c-4.4 0-8-3.5-8-8s3.6-8 8-8z" fill="url(#videoGradientSurface)" stroke="#c43d40" stroke-width="1.4" stroke-linejoin="round"></path>
                        <rect x="10.5" y="17" width="21" height="12.5" rx="4.5" fill="url(#videoGradientBubble)" stroke="rgba(255,255,255,0.45)" stroke-width="1"></rect>
                        <path d="M21.5 20.5l5.5 3.5-5.5 3.5z" fill="#ff5c7a"></path>
                        <circle cx="16.5" cy="23.5" r="2" fill="#ffd689"></circle>
                        <circle cx="19.6" cy="23.5" r="1" fill="#ff9674"></circle>
                        <path d="M36.5 19l7 4v6l-7 4v-14z" fill="url(#videoGradientCamera)" stroke="#1a2791" stroke-width="1.2" stroke-linejoin="round"></path>
                        <circle cx="40" cy="26" r="1.2" fill="#a5c7ff"></circle>
                        <rect x="17.7" y="34" width="9" height="2.8" rx="1.4" fill="#c43d40" opacity="0.35"></rect>
                    </svg>
                </div>
                <div class="feature-label">VIDEO CONSULT</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon">
                    <svg viewBox="0 0 48 48" aria-hidden="true">
                        <defs>
                            <linearGradient id="bookingGradientBase" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#7fd4ff" />
                                <stop offset="100%" stop-color="#2f4bff" />
                            </linearGradient>
                            <linearGradient id="bookingGradientHeader" x1="0" y1="0" x2="1" y2="0">
                                <stop offset="0%" stop-color="#9ed0ff" />
                                <stop offset="100%" stop-color="#4d7bff" />
                            </linearGradient>
                            <linearGradient id="bookingGradientAccent" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#ffffff" />
                                <stop offset="100%" stop-color="#dfe9ff" />
                            </linearGradient>
                        </defs>
                        <rect x="11" y="10" width="26" height="28" rx="8" fill="url(#bookingGradientBase)" stroke="#0f1b6d" stroke-width="1.4"></rect>
                        <path d="M11 15h26" stroke="#0f1b6d" stroke-width="1.4"></path>
                        <rect x="14" y="7" width="4" height="8" rx="2" fill="#0f1b6d"></rect>
                        <rect x="30" y="7" width="4" height="8" rx="2" fill="#0f1b6d"></rect>
                        <rect x="13.5" y="16.5" width="21" height="17" rx="4" fill="url(#bookingGradientAccent)"></rect>
                        <path d="M17 21h14M17 25h14M17 29h8" stroke="#aab8e5" stroke-width="1.4" stroke-linecap="round"></path>
                        <rect x="20.5" y="26.5" width="4.5" height="4.5" rx="1.2" fill="#4d7bff" opacity="0.2"></rect>
                        <path d="M22 28.7l1.2 1.2 2.3-2.6" fill="none" stroke="#3062ff" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"></path>
                        <rect x="13.5" y="10" width="21" height="5" rx="2.5" fill="url(#bookingGradientHeader)" opacity="0.9"></rect>
                    </svg>
                </div>
                <div class="feature-label">APPOINTMENT BOOKING</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon">
                    <svg viewBox="0 0 48 48" aria-hidden="true">
                        <defs>
                            <linearGradient id="reportGradientPaper" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#f5feff" />
                                <stop offset="100%" stop-color="#dbefff" />
                            </linearGradient>
                            <linearGradient id="reportGradientAccent" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#5ff0d5" />
                                <stop offset="100%" stop-color="#2b8fff" />
                            </linearGradient>
                        </defs>
                        <path d="M16 8h14l8 8v24a4 4 0 0 1-4 4H16a4 4 0 0 1-4-4V12a4 4 0 0 1 4-4z" fill="url(#reportGradientPaper)" stroke="#0d4d72" stroke-width="1.4"></path>
                        <path d="M30 8v8h8" fill="#c0e4ff"></path>
                        <path d="M17 19h14M17 23h14" stroke="#a7badb" stroke-width="1.4" stroke-linecap="round"></path>
                        <rect x="19" y="26" width="10" height="10" rx="2" fill="url(#reportGradientAccent)" opacity="0.2"></rect>
                        <path d="M20 31l3 3 6-7" fill="none" stroke="url(#reportGradientAccent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M17 34h7" stroke="#a7badb" stroke-width="1.2" stroke-linecap="round"></path>
                        <circle cx="24" cy="13.5" r="1.8" fill="#7fd4ff"></circle>
                        <rect x="20" y="16" width="8" height="1.2" rx="0.6" fill="#bdd4f8"></rect>
                    </svg>
                </div>
                <div class="feature-label">DIGITAL REPORT</div>
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
