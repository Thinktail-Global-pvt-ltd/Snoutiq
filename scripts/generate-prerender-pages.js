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

console.log('🔄 Prerendering static HTML routes with titles and meta descriptions...');

let createdCount = 0;

PUBLIC_ROUTES.forEach(({ path: routePath, title, description }) => {
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

  // Clean up existing tags
  pageHtml = pageHtml.replace(/<link\s+rel=["']canonical["'][^>]*>/gi, '');
  pageHtml = pageHtml.replace(/<meta\s+property=["']og:url["'][^>]*>/gi, '');
  pageHtml = pageHtml.replace(/<meta\s+property=["']twitter:url["'][^>]*>/gi, '');

  if (title) {
    pageHtml = pageHtml.replace(/<title>[^<]*<\/title>/gi, `<title>${title}</title>`);
    pageHtml = pageHtml.replace(/<meta\s+name=["']title["'][^>]*>/gi, `<meta name="title" content="${title}" />`);
    pageHtml = pageHtml.replace(/<meta\s+property=["']og:title["'][^>]*>/gi, `<meta property="og:title" content="${title}" />`);
  }

  if (description) {
    pageHtml = pageHtml.replace(/<meta\s+name=["']description["'][^>]*>/gi, `<meta name="description" content="${description}" />`);
    pageHtml = pageHtml.replace(/<meta\s+property=["']og:description["'][^>]*>/gi, `<meta property="og:description" content="${description}" />`);
  }

  const injectTags = `\n    ${canonicalTag}\n    ${ogUrlTag}\n    ${twitterUrlTag}\n  `;
  pageHtml = pageHtml.replace('</head>', `${injectTags}</head>`);

  if (routePath === '/') {
    fs.writeFileSync(baseIndexPath, pageHtml, 'utf-8');
    createdCount++;
  } else {
    // Relative folder under distDir: e.g. /delhi -> delhi
    const relativeSubDir = cleanPath.startsWith('/') ? cleanPath.slice(1) : cleanPath;
    const targetDir = path.join(distDir, relativeSubDir);
    fs.mkdirSync(targetDir, { recursive: true });
    const targetFilePath = path.join(targetDir, 'index.html');
    fs.writeFileSync(targetFilePath, pageHtml, 'utf-8');
    createdCount++;
  }
});

console.log(`✅ Successfully prerendered ${createdCount} static route HTML files in dist/!`);
