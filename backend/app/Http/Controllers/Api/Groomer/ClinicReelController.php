<?php

namespace App\Http\Controllers\Api\Groomer;

use App\Http\Controllers\Controller;
use App\Models\ClinicReel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ClinicReelController extends Controller
{
    /** Resolve user_id from: explicit user_id -> session -> vet_slug mapping */
    protected function resolveUserId(Request $request): ?int
    {
        // 1) explicit (frontend should pass this for admin)
        if ($request->filled('user_id')) {
            return (int) $request->input('user_id');
        }

        // 2) session fallback (if any)
        if (session()->has('user_id')) {
            return (int) session('user_id');
        }
        if ($request->user()) {
            return (int) $request->user()->id;
        }

        // 3) vet_slug → id (for public pages and widgets)
        $slug = $request->query('vet_slug') ?: $request->header('X-Vet-Slug');
        if ($slug) {
            $slug = trim($slug);
            $row = DB::table('vet_registerations_temp')
                ->select('id')
                ->whereRaw('LOWER(slug) = ?', [strtolower($slug)])
                ->orWhereRaw('LOWER(vet_slug) = ?', [strtolower($slug)])
                ->orWhereRaw('LOWER(clinic_slug) = ?', [strtolower($slug)])
                ->first();
            return $row?->id ? (int) $row->id : null;
        }

        return null;
    }

    /** Admin & public: list reels for a user/clinic */
    public function get(Request $request)
    {
        try {
            $uid = $this->resolveUserId($request);
            if (!$uid) {
                return response()->json(['status'=>false,'message'=>'user_id missing'], 422);
            }

            $query = ClinicReel::where('user_id', $uid)->orderBy('order_index')->orderByDesc('id');

            // optional filter: only active for public pages
            if ($request->boolean('only_active')) {
                $query->where('status', 'Active');
            }

            $items = $query->get();

            return response()->json([
                'status'  => true,
                'message' => 'Reels retrieved successfully',
                'data'    => $items
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to retrieve reels',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /** Show one reel (owner gated by user_id/slug) */
    public function show(Request $request, $id)
    {
        try {
            $uid = $this->resolveUserId($request);
            if (!$uid) return response()->json(['status'=>false,'message'=>'user_id missing'], 422);

            $reel = ClinicReel::where('user_id', $uid)->findOrFail($id);

            return response()->json([
                'status'=>true,'message'=>'Reel retrieved','data'=>$reel
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status'=>false,'message'=>'Reel not found','error'=>$e->getMessage()], 404);
        }
    }

    /** Create */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'title'       => 'required|string|max:255',
                'description' => 'nullable|string',
                'status'      => 'required|in:Active,Inactive',
                'order_index' => 'nullable|integer|min:0',
                'reel_url'    => 'nullable|url',
                'thumb'       => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
                'video'       => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/x-m4v|max:51200', // up to 50MB
                'user_id'     => 'nullable|integer',
                'vet_slug'    => 'nullable|string'
            ]);

            $uid = $this->resolveUserId($request);
            if (!$uid) return response()->json(['status'=>false,'message'=>'user_id missing'], 422);

            $data = [
                'user_id'     => $uid,
                'title'       => $request->title,
                'description' => $request->description,
                'reel_url'    => $request->reel_url,
                'status'      => $request->status,
                'order_index' => $request->input('order_index', 0),
            ];

            // file uploads
            $baseDir = public_path('reels');
            if (!File::exists($baseDir)) File::makeDirectory($baseDir, 0755, true);

            if ($request->hasFile('thumb')) {
                $f = $request->file('thumb');
                $name = 'thumb_' . time() . '_' . Str::random(8) . '.' . $f->getClientOriginalExtension();
                $f->move($baseDir, $name);
                $data['thumb_path'] = 'reels/'.$name;
            }

            if ($request->hasFile('video')) {
                $f = $request->file('video');
                $name = 'video_' . time() . '_' . Str::random(8) . '.' . $f->getClientOriginalExtension();
                $f->move($baseDir, $name);
                $data['video_path'] = 'reels/'.$name;
            }

            $reel = ClinicReel::create($data);

            return response()->json([
                'status'=>true,'message'=>'Reel created','data'=>$reel
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status'=>false,'message'=>'Validation error','errors'=>$e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['status'=>false,'message'=>'Failed to create reel','error'=>$e->getMessage()], 500);
        }
    }

    /** Update */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'title'       => 'required|string|max:255',
                'description' => 'nullable|string',
                'status'      => 'required|in:Active,Inactive',
                'order_index' => 'nullable|integer|min:0',
                'reel_url'    => 'nullable|url',
                'thumb'       => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
                'video'       => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/x-m4v|max:51200',
                'user_id'     => 'nullable|integer',
                'vet_slug'    => 'nullable|string'
            ]);

            $uid = $this->resolveUserId($request);
            if (!$uid) return response()->json(['status'=>false,'message'=>'user_id missing'], 422);

            $reel = ClinicReel::where('user_id', $uid)->findOrFail($id);

            $data = [
                'title'       => $request->title,
                'description' => $request->description,
                'reel_url'    => $request->reel_url,
                'status'      => $request->status,
                'order_index' => $request->input('order_index', $reel->order_index),
            ];

            $baseDir = public_path('reels');
            if (!File::exists($baseDir)) File::makeDirectory($baseDir, 0755, true);

            if ($request->hasFile('thumb')) {
                if ($reel->thumb_path && File::exists(public_path($reel->thumb_path))) {
                    File::delete(public_path($reel->thumb_path));
                }
                $f = $request->file('thumb');
                $name = 'thumb_' . time() . '_' . Str::random(8) . '.' . $f->getClientOriginalExtension();
                $f->move($baseDir, $name);
                $data['thumb_path'] = 'reels/'.$name;
            }

            if ($request->hasFile('video')) {
                if ($reel->video_path && File::exists(public_path($reel->video_path))) {
                    File::delete(public_path($reel->video_path));
                }
                $f = $request->file('video');
                $name = 'video_' . time() . '_' . Str::random(8) . '.' . $f->getClientOriginalExtension();
                $f->move($baseDir, $name);
                $data['video_path'] = 'reels/'.$name;
            }

            $reel->update($data);

            return response()->json(['status'=>true,'message'=>'Reel updated','data'=>$reel]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status'=>false,'message'=>'Validation error','errors'=>$e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['status'=>false,'message'=>'Failed to update reel','error'=>$e->getMessage()], 500);
        }
    }

    /** Delete */
    public function destroy(Request $request, $id)
    {
        try {
            $uid = $this->resolveUserId($request);
            if (!$uid) return response()->json(['status'=>false,'message'=>'user_id missing'], 422);

            $reel = ClinicReel::where('user_id', $uid)->findOrFail($id);

            if ($reel->thumb_path && File::exists(public_path($reel->thumb_path))) {
                File::delete(public_path($reel->thumb_path));
            }
            if ($reel->video_path && File::exists(public_path($reel->video_path))) {
                File::delete(public_path($reel->video_path));
            }

            $reel->delete();

            return response()->json(['status'=>true,'message'=>'Reel deleted']);
        } catch (\Throwable $e) {
            return response()->json(['status'=>false,'message'=>'Failed to delete reel','error'=>$e->getMessage()], 500);
        }
    }
}
