<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogPostController extends Controller
{
    // ─── Public ──────────────────────────────────────────────────────────────

    /**
     * GET /api/blog-posts
     * List all published posts (latest first).
     * Query params: ?status=all (admin use)
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'published');

        $query = Post::latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $posts = $query->get([
            'id', 'title', 'slug', 'excerpt',
            'meta_title', 'meta_description',
            'featured_image', 'status', 'created_at', 'updated_at',
        ]);

        return response()->json([
            'success' => true,
            'data'    => $posts,
        ]);
    }

    /**
     * GET /api/blog-posts/{slug}
     * Fetch a single post by slug.
     */
    public function show(string $slug)
    {
        $post = Post::where('slug', $slug)->first();

        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        }

        if ($post->status !== 'published') {
            return response()->json([
                'success' => false,
                'message' => 'Post not published.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $post,
        ]);
    }

    // ─── Admin (protected by EnsureAdminAuthenticated middleware) ────────────

    /**
     * GET /api/admin/blog-posts
     * List ALL posts (draft + published) for admin dashboard.
     */
    public function adminIndex()
    {
        $posts = Post::latest()->get([
            'id', 'title', 'slug', 'excerpt',
            'meta_title', 'meta_description',
            'featured_image', 'status', 'created_at', 'updated_at',
        ]);

        return response()->json([
            'success' => true,
            'data'    => $posts,
        ]);
    }

    /**
     * GET /api/admin/blog-posts/{id}
     * Fetch a single post by ID for editing (includes draft).
     */
    public function adminShow(int $id)
    {
        $post = Post::find($id);

        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $post,
        ]);
    }

    /**
     * POST /api/admin/blog-posts
     * Create a new post.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'slug'             => ['nullable', 'string', 'max:255'],
            'content'          => ['nullable', 'string'],
            'excerpt'          => ['nullable', 'string', 'max:1000'],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'featured_image'   => ['nullable', 'string', 'max:2048'],
            'status'           => ['nullable', 'in:published,draft'],
        ]);

        // Build a unique slug from the provided slug or title
        $base = Str::slug($validated['slug'] ?? $validated['title']);
        $validated['slug']   = Post::uniqueSlug($base);
        $validated['status'] = $validated['status'] ?? 'draft';

        $post = Post::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully.',
            'data'    => $post,
        ], 201);
    }

    /**
     * PUT /api/admin/blog-posts/{id}
     * Update an existing post.
     */
    public function update(Request $request, int $id)
    {
        $post = Post::find($id);

        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        }

        $validated = $request->validate([
            'title'            => ['sometimes', 'required', 'string', 'max:255'],
            'slug'             => ['sometimes', 'nullable', 'string', 'max:255'],
            'content'          => ['nullable', 'string'],
            'excerpt'          => ['nullable', 'string', 'max:1000'],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'featured_image'   => ['nullable', 'string', 'max:2048'],
            'status'           => ['nullable', 'in:published,draft'],
        ]);

        // If slug is being updated, ensure uniqueness (excluding self)
        if (! empty($validated['slug'])) {
            $base = Str::slug($validated['slug']);
            $validated['slug'] = Post::uniqueSlug($base, $post->id);
        }

        $post->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully.',
            'data'    => $post->fresh(),
        ]);
    }

    /**
     * DELETE /api/admin/blog-posts/{id}
     * Delete a post.
     */
    public function destroy(int $id)
    {
        $post = Post::find($id);

        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully.',
        ]);
    }
}
