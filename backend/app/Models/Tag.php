<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = ['name','slug'];

    public function getRouteKeyName(){ return 'slug'; }

    protected static function booted()
    {
        static::saving(function (Tag $t) {
            $t->slug = Str::slug($t->slug ?: $t->name);
        });
    }

    public function posts(){ return $this->belongsToMany(Post::class); }
}
