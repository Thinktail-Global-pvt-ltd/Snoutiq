import React, { useEffect, useState } from "react";
import { useParams, Link } from "react-router-dom";
import { Helmet, HelmetProvider } from "react-helmet-async";
import { Header } from "../newflow/Navbar";
import { Footer } from "../newflow/NewFooter";
import axiosClient from "../axios";

// ─────────────────────────────────────────────────────────────────────────────
// DynamicBlogPost — renders a CMS blog post fetched from /api/blog-posts/:slug
// Reuses the same visual style as GoldenRetrieverVaccinationScheduleIndia.jsx
// ─────────────────────────────────────────────────────────────────────────────

const DynamicBlogPost = () => {
  const { slug } = useParams();
  const [post, setPost] = useState(null);
  const [loading, setLoading] = useState(true);
  const [notFound, setNotFound] = useState(false);
  const [showBackToTop, setShowBackToTop] = useState(false);

  // ── Fetch post ──────────────────────────────────────────────────────────────
  useEffect(() => {
    setLoading(true);
    setNotFound(false);
    axiosClient
      .get(`/blog-posts/${slug}`)
      .then((res) => {
        if (res.data?.success && res.data?.data) {
          setPost(res.data.data);
        } else {
          setNotFound(true);
        }
      })
      .catch(() => setNotFound(true))
      .finally(() => setLoading(false));
  }, [slug]);

  // ── Scroll events ────────────────────────────────────────────────────────────
  useEffect(() => {
    const handleScroll = () => setShowBackToTop(window.pageYOffset > 300);
    window.addEventListener("scroll", handleScroll);
    return () => window.removeEventListener("scroll", handleScroll);
  }, []);

  const scrollToTop = () => window.scrollTo({ top: 0, behavior: "smooth" });

  // ── Loading skeleton ─────────────────────────────────────────────────────────
  if (loading) {
    return (
      <div className="font-sans bg-gradient-to-br from-indigo-50 to-purple-50 min-h-screen mt-20">
        <Header />
        <div className="max-w-4xl mx-auto px-8 py-16 flex flex-col gap-6 animate-pulse">
          <div className="h-8 bg-indigo-200 rounded w-3/4" />
          <div className="h-4 bg-purple-100 rounded w-full" />
          <div className="h-4 bg-purple-100 rounded w-5/6" />
          <div className="h-4 bg-purple-100 rounded w-4/6" />
          <div className="h-96 bg-indigo-100 rounded" />
        </div>
        <Footer />
      </div>
    );
  }

  // ── 404 ──────────────────────────────────────────────────────────────────────
  if (notFound || !post) {
    return (
      <div className="font-sans bg-gradient-to-br from-indigo-50 to-purple-50 min-h-screen mt-20">
        <Header />
        <div className="max-w-4xl mx-auto px-8 py-24 text-center">
          <div className="text-8xl mb-6">🐾</div>
          <h1 className="text-4xl font-black text-indigo-700 mb-4">Post Not Found</h1>
          <p className="text-lg text-gray-600 mb-8">
            The blog post you're looking for doesn't exist or has been removed.
          </p>
          <Link
            to="/blog"
            className="inline-block bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-8 py-3 rounded-lg font-bold hover:opacity-90 transition"
          >
            ← Back to Blog
          </Link>
        </div>
        <Footer />
      </div>
    );
  }

  // ── Helpers ───────────────────────────────────────────────────────────────────
  const siteBase = "https://snoutiq.com";
  const canonicalUrl = `${siteBase}/blog/${post.slug}`;
  const metaTitle = post.meta_title || post.title;
  const metaDesc = post.meta_description || post.excerpt || "";
  const ogImage = post.featured_image || `${siteBase}/favicon-512.png`;

  // ── Render ────────────────────────────────────────────────────────────────────
  return (
    <>
      <HelmetProvider>
        <Helmet>
          <title>{metaTitle}</title>
          <meta name="title" content={metaTitle} />
          <meta name="description" content={metaDesc} />
          <meta name="author" content="SnoutIQ" />
          <meta name="robots" content="index, follow" />
          <link rel="canonical" href={canonicalUrl} />

          {/* Open Graph */}
          <meta property="og:type" content="article" />
          <meta property="og:url" content={canonicalUrl} />
          <meta property="og:title" content={metaTitle} />
          <meta property="og:description" content={metaDesc} />
          <meta property="og:image" content={ogImage} />

          {/* Twitter */}
          <meta property="twitter:card" content="summary_large_image" />
          <meta property="twitter:url" content={canonicalUrl} />
          <meta property="twitter:title" content={metaTitle} />
          <meta property="twitter:description" content={metaDesc} />
          <meta property="twitter:image" content={ogImage} />

          {/* Article Schema */}
          <script type="application/ld+json">
            {JSON.stringify({
              "@context": "https://schema.org",
              "@type": "Article",
              headline: post.title,
              description: metaDesc,
              image: ogImage,
              author: { "@type": "Organization", name: "SnoutIQ" },
              publisher: {
                "@type": "Organization",
                name: "SnoutIQ",
                logo: { "@type": "ImageObject", url: `${siteBase}/favicon-512.png` },
              },
              datePublished: post.created_at,
              dateModified: post.updated_at,
              mainEntityOfPage: { "@type": "WebPage", "@id": canonicalUrl },
            })}
          </script>
        </Helmet>

      <Header />
      <div className="min-h-screen bg-gray-50 py-8 px-4 mt-10">
        <div className="max-w-4xl mx-auto">
          {/* Blog Header */}
          <header className="text-center mb-8">
            <h1 className="text-3xl font-bold text-gray-800 mb-4">
              {post.title}
            </h1>
            {post.excerpt && (
              <p className="text-gray-600 text-lg">
                {post.excerpt}
              </p>
            )}
            <div className="w-20 h-1 bg-blue-500 mx-auto mt-4"></div>
          </header>

          {/* Featured Image */}
          {post.featured_image && (
            <section className="mb-8">
              <img
                src={post.featured_image}
                alt={post.title}
                className="w-full h-auto rounded-lg shadow-sm"
                onError={(e) => { e.target.style.display = "none"; }}
              />
            </section>
          )}

          {/* Article Content */}
          <section className="bg-white rounded-lg shadow-sm p-6 mb-8">
            {post.content ? (
              <div
                className="ql-editor-content prose prose-indigo max-w-none"
                dangerouslySetInnerHTML={{ __html: post.content }}
              />
            ) : (
              <p className="text-gray-500 italic text-center py-12">
                No content available for this post.
              </p>
            )}
          </section>
        </div>
      </div>
      <Footer />
      </HelmetProvider>

      {/* Quill rendered content styles */}
      <style>{`
        .ql-editor-content h1 { font-size: 2rem; font-weight: 800; color: #4338ca; margin-bottom: 1rem; }
        .ql-editor-content h2 { font-size: 1.6rem; font-weight: 700; color: #4f46e5; margin-bottom: 0.75rem; margin-top: 2rem; padding-bottom: 0.5rem; border-bottom: 2px solid #a5b4fc; }
        .ql-editor-content h3 { font-size: 1.3rem; font-weight: 700; color: #1e1b4b; margin-bottom: 0.5rem; margin-top: 1.5rem; }
        .ql-editor-content p { font-size: 1.1rem; line-height: 1.8; margin-bottom: 1rem; color: #374151; }
        .ql-editor-content ul, .ql-editor-content ol { padding-left: 1.5rem; margin-bottom: 1rem; }
        .ql-editor-content li { font-size: 1.05rem; line-height: 1.75; margin-bottom: 0.35rem; color: #374151; }
        .ql-editor-content blockquote { border-left: 4px solid #6366f1; background: #eef2ff; padding: 1rem 1.25rem; margin: 1.5rem 0; border-radius: 0 0.5rem 0.5rem 0; font-style: italic; color: #3730a3; }
        .ql-editor-content table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.07); border-radius: 0.75rem; overflow: hidden; }
        .ql-editor-content th { background: linear-gradient(to right, #4f46e5, #7c3aed); color: white; padding: 0.75rem 1rem; text-align: left; font-weight: 600; }
        .ql-editor-content td { padding: 0.65rem 1rem; border-bottom: 1px solid #e5e7eb; color: #374151; }
        .ql-editor-content tr:nth-child(even) { background: #eef2ff; }
        .ql-editor-content tr:hover { background: #e0e7ff; }
        .ql-editor-content img { max-width: 100%; border-radius: 0.75rem; margin: 1rem 0; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .ql-editor-content a { color: #4f46e5; text-decoration: underline; }
        .ql-editor-content strong { font-weight: 700; color: #1e1b4b; }
        .ql-editor-content code { background: #f1f5f9; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.9em; color: #7c3aed; }
        .ql-editor-content pre { background: #1e1b4b; color: #e2e8f0; padding: 1rem 1.25rem; border-radius: 0.5rem; overflow-x: auto; margin: 1rem 0; }
      `}</style>
    </>
  );
};

export default DynamicBlogPost;
