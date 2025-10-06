<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Support\Facades\Cache;

class BlogController extends Controller
{
    public function index()
    {
        $perPage = (int) config('blog.posts_per_page', 10);

        $posts = Cache::remember('blog.index.'.request('page',1), 60, function () use ($perPage) {
            return Post::published()->latest()->paginate($perPage);
        });

        return view('blog.index', [
            'posts' => $posts,
            'site'  => config('blog.site_name', config('app.name')),
        ]);
    }

    public function show(Post $post)
    {
        abort_unless($post->status === 'published', 404);
        return view('blog.show', ['post'=>$post, 'site'=>config('blog.site_name', config('app.name'))]);
    }

    public function category(Category $category)
    {
        $posts = $category->posts()->published()->latest()
            ->paginate(config('blog.posts_per_page', 10));

        return view('blog.category', compact('category','posts'));
    }

    public function tag(Tag $tag)
    {
        $posts = $tag->posts()->published()->latest()
            ->paginate(config('blog.posts_per_page', 10));

        return view('blog.tag', compact('tag','posts'));
    }

    public function sitemap()
    {
        $posts = Post::published()->latest()->take(1000)->get();
        $cats  = Category::orderBy('name')->get();

        return response()->view('blog.sitemap', compact('posts','cats'))
            ->header('Content-Type','application/xml');
    }

    public function feed()
    {
        $posts = Post::published()->latest()->take(20)->get();
        return response()->view('blog.feed', compact('posts'))
            ->header('Content-Type','application/rss+xml; charset=utf-8');
    }
}
