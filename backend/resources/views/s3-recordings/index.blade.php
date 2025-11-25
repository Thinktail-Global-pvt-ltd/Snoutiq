<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>S3 Recording Library</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      color-scheme: light dark;
    }
    * {
      box-sizing: border-box;
    }
    body {
      margin: 0;
      font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background: #f4f4f7;
      color: #111827;
    }
    .page {
      min-height: 100vh;
      padding: 32px 16px 48px;
      max-width: 1200px;
      margin: 0 auto;
    }
    .hero {
      margin-bottom: 24px;
    }
    .hero h1 {
      margin: 0;
      font-size: 28px;
      font-weight: 600;
    }
    .hero p {
      margin: 8px 0 0;
      color: #475569;
    }
    .badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 4px 12px;
      border-radius: 999px;
      background: #e0e7ff;
      color: #3730a3;
      font-size: 12px;
      font-weight: 600;
      letter-spacing: .3px;
    }
    .table-card {
      background: #fff;
      border-radius: 20px;
      padding: 20px;
      box-shadow: 0 20px 60px rgba(15,23,42,0.08);
    }
    .table-card small {
      color: #64748b;
    }
    .table-wrapper {
      overflow-x: auto;
      margin-top: 16px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
    }
    thead {
      text-transform: uppercase;
      font-size: 12px;
      letter-spacing: 0.08em;
      color: #475569;
    }
    th, td {
      text-align: left;
      padding: 12px 10px;
      border-bottom: 1px solid #eef2ff;
      vertical-align: top;
    }
    tbody tr:last-child td {
      border-bottom: none;
    }
    .file-name {
      font-weight: 600;
      display: block;
      color: #111827;
    }
    .file-path {
      font-size: 12px;
      color: #94a3b8;
      margin-top: 4px;
      display: block;
    }
    .link-cell {
      display: flex;
      gap: 8px;
      align-items: center;
      flex-wrap: wrap;
    }
    .link-cell a,
    .link-cell button {
      border: none;
      border-radius: 999px;
      padding: 6px 14px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .link-cell a {
      background: #2563eb;
      color: #fff;
      text-decoration: none;
    }
    .link-cell button {
      background: #e2e8f0;
      color: #0f172a;
    }
    .link-cell button:disabled {
      opacity: 0.6;
      cursor: default;
    }
    .ghost {
      color: #94a3b8;
    }
    .empty-state {
      padding: 32px;
      text-align: center;
      color: #94a3b8;
    }
    @media (max-width: 640px) {
      th, td {
        padding: 10px 6px;
      }
    }
  </style>
</head>
<body>
<div class="page">
  <header class="hero">
    <div class="badge">AWS S3 bucket</div>
    <h1>S3 recordings list <small>({{ $bucket }})</small></h1>
    <p>All files uploaded through the <code>/api/call-recordings/upload</code> endpoint appear here with a shareable public link.</p>
  </header>

  @php
    $formatSize = function (?int $bytes) {
        if ($bytes === null) {
            return '—';
        }
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return number_format($bytes / (1024 * 1024), 2) . ' MB';
    };
  @endphp

  <section class="table-card">
    <div class="table-wrapper">
      <table>
        <thead>
        <tr>
          <th>File</th>
          <th>Last modified</th>
          <th>Size</th>
          <th>Public link</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($files as $file)
          <tr>
            <td>
              <span class="file-name">{{ $file['name'] }}</span>
              <span class="file-path">{{ $file['path'] }}</span>
            </td>
            <td>
              @if ($file['last_modified'])
                {{ $file['last_modified']->timezone('Asia/Kolkata')->format('d M Y H:i:s') }}
              @else
                <span class="ghost">Unknown</span>
              @endif
            </td>
            <td>
              {{ $formatSize($file['size']) }}
            </td>
            <td class="link-cell">
              <a href="{{ $file['url'] }}" target="_blank" rel="noreferrer">Open</a>
              <button type="button" data-url="{{ $file['url'] }}" onclick="copyLink(this)">Copy URL</button>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="empty-state">
              No recordings found yet. Upload a file through the API to populate this list.
            </td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </section>
</div>

<script>
  function copyLink(button) {
    const url = button?.dataset?.url;
    if (!url) return;
    navigator.clipboard?.writeText(url).then(() => {
      const original = button.textContent;
      button.textContent = 'Copied!';
      button.disabled = true;
      setTimeout(() => {
        button.textContent = original;
        button.disabled = false;
      }, 1200);
    }).catch(() => {
      alert('Unable to copy automatically; please copy the link manually.');
    });
  }
</script>
</body>
</html>
