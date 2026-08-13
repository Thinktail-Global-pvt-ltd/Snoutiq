import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { DOMAIN, PUBLIC_ROUTES } from './routes.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const TODAY = new Date().toISOString();

async function fetchDynamicBlogPosts() {
  const dynamicSlugs = new Set();
  try {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 3000);
    const res = await fetch(`${DOMAIN}/backend/api/blog-posts`, {
      signal: controller.signal,
      headers: { Accept: 'application/json' },
    });
    clearTimeout(timeout);
    if (res.ok) {
      const data = await res.json();
      const posts = data?.data || data?.posts || (Array.isArray(data) ? data : []);
      posts.forEach((post) => {
        if (post?.slug) {
          dynamicSlugs.add(`/blog/${post.slug}`);
        }
      });
    }
  } catch (err) {
    // Graceful fallback if backend is unreachable during build
  }
  return Array.from(dynamicSlugs);
}

async function generateSitemap() {
  console.log('🔄 Generating sitemap.xml...');
  const dynamicBlogPaths = await fetchDynamicBlogPosts();

  const allRoutes = [...PUBLIC_ROUTES];
  const existingPaths = new Set(PUBLIC_ROUTES.map((r) => r.path));

  dynamicBlogPaths.forEach((path) => {
    if (!existingPaths.has(path)) {
      allRoutes.push({ path, priority: '0.6' });
    }
  });

  const xmlContent = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${allRoutes
  .map(
    (r) => `  <url>
    <loc>${DOMAIN}${r.path}</loc>
    <lastmod>${TODAY}</lastmod>
    <priority>${r.priority}</priority>
  </url>`
  )
  .join('\n')}
</urlset>
`;

  const outputPath = path.resolve(__dirname, '../public/sitemap.xml');
  fs.writeFileSync(outputPath, xmlContent, 'utf-8');
  console.log(`✅ sitemap.xml generated with ${allRoutes.length} URLs at ${outputPath}`);
}

generateSitemap();
