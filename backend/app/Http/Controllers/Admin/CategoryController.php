<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // ↓ Removed constructor

    public function index()
    {
        $categories = Category::orderBy('name')->paginate(50);
        return view('admin.categories.index', compact('categories'));
    }

    public function create(){ return view('admin.categories.form', ['category'=>new Category]); }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name'=>['required','string','max:100'],
            'slug'=>['nullable','string','max:100'],
            'description'=>['nullable','string'],
        ]);
        $c = Category::create($data);
        return redirect()->route('admin.categories.edit',$c)->with('ok','Saved');
    }

    public function edit(Category $category){ return view('admin.categories.form', compact('category')); }

    public function update(Request $r, Category $category)
    {
        $data = $r->validate([
            'name'=>['required','string','max:100'],
            'slug'=>['nullable','string','max:100'],
            'description'=>['nullable','string'],
        ]);
        $category->update($data);
        return back()->with('ok','Updated');
    }

    public function destroy(Category $category)
    {
        $category->posts()->detach();
        $category->delete();
        return back()->with('ok','Deleted');
    }
}
