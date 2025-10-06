<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    protected $fillable = [
        'title','slug','content','excerpt',
        'meta_title','meta_description','featured_image','status',
    ];

    public function getRouteKeyName() { return 'slug'; }

    protected static function booted()
    {
        static::saving(function (Post $post) {
            $base = Str::slug($post->slug ?: $post->title);
            $post->slug = static::uniqueSlug($base, $post->id);
        });
    }

    public static function uniqueSlug(string $base, $ignoreId = null): string
    {
        $slug = $base; $i = 1;
        while (static::where('slug', $slug)
            ->when($ignoreId, fn($q)=>$q->where('id','!=',$ignoreId))
            ->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }

    public function categories(){ return $this->belongsToMany(Category::class); }
    public function tags(){ return $this->belongsToMany(Tag::class); }

    public function scopePublished($q){ return $q->where('status','published'); }
}
