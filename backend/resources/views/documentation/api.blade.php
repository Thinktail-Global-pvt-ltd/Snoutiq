<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Snoutiq Command Center · API Documentation</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            --bg: #0b1120;
            --card: #ffffff;
            --muted: #64748b;
            --divider: #e2e8f0;
            --accent: #2563eb;
            --accent-soft: rgba(37, 99, 235, 0.08);
            --code-bg: #0f172a;
            --code-text: #e2e8f0;
            --table-header: #f8fafc;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: radial-gradient(circle at top, rgba(14, 165, 233, 0.22), transparent 55%),
                        radial-gradient(circle at 20% 20%, rgba(37, 99, 235, 0.3), transparent 40%),
                        var(--bg);
            color: #0f172a;
            padding: 2.5rem 1rem 3rem;
        }

        main {
            max-width: 1100px;
            margin: 0 auto;
            background: var(--card);
            border-radius: 28px;
            padding: 2.5rem;
            box-shadow:
                0 40px 80px rgba(15, 23, 42, 0.18),
                0 0 0 1px rgba(15, 23, 42, 0.04);
        }

        @media (max-width: 768px) {
            main {
                padding: 1.5rem;
                border-radius: 18px;
            }
        }

        .page-header {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            justify-content: space-between;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            margin: 0.2rem 0 0.6rem;
            font-size: clamp(2rem, 4vw, 2.75rem);
        }

        .subtitle {
            color: var(--muted);
            margin: 0;
            font-size: 1.05rem;
        }

        .metadata {
            text-align: right;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .metadata time {
            font-weight: 600;
            color: #0f172a;
        }

        .doc-source {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.75rem;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .doc-content {
            line-height: 1.7;
            color: #1e293b;
        }

        .doc-content h1,
        .doc-content h2,
        .doc-content h3,
        .doc-content h4 {
            color: #0f172a;
            margin-top: 2.1rem;
        }

        .doc-content h2 {
            padding-top: 1.5rem;
            border-top: 1px solid var(--divider);
        }

        .doc-content h3 {
            margin-top: 1.4rem;
        }

        .doc-content p {
            margin: 0.9rem 0;
        }

        .doc-content ul,
        .doc-content ol {
            padding-left: 1.25rem;
        }

        .doc-content li {
            margin: 0.35rem 0;
        }

        .doc-content blockquote {
            margin: 1rem 0;
            padding: 1rem 1.25rem;
            border-left: 4px solid var(--accent);
            background: #f8fafc;
            border-radius: 0 16px 16px 0;
        }

        .doc-content code {
            font-family: 'JetBrains Mono', 'SFMono-Regular', Menlo, monospace;
            background: rgba(15, 23, 42, 0.08);
            padding: 0.15rem 0.4rem;
            border-radius: 6px;
            font-size: 0.92rem;
        }

        .doc-content pre {
            background: var(--code-bg);
            color: var(--code-text);
            padding: 1.2rem 1.4rem;
            border-radius: 16px;
            overflow-x: auto;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.05);
        }

        .doc-content pre code {
            background: transparent;
            padding: 0;
            font-size: 0.9rem;
            color: inherit;
        }

        .doc-content table {
            border-collapse: collapse;
            width: 100%;
            margin: 1.2rem 0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 0 0 1px var(--divider);
        }

        .doc-content table thead {
            background: var(--table-header);
        }

        .doc-content th,
        .doc-content td {
            padding: 0.85rem;
            border-bottom: 1px solid var(--divider);
            text-align: left;
            font-size: 0.95rem;
        }

        .doc-content tr:last-child td {
            border-bottom: none;
        }

        .doc-content a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }

        .doc-content a:hover {
            text-decoration: underline;
        }

        .toc-note {
            margin-bottom: 2rem;
            padding: 1.25rem 1.5rem;
            border-radius: 18px;
            background: linear-gradient(120deg, rgba(37, 99, 235, 0.08), rgba(14, 165, 233, 0.08));
            border: 1px solid rgba(37, 99, 235, 0.2);
            color: #0f172a;
        }

        .toc-note strong {
            display: block;
            font-size: 0.95rem;
            letter-spacing: 0.02em;
            color: var(--accent);
            margin-bottom: 0.35rem;
            text-transform: uppercase;
        }

        .toc-note p {
            margin: 0;
        }

        .floating-top {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #0f172a;
            color: #f8fafc;
            border: none;
            border-radius: 999px;
            padding: 0.8rem 1.4rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.35);
            opacity: 0;
            transform: translateY(10px);
            pointer-events: none;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .floating-top.visible {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
    </style>
</head>

<body>
    <main>
        <div class="page-header">
            <div>
                <div class="doc-source">
                    Snoutiq Command Center · API Spec
                </div>
                <h1>API Documentation</h1>
                <p class="subtitle">
                    Complete specification rendered directly from <code>{{ $docSourcePath }}</code>.
                </p>
            </div>
            <div class="metadata">
                <div>Last synced:</div>
                <time datetime="{{ $lastUpdatedIso }}">{{ $lastUpdatedHuman }}</time>
            </div>
        </div>

        <div class="toc-note">
            <strong>Heads up</strong>
            <p>
                This view mirrors the markdown document verbatim, so every requirement, field description,
                and test note in the spec is visible below. Update the markdown file to refresh the content here.
            </p>
        </div>

        <article class="doc-content">
            {!! $documentationHtml !!}
        </article>
    </main>

    <button class="floating-top" type="button" aria-label="Back to top">Back to top</button>

    <script>
        (() => {
            const headingSelector = '.doc-content h1, .doc-content h2, .doc-content h3, .doc-content h4';

            const slugify = (value) => value
                .toLowerCase()
                .trim()
                .replace(/[\u2000-\u206F\u2E00-\u2E7F'!"#$%&()*+,./:;<=>?@[\]^`{|}~]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');

            document.querySelectorAll(headingSelector).forEach((heading) => {
                if (!heading.textContent.trim()) {
                    return;
                }
                if (!heading.id) {
                    heading.id = slugify(heading.textContent);
                }
            });

            document.querySelectorAll('.doc-content a[href^="#"]').forEach((anchor) => {
                anchor.addEventListener('click', (event) => {
                    const targetId = anchor.getAttribute('href').slice(1);
                    const target = document.getElementById(targetId);
                    if (target) {
                        event.preventDefault();
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        history.replaceState(null, '', `#${targetId}`);
                    }
                });
            });

            const backToTopBtn = document.querySelector('.floating-top');
            backToTopBtn.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
            document.addEventListener('scroll', () => {
                if (window.scrollY > 600) {
                    backToTopBtn.classList.add('visible');
                } else {
                    backToTopBtn.classList.remove('visible');
                }
            });
        })();
    </script>
</body>

</html>
