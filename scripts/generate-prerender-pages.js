import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { DOMAIN, PUBLIC_ROUTES } from './routes.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const distDir = path.resolve(__dirname, '../dist');
const baseIndexPath = path.join(distDir, 'index.html');

if (!fs.existsSync(baseIndexPath)) {
  console.error('❌ dist/index.html not found. Run vite build first.');
  process.exit(1);
}

const templateHtml = fs.readFileSync(baseIndexPath, 'utf-8');

console.log('🔄 Prerendering static HTML routes for SEO crawlers...');

let createdCount = 0;

PUBLIC_ROUTES.forEach(({ path: routePath }) => {
  const cleanPath =
    routePath.length > 1 && routePath.endsWith('/')
      ? routePath.slice(0, -1)
      : routePath;
  const canonicalUrl = `${DOMAIN}${cleanPath}`;

  // Insert canonical link and og:url / twitter:url into <head>
  const canonicalTag = `<link rel="canonical" href="${canonicalUrl}" />`;
  const ogUrlTag = `<meta property="og:url" content="${canonicalUrl}" />`;
  const twitterUrlTag = `<meta property="twitter:url" content="${canonicalUrl}" />`;

  let pageHtml = templateHtml;

  // If head contains existing canonical/og:url, clean it up first
  pageHtml = pageHtml.replace(/<link\s+rel=["']canonical["'][^>]*>/gi, '');
  pageHtml = pageHtml.replace(/<meta\s+property=["']og:url["'][^>]*>/gi, '');
  pageHtml = pageHtml.replace(/<meta\s+property=["']twitter:url["'][^>]*>/gi, '');

  const injectTags = `\n    ${canonicalTag}\n    ${ogUrlTag}\n    ${twitterUrlTag}\n  `;
  pageHtml = pageHtml.replace('</head>', `${injectTags}</head>`);

  if (routePath === '/') {
    fs.writeFileSync(baseIndexPath, pageHtml, 'utf-8');
    createdCount++;
  } else {
    // e.g. /delhi -> dist/delhi/index.html
    const targetDir = path.join(distDir, cleanPath);
    fs.mkdirSync(targetDir, { recursive: true });
    const targetFilePath = path.join(targetDir, 'index.html');
    fs.writeFileSync(targetFilePath, pageHtml, 'utf-8');
    createdCount++;
  }
});

console.log(`✅ Successfully prerendered ${createdCount} static route HTML files in dist/!`);
