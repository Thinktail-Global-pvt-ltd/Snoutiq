import React, { useEffect, useState, useCallback, lazy, Suspense } from "react";
import { Link } from "react-router-dom";
import axiosClient from "../axios";
import "react-quill/dist/quill.snow.css";

// Lazy-load ReactQuill to avoid SSR issues and reduce initial bundle
const ReactQuill = lazy(() => import("react-quill"));

// ─────────────────────────────────────────────────────────────────────────────
// AdminBlogManager — Blog CMS page for /admin/blog
// Requires admin session (EnsureAdminAuthenticated on backend)
// ─────────────────────────────────────────────────────────────────────────────

const API_ADMIN = "/admin/blog-posts";

const QUILL_MODULES = {
  toolbar: [
    [{ header: [1, 2, 3, 4, false] }],
    ["bold", "italic", "underline", "strike"],
    [{ color: [] }, { background: [] }],
    [{ list: "ordered" }, { list: "bullet" }],
    [{ indent: "-1" }, { indent: "+1" }],
    ["blockquote", "code-block"],
    ["link", "image"],
    ["clean"],
  ],
};

const QUILL_FORMATS = [
  "header", "bold", "italic", "underline", "strike",
  "color", "background", "list", "bullet", "indent",
  "blockquote", "code-block", "link", "image",
];

// ── Slug generator ────────────────────────────────────────────────────────────
function toSlug(str) {
  return str
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9\s-]/g, "")
    .replace(/\s+/g, "-")
    .replace(/-+/g, "-");
}

// ── Empty form state ─────────────────────────────────────────────────────────
const EMPTY_FORM = {
  id: null,
  title: "",
  slug: "",
  content: "",
  excerpt: "",
  meta_title: "",
  meta_description: "",
  featured_image: "",
  status: "draft",
};

// ─────────────────────────────────────────────────────────────────────────────
export default function AdminBlogManager() {
  const [posts, setPosts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [deleting, setDeleting] = useState(null); // post id being deleted
  const [view, setView] = useState("list"); // "list" | "editor"
  const [form, setForm] = useState(EMPTY_FORM);
  const [toast, setToast] = useState(null); // { type: "success"|"error", msg }
  const [deleteConfirm, setDeleteConfirm] = useState(null); // post to delete
  const [slugEdited, setSlugEdited] = useState(false); // user manually edited slug

  // ── Toast helpers ─────────────────────────────────────────────────────────
  const showToast = useCallback((type, msg) => {
    setToast({ type, msg });
    setTimeout(() => setToast(null), 4000);
  }, []);

  // ── Fetch all posts ───────────────────────────────────────────────────────
  const fetchPosts = useCallback(() => {
    setLoading(true);
    axiosClient
      .get(API_ADMIN)
      .then((res) => setPosts(res.data?.data || []))
      .catch(() => showToast("error", "Failed to load posts. Check admin auth."))
      .finally(() => setLoading(false));
  }, [showToast]);

  useEffect(() => {
    fetchPosts();
  }, [fetchPosts]);

  // ── Form field change ─────────────────────────────────────────────────────
  const handleChange = (field, value) => {
    setForm((prev) => {
      const next = { ...prev, [field]: value };
      // Auto-generate slug from title unless user manually edited it
      if (field === "title" && !slugEdited) {
        next.slug = toSlug(value);
      }
      return next;
    });
  };

  const handleSlugChange = (value) => {
    setSlugEdited(true);
    setForm((prev) => ({ ...prev, slug: toSlug(value) }));
  };

  // ── Open editor (new post) ────────────────────────────────────────────────
  const openNew = () => {
    setForm(EMPTY_FORM);
    setSlugEdited(false);
    setView("editor");
  };

  // ── Open editor (edit existing) ───────────────────────────────────────────
  const openEdit = (post) => {
    setForm({
      id: post.id,
      title: post.title || "",
      slug: post.slug || "",
      content: post.content || "",
      excerpt: post.excerpt || "",
      meta_title: post.meta_title || "",
      meta_description: post.meta_description || "",
      featured_image: post.featured_image || "",
      status: post.status || "draft",
    });
    setSlugEdited(true);
    setView("editor");
  };

  // ── Save (create or update) ───────────────────────────────────────────────
  const handleSave = async () => {
    if (!form.title.trim()) {
      showToast("error", "Title is required.");
      return;
    }
    setSaving(true);
    try {
      if (form.id) {
        await axiosClient.put(`${API_ADMIN}/${form.id}`, form);
        showToast("success", "Post updated successfully!");
      } else {
        await axiosClient.post(API_ADMIN, form);
        showToast("success", "Post created successfully!");
      }
      fetchPosts();
      setView("list");
    } catch (err) {
      const msg =
        err?.response?.data?.message ||
        Object.values(err?.response?.data?.errors || {}).flat().join(" ") ||
        "Failed to save post.";
      showToast("error", msg);
    } finally {
      setSaving(false);
    }
  };

  // ── Delete ────────────────────────────────────────────────────────────────
  const handleDelete = async (post) => {
    setDeleting(post.id);
    try {
      await axiosClient.delete(`${API_ADMIN}/${post.id}`);
      showToast("success", "Post deleted.");
      fetchPosts();
    } catch {
      showToast("error", "Failed to delete post.");
    } finally {
      setDeleting(null);
      setDeleteConfirm(null);
    }
  };

  // ── Render ─────────────────────────────────────────────────────────────────
  return (
    <div style={{ fontFamily: "'Inter', sans-serif" }} className="min-h-screen bg-gray-50">
      {/* Google Font */}
      <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
      />

      {/* ── Top Bar ──────────────────────────────────────────────────────── */}
      <header className="bg-gradient-to-r from-indigo-700 to-purple-700 text-white shadow-xl">
        <div className="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between flex-wrap gap-3">
          <div className="flex items-center gap-3">
            <span className="text-3xl">📝</span>
            <div>
              <h1 className="text-xl font-black tracking-tight">Blog Manager</h1>
              <p className="text-indigo-200 text-xs">SnoutIQ Admin Panel</p>
            </div>
          </div>
          <div className="flex items-center gap-3">
            {view === "list" ? (
              <button
                id="admin-blog-new-post-btn"
                onClick={openNew}
                className="flex items-center gap-2 bg-white text-indigo-700 font-bold px-5 py-2.5 rounded-lg shadow hover:bg-indigo-50 transition text-sm"
              >
                <span className="text-lg">+</span> New Post
              </button>
            ) : (
              <button
                onClick={() => setView("list")}
                className="flex items-center gap-2 bg-white/20 hover:bg-white/30 text-white font-semibold px-5 py-2.5 rounded-lg transition text-sm"
              >
                ← Back to Posts
              </button>
            )}
          </div>
        </div>
      </header>

      {/* ── Toast notification ────────────────────────────────────────────── */}
      {toast && (
        <div
          className={`fixed top-5 right-5 z-50 flex items-center gap-3 px-5 py-3.5 rounded-xl shadow-2xl text-white font-semibold text-sm transition-all ${
            toast.type === "success"
              ? "bg-gradient-to-r from-green-500 to-emerald-600"
              : "bg-gradient-to-r from-red-500 to-rose-600"
          }`}
        >
          <span>{toast.type === "success" ? "✅" : "❌"}</span>
          {toast.msg}
        </div>
      )}

      {/* ── Delete confirmation modal ─────────────────────────────────────── */}
      {deleteConfirm && (
        <div className="fixed inset-0 bg-black/50 z-40 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full">
            <div className="text-5xl text-center mb-4">🗑️</div>
            <h3 className="text-xl font-bold text-gray-800 text-center mb-2">Delete Post?</h3>
            <p className="text-gray-600 text-center mb-6 text-sm">
              "<strong>{deleteConfirm.title}</strong>" will be permanently deleted.
            </p>
            <div className="flex gap-3">
              <button
                onClick={() => setDeleteConfirm(null)}
                className="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-lg transition text-sm"
              >
                Cancel
              </button>
              <button
                onClick={() => handleDelete(deleteConfirm)}
                disabled={deleting === deleteConfirm.id}
                className="flex-1 bg-gradient-to-r from-red-500 to-rose-600 text-white font-bold py-2.5 rounded-lg hover:opacity-90 transition text-sm disabled:opacity-60"
              >
                {deleting === deleteConfirm.id ? "Deleting…" : "Yes, Delete"}
              </button>
            </div>
          </div>
        </div>
      )}

      <div className="max-w-7xl mx-auto px-6 py-8">
        {/* ════════════════════════════════════ LIST VIEW ══════════════════ */}
        {view === "list" && (
          <div>
            {/* Stats */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
              {[
                { label: "Total Posts", value: posts.length, icon: "📄", color: "from-indigo-500 to-purple-600" },
                { label: "Published", value: posts.filter((p) => p.status === "published").length, icon: "🟢", color: "from-green-500 to-emerald-600" },
                { label: "Drafts", value: posts.filter((p) => p.status === "draft").length, icon: "🟡", color: "from-amber-400 to-orange-500" },
              ].map((stat) => (
                <div key={stat.label} className={`bg-gradient-to-br ${stat.color} text-white rounded-2xl p-5 shadow-lg`}>
                  <div className="text-3xl mb-1">{stat.icon}</div>
                  <div className="text-3xl font-black">{stat.value}</div>
                  <div className="text-sm opacity-80 font-medium">{stat.label}</div>
                </div>
              ))}
            </div>

            {/* Posts table */}
            <div className="bg-white rounded-2xl shadow-lg overflow-hidden">
              <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 className="font-bold text-gray-800 text-lg">All Blog Posts</h2>
                <span className="text-sm text-gray-400">{posts.length} posts</span>
              </div>

              {loading ? (
                <div className="p-12 text-center">
                  <div className="inline-block w-10 h-10 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin mb-3" />
                  <p className="text-gray-500 text-sm">Loading posts…</p>
                </div>
              ) : posts.length === 0 ? (
                <div className="p-16 text-center">
                  <div className="text-6xl mb-4">📭</div>
                  <p className="text-gray-500 font-medium">No posts yet.</p>
                  <button onClick={openNew} className="mt-4 text-indigo-600 hover:underline text-sm font-semibold">
                    Create your first post →
                  </button>
                </div>
              ) : (
                <div className="overflow-x-auto">
                  <table className="min-w-full divide-y divide-gray-100">
                    <thead className="bg-gray-50">
                      <tr>
                        {["Title", "Slug", "Status", "Date", "Actions"].map((h) => (
                          <th key={h} className="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            {h}
                          </th>
                        ))}
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-50">
                      {posts.map((post) => (
                        <tr key={post.id} className="hover:bg-indigo-50/40 transition">
                          <td className="px-6 py-4 max-w-xs">
                            <span className="font-semibold text-gray-800 text-sm line-clamp-2">{post.title}</span>
                          </td>
                          <td className="px-6 py-4">
                            <code className="text-xs bg-gray-100 text-indigo-600 px-2 py-1 rounded font-mono">
                              {post.slug}
                            </code>
                          </td>
                          <td className="px-6 py-4">
                            <span
                              className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold ${
                                post.status === "published"
                                  ? "bg-green-100 text-green-700"
                                  : "bg-amber-100 text-amber-700"
                              }`}
                            >
                              {post.status === "published" ? "🟢" : "🟡"}{" "}
                              {post.status.charAt(0).toUpperCase() + post.status.slice(1)}
                            </span>
                          </td>
                          <td className="px-6 py-4 text-sm text-gray-400 whitespace-nowrap">
                            {new Date(post.created_at).toLocaleDateString("en-IN", {
                              day: "numeric", month: "short", year: "numeric",
                            })}
                          </td>
                          <td className="px-6 py-4">
                            <div className="flex items-center gap-2">
                              <button
                                id={`admin-blog-edit-${post.id}`}
                                onClick={() => openEdit(post)}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg transition"
                              >
                                ✏️ Edit
                              </button>
                              {post.status === "published" && (
                                <Link
                                  to={`/blog/${post.slug}`}
                                  target="_blank"
                                  className="bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold px-3 py-1.5 rounded-lg transition"
                                >
                                  👁 View
                                </Link>
                              )}
                              <button
                                id={`admin-blog-delete-${post.id}`}
                                onClick={() => setDeleteConfirm(post)}
                                className="bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold px-3 py-1.5 rounded-lg transition"
                              >
                                🗑 Del
                              </button>
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          </div>
        )}

        {/* ════════════════════════════════════ EDITOR VIEW ════════════════ */}
        {view === "editor" && (
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {/* ── Main editor (2/3 width) ─────────────────────────────────── */}
            <div className="lg:col-span-2 flex flex-col gap-5">
              {/* Title */}
              <div className="bg-white rounded-2xl shadow p-6">
                <label className="block text-sm font-semibold text-gray-700 mb-2">
                  Post Title <span className="text-red-500">*</span>
                </label>
                <input
                  id="admin-blog-title"
                  type="text"
                  value={form.title}
                  onChange={(e) => handleChange("title", e.target.value)}
                  placeholder="Enter a compelling post title…"
                  className="w-full text-xl font-bold border-2 border-gray-200 focus:border-indigo-500 rounded-xl px-4 py-3 outline-none transition placeholder-gray-300"
                />
                {/* Slug preview */}
                <div className="mt-2 flex items-center gap-2">
                  <span className="text-xs text-gray-400">Slug:</span>
                  <input
                    id="admin-blog-slug"
                    type="text"
                    value={form.slug}
                    onChange={(e) => handleSlugChange(e.target.value)}
                    className="text-xs font-mono text-indigo-600 bg-indigo-50 border border-indigo-200 rounded-lg px-2 py-1 focus:outline-none focus:border-indigo-400 flex-1"
                    placeholder="auto-generated-slug"
                  />
                  {form.slug && (
                    <a
                      href={`/blog/${form.slug}`}
                      target="_blank"
                      rel="noreferrer"
                      className="text-xs text-indigo-500 hover:underline whitespace-nowrap"
                    >
                      Preview ↗
                    </a>
                  )}
                </div>
              </div>

              {/* Rich Text Editor */}
              <div className="bg-white rounded-2xl shadow p-6">
                <label className="block text-sm font-semibold text-gray-700 mb-3">
                  Content
                </label>
                <Suspense
                  fallback={
                    <div className="h-96 flex items-center justify-center bg-gray-50 rounded-xl">
                      <div className="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin" />
                    </div>
                  }
                >
                  <div className="quill-wrapper rounded-xl overflow-hidden border-2 border-gray-200 focus-within:border-indigo-400 transition">
                    <ReactQuill
                      theme="snow"
                      value={form.content}
                      onChange={(val) => handleChange("content", val)}
                      modules={QUILL_MODULES}
                      formats={QUILL_FORMATS}
                      placeholder="Write your blog post content here…"
                      style={{ minHeight: "480px" }}
                    />
                  </div>
                </Suspense>
              </div>

              {/* Excerpt */}
              <div className="bg-white rounded-2xl shadow p-6">
                <label className="block text-sm font-semibold text-gray-700 mb-2">
                  Excerpt <span className="text-gray-400 font-normal">(short summary shown in blog list)</span>
                </label>
                <textarea
                  id="admin-blog-excerpt"
                  value={form.excerpt}
                  onChange={(e) => handleChange("excerpt", e.target.value)}
                  rows={3}
                  maxLength={1000}
                  placeholder="Brief description of the post…"
                  className="w-full border-2 border-gray-200 focus:border-indigo-500 rounded-xl px-4 py-3 text-sm outline-none transition resize-none placeholder-gray-300"
                />
                <div className="text-right text-xs text-gray-400 mt-1">{form.excerpt.length}/1000</div>
              </div>
            </div>

            {/* ── Sidebar (1/3 width) ──────────────────────────────────────── */}
            <div className="flex flex-col gap-5">
              {/* Publish controls */}
              <div className="bg-white rounded-2xl shadow p-6">
                <h3 className="font-bold text-gray-800 mb-4 flex items-center gap-2">
                  <span>🚀</span> Publish
                </h3>
                <label className="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                <select
                  id="admin-blog-status"
                  value={form.status}
                  onChange={(e) => handleChange("status", e.target.value)}
                  className="w-full border-2 border-gray-200 focus:border-indigo-500 rounded-xl px-3 py-2.5 text-sm outline-none transition mb-4"
                >
                  <option value="draft">🟡 Draft</option>
                  <option value="published">🟢 Published</option>
                </select>

                <button
                  id="admin-blog-save-btn"
                  onClick={handleSave}
                  disabled={saving}
                  className="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold py-3 rounded-xl hover:opacity-90 transition disabled:opacity-60 flex items-center justify-center gap-2"
                >
                  {saving ? (
                    <>
                      <div className="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />
                      Saving…
                    </>
                  ) : (
                    <>{form.id ? "💾 Update Post" : "✅ Publish Post"}</>
                  )}
                </button>

                {form.id && form.status === "published" && (
                  <Link
                    to={`/blog/${form.slug}`}
                    target="_blank"
                    className="mt-3 w-full flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl transition text-sm"
                  >
                    👁 View Live Post
                  </Link>
                )}
              </div>

              {/* Featured Image */}
              <div className="bg-white rounded-2xl shadow p-6">
                <h3 className="font-bold text-gray-800 mb-4 flex items-center gap-2">
                  <span>🖼️</span> Featured Image
                </h3>
                <input
                  id="admin-blog-image"
                  type="url"
                  value={form.featured_image}
                  onChange={(e) => handleChange("featured_image", e.target.value)}
                  placeholder="https://example.com/image.jpg"
                  className="w-full border-2 border-gray-200 focus:border-indigo-500 rounded-xl px-3 py-2.5 text-sm outline-none transition"
                />
                {form.featured_image && (
                  <div className="mt-3 rounded-xl overflow-hidden border-2 border-indigo-100">
                    <img
                      src={form.featured_image}
                      alt="Preview"
                      className="w-full h-36 object-cover"
                      onError={(e) => { e.target.style.display = "none"; }}
                    />
                  </div>
                )}
              </div>

              {/* SEO */}
              <div className="bg-white rounded-2xl shadow p-6">
                <h3 className="font-bold text-gray-800 mb-4 flex items-center gap-2">
                  <span>🔍</span> SEO Settings
                </h3>
                <div className="flex flex-col gap-4">
                  <div>
                    <label className="block text-xs font-semibold text-gray-600 mb-1">
                      Meta Title <span className="text-gray-400">({form.meta_title.length}/60)</span>
                    </label>
                    <input
                      id="admin-blog-meta-title"
                      type="text"
                      value={form.meta_title}
                      onChange={(e) => handleChange("meta_title", e.target.value)}
                      maxLength={60}
                      placeholder="SEO title (defaults to post title)"
                      className="w-full border-2 border-gray-200 focus:border-indigo-500 rounded-xl px-3 py-2 text-sm outline-none transition"
                    />
                    {/* Character indicator */}
                    <div className="mt-1 h-1 bg-gray-100 rounded-full overflow-hidden">
                      <div
                        className={`h-1 rounded-full transition-all ${
                          form.meta_title.length > 55 ? "bg-red-400" :
                          form.meta_title.length > 40 ? "bg-amber-400" : "bg-green-400"
                        }`}
                        style={{ width: `${Math.min((form.meta_title.length / 60) * 100, 100)}%` }}
                      />
                    </div>
                  </div>
                  <div>
                    <label className="block text-xs font-semibold text-gray-600 mb-1">
                      Meta Description <span className="text-gray-400">({form.meta_description.length}/160)</span>
                    </label>
                    <textarea
                      id="admin-blog-meta-desc"
                      value={form.meta_description}
                      onChange={(e) => handleChange("meta_description", e.target.value)}
                      rows={3}
                      maxLength={160}
                      placeholder="Short description for search results…"
                      className="w-full border-2 border-gray-200 focus:border-indigo-500 rounded-xl px-3 py-2 text-sm outline-none transition resize-none"
                    />
                    <div className="mt-1 h-1 bg-gray-100 rounded-full overflow-hidden">
                      <div
                        className={`h-1 rounded-full transition-all ${
                          form.meta_description.length > 150 ? "bg-red-400" :
                          form.meta_description.length > 120 ? "bg-amber-400" : "bg-green-400"
                        }`}
                        style={{ width: `${Math.min((form.meta_description.length / 160) * 100, 100)}%` }}
                      />
                    </div>
                  </div>
                </div>

                {/* Google SERP Preview */}
                {(form.meta_title || form.title) && (
                  <div className="mt-4 p-3 bg-gray-50 rounded-xl border border-gray-200">
                    <p className="text-xs font-semibold text-gray-500 mb-2">SERP Preview</p>
                    <p className="text-blue-700 text-sm font-medium leading-tight truncate">
                      {form.meta_title || form.title}
                    </p>
                    <p className="text-green-700 text-xs">snoutiq.com/blog/{form.slug}</p>
                    <p className="text-gray-600 text-xs mt-1 line-clamp-2">
                      {form.meta_description || form.excerpt || "No description."}
                    </p>
                  </div>
                )}
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Quill custom styles */}
      <style>{`
        .quill-wrapper .ql-toolbar { border: none !important; border-bottom: 2px solid #e5e7eb !important; background: #f9fafb; }
        .quill-wrapper .ql-container { border: none !important; font-size: 1rem; }
        .quill-wrapper .ql-editor { min-height: 420px; padding: 1.25rem 1.5rem; }
        .quill-wrapper .ql-editor.ql-blank::before { color: #9ca3af; font-style: normal; }
        .quill-wrapper .ql-snow .ql-stroke { stroke: #6366f1; }
        .quill-wrapper .ql-snow .ql-fill { fill: #6366f1; }
        .quill-wrapper .ql-snow.ql-toolbar button:hover, .quill-wrapper .ql-snow .ql-toolbar button.ql-active { color: #4f46e5; background: #eef2ff; border-radius: 4px; }
      `}</style>
    </div>
  );
}
