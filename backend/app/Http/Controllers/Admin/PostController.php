<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Resolve a view name from multiple possible paths.
     * Adjust the order or add your own filenames if needed.
     */
    private function v(string $name, array $data = [])
    {
        $candidates = [
            // original nested paths (keep working if you add folders later)
            "admin.posts.$name",
            "posts.$name",

            // flat files directly under resources/views
            "posts_$name",     // e.g. posts_index, posts_form  <-- RECOMMENDED
            "post_$name",      // e.g. post_index,  post_form
            $name,             // e.g. index.blade.php or form.blade.php at root

            // very explicit fallbacks
            $name === 'index' ? 'posts' : null, // if you named list as posts.blade.php
        ];

        foreach ($candidates as $view) {
            if ($view && view()->exists($view)) {
                return view($view, $data);
            }
        }

        abort(500, 'View not found. Looked for: '.implode(', ', array_filter($candidates)));
    }

    public function index()
    {
        $posts = Post::latest()->paginate(50);
        return $this->v('index', compact('posts'));
    }

    public function create()
    {
        return $this->v('form', [
            'post' => new Post,
            'categories' => Category::orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
        ]);
    }

    public function edit(Post $post)
    {
        return $this->v('form', [
            'post' => $post,
            'categories' => Category::orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $post = Post::create($data);
        $post->categories()->sync($request->input('categories', []));
        $post->tags()->sync($request->input('tags', []));
        return redirect()->route('admin.posts.edit', $post)->with('ok','Saved');
    }

    public function update(Request $request, Post $post)
    {
        $data = $this->validated($request, $post->id);
        $post->update($data);
        $post->categories()->sync($request->input('categories', []));
        $post->tags()->sync($request->input('tags', []));
        return back()->with('ok','Updated');
    }

    public function destroy(Post $post)
    {
        $post->categories()->detach();
        $post->tags()->detach();
        $post->delete();
        return back()->with('ok','Deleted');
    }

    private function validated(Request $r, $ignoreId = null): array
    {
        return $r->validate([
            'title'            => ['required','string','max:255'],
            'slug'             => ['nullable','string','max:255'],
            'content'          => ['nullable','string'],
            'excerpt'          => ['nullable','string'],
            'meta_title'       => ['nullable','string','max:255'],
            'meta_description' => ['nullable','string','max:1000'],
            'featured_image'   => ['nullable','string'],
            'status'           => ['required','in:published,draft'],
        ]);
    }
}
