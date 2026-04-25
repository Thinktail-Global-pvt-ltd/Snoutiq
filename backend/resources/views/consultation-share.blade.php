<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Consultation</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f6f8fb;
            --card: #ffffff;
            --ink: #132238;
            --muted: #5b6b80;
            --line: #d7e0ea;
            --accent: #179c52;
            --accent-dark: #0e7d40;
            --warn-bg: #fff5e8;
            --warn-ink: #8a4b08;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, #dff7e8, transparent 34%),
                radial-gradient(circle at bottom right, #e9f1ff, transparent 40%),
                var(--bg);
            color: var(--ink);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            width: min(100%, 460px);
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 28px;
            box-shadow: 0 20px 55px rgba(19, 34, 56, 0.08);
            overflow: hidden;
        }

        .hero {
            padding: 28px 28px 22px;
            background: linear-gradient(135deg, #0f8c49, #35bf71);
            color: #fff;
        }

        .hero p {
            margin: 0 0 8px;
            font-size: 13px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            opacity: 0.82;
        }

        .hero h1 {
            margin: 0;
            font-size: 28px;
            line-height: 1.15;
        }

        .body {
            padding: 24px 28px 28px;
        }

        .copy {
            margin: 0 0 20px;
            color: var(--muted);
            font-size: 15px;
            line-height: 1.6;
        }

        .cta {
            display: inline-flex;
            width: 100%;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 18px;
            padding: 16px 20px;
            background: var(--accent);
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            font-size: 16px;
            box-shadow: 0 14px 28px rgba(23, 156, 82, 0.22);
        }

        .cta:hover {
            background: var(--accent-dark);
        }

        .warning {
            margin-top: 16px;
            border-radius: 16px;
            padding: 14px 16px;
            background: var(--warn-bg);
            color: var(--warn-ink);
            font-size: 14px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <main class="card">
        <section class="hero">
            <p>Snoutiq Consultation</p>
            <h1>Start consultation with {{ $doctorName }}</h1>
        </section>

        <section class="body">
            <p class="copy">
                Tap the button below and send the prefilled WhatsApp message. Once your message is received,
                your payment link will be sent automatically in the same 24-hour WhatsApp window.
            </p>

            @if ($openWhatsAppUrl)
                <a
                    class="cta"
                    href="{{ $openWhatsAppUrl }}"
                    id="open-whatsapp-link"
                    data-web-url="{{ $openWhatsAppUrl }}"
                >
                    Open WhatsApp
                </a>
            @else
                <div class="warning">
                    WhatsApp start is not configured for this environment yet. Set
                    <strong>WHATSAPP_BUSINESS_PHONE</strong> so this button can open the correct business chat.
                </div>
            @endif
        </section>
    </main>
    <script>
        (function () {
            const link = document.getElementById('open-whatsapp-link');
            if (!link) {
                return;
            }

            const userAgent = navigator.userAgent || '';
            const isMobile = /android|iphone|ipad|ipod|mobile/i.test(userAgent);
            if (!isMobile) {
                return;
            }

            const webUrl = String(link.dataset.webUrl || '').trim();
            if (!webUrl) {
                return;
            }

            const buildTargets = () => {
                try {
                    const parsed = new URL(webUrl);
                    const phone = /(^|\.)wa\.me$/i.test(parsed.hostname)
                        ? (parsed.pathname || '').replace(/^\/+/, '').split('/')[0] || ''
                        : String(parsed.searchParams.get('phone') || '').trim();
                    const text = String(parsed.searchParams.get('text') || '').trim();

                    if (!phone && !text) {
                        return null;
                    }

                    const params = new URLSearchParams();
                    if (phone) params.set('phone', phone);
                    if (text) params.set('text', text);

                    return {
                        appUrl: `whatsapp://send?${params.toString()}`,
                        webUrl,
                    };
                } catch {
                    return null;
                }
            };

            const targets = buildTargets();
            if (!targets?.appUrl) {
                return;
            }

            const launchWhatsApp = () => {
                const storageKey = `consult-whatsapp-launch:${window.location.pathname}`;

                try {
                    if (window.sessionStorage.getItem(storageKey) === '1') {
                        return;
                    }
                    window.sessionStorage.setItem(storageKey, '1');
                } catch {}

                let fallbackTimerId = null;

                const cleanup = () => {
                    if (fallbackTimerId !== null) {
                        window.clearTimeout(fallbackTimerId);
                        fallbackTimerId = null;
                    }

                    document.removeEventListener('visibilitychange', handleVisibilityChange);
                    window.removeEventListener('pagehide', cleanup);
                    window.removeEventListener('blur', cleanup);
                };

                const handleVisibilityChange = () => {
                    if (document.visibilityState === 'hidden') {
                        cleanup();
                    }
                };

                document.addEventListener('visibilitychange', handleVisibilityChange);
                window.addEventListener('pagehide', cleanup, { once: true });
                window.addEventListener('blur', cleanup, { once: true });

                fallbackTimerId = window.setTimeout(() => {
                    if (document.visibilityState === 'visible') {
                        try {
                            window.sessionStorage.removeItem(storageKey);
                        } catch {}
                    }
                    cleanup();
                }, 900);

                window.location.assign(targets.appUrl);
            };

            link.addEventListener('click', function (event) {
                event.preventDefault();
                launchWhatsApp();
            });

            window.setTimeout(launchWhatsApp, 60);
        })();
    </script>
</body>
</html>
