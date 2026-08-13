import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const DOMAIN = 'https://snoutiq.com';
const TODAY = new Date().toISOString();

const STATIC_ROUTES = [
  // Primary Pages
  { path: '/', priority: '1.0' },
  { path: '/delhi', priority: '0.8' },
  { path: '/gurugram', priority: '0.8' },
  { path: '/clinics-solution', priority: '0.8' },
  { path: '/find-vets-near-you', priority: '0.8' },
  { path: '/veterinary-doctor-online-india', priority: '0.8' },
  { path: '/online-vet-consultation', priority: '0.8' },
  { path: '/talk-to-vet-online', priority: '0.8' },
  { path: '/pet-doctor-online', priority: '0.8' },
  { path: '/vet-consultation', priority: '0.8' },
  { path: '/ask', priority: '0.8' },

  // Secondary Pages
  { path: '/about', priority: '0.7' },
  { path: '/parents', priority: '0.7' },
  { path: '/clinics', priority: '0.7' },
  { path: '/vets', priority: '0.7' },
  { path: '/puppy-vaccination-delhi', priority: '0.7' },
  { path: '/kitten-vaccination-delhi', priority: '0.7' },
  { path: '/dog-neutering-delhi', priority: '0.7' },
  { path: '/cat-neutering-delhi', priority: '0.7' },
  { path: '/lp/vaccination', priority: '0.7' },
  { path: '/lp/neutering', priority: '0.7' },
  { path: '/vet-insights', priority: '0.7' },
  { path: '/vet-insights/interview-dr-sharma-emergency-care', priority: '0.7' },
  { path: '/dog-vomiting-treatment-india', priority: '0.7' },
  { path: '/veterinary-practice-software', priority: '0.7' },
  
  // Blog Listing
  { path: '/blog/', priority: '0.7' },

  // Blog Static Posts
  { path: '/blog/how-vets-grow-with-online-consultations', priority: '0.6' },
  { path: '/blog/online-vet-consultation', priority: '0.6' },
  { path: '/blog/register-as-an-online-vet', priority: '0.6' },
  { path: '/blog/dog-winter-care-guide', priority: '0.6' },
  { path: '/blog/symptoms-of-tick-fever-in-dogs', priority: '0.6' },
  { path: '/blog/Vets-Increase-Monthly-Revenue', priority: '0.6' },
  { path: '/blog/protecting-pet-paws-in-winter-tips-guide', priority: '0.6' },
  { path: '/blog/first-aid-tips-every-pet-parent-should-know', priority: '0.6' },
  { path: '/blog/vaccination-schedule-for-pets-in-india', priority: '0.6' },
  { path: '/blog/best-food-for-dogs-in-winter', priority: '0.6' },
  { path: '/blog/boost-your-dogs-immunity-naturally', priority: '0.6' },
  { path: '/blog/top-friendly-dog-breeds-in-india', priority: '0.6' },
  { path: '/blog/best-cat-breeds-in-india', priority: '0.6' },
  { path: '/blog/cat-vaccination-schedule-india', priority: '0.6' },
  { path: '/blog/cats-diseases-and-symptoms', priority: '0.6' },
  { path: '/blog/best-cat-food-in-india', priority: '0.6' },
  { path: '/blog/foods-golden-retrievers-should-never-eat', priority: '0.6' },
  { path: '/blog/best-dog-food-for-golden-retrievers', priority: '0.6' },
  { path: '/blog/golden-retriever-vaccination-schedule-india', priority: '0.6' },
  { path: '/blog/why-winter-grooming-is-important-for-cats', priority: '0.6' },

  // Legal Pages
  { path: '/privacy-policy', priority: '0.4' },
  { path: '/terms-of-service', priority: '0.4' },
  { path: '/cancellation-policy', priority: '0.4' },
  { path: '/cookie-policy', priority: '0.4' },
  { path: '/medical-data-consent', priority: '0.4' },
  { path: '/shipping-policy', priority: '0.4' },
];

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

  const allRoutes = [...STATIC_ROUTES];
  const existingPaths = new Set(STATIC_ROUTES.map((r) => r.path));

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
