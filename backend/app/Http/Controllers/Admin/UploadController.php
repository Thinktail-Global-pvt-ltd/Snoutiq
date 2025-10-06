<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    // ↓ Removed constructor

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required','image','mimes:jpg,jpeg,png,gif,webp','max:5120'],
        ]);
        $path = $request->file('file')->store('uploads','public');
        return response()->json(['success'=>true,'url'=>Storage::url($path)]);
    }
}
