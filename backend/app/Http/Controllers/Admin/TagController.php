<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    // ↓ Removed constructor

    public function index(){ $tags = Tag::orderBy('name')->paginate(50); return view('admin.tags.index', compact('tags')); }
    public function create(){ return view('admin.tags.form', ['tag'=>new Tag]); }

    public function store(Request $r)
    {
        $data = $r->validate(['name'=>['required','max:100'],'slug'=>['nullable','max:100']]);
        $t = Tag::create($data);
        return redirect()->route('admin.tags.edit',$t)->with('ok','Saved');
    }

    public function edit(Tag $tag){ return view('admin.tags.form', compact('tag')); }

    public function update(Request $r, Tag $tag)
    {
        $data = $r->validate(['name'=>['required','max:100'],'slug'=>['nullable','max:100']]);
        $tag->update($data);
        return back()->with('ok','Updated');
    }

    public function destroy(Tag $tag)
    {
        $tag->posts()->detach();
        $tag->delete();
        return back()->with('ok','Deleted');
    }
}
