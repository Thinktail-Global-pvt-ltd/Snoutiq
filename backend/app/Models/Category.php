<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = ['name','slug','description'];

    public function getRouteKeyName(){ return 'slug'; }

    protected static function booted()
    {
        static::saving(function (Category $c) {
            $c->slug = Str::slug($c->slug ?: $c->name);
        });
    }

    public function posts(){ return $this->belongsToMany(Post::class); }
}
