<?php

namespace App\Http\Controllers\Api\Groomer;

use App\Http\Controllers\Controller;
use App\Models\GroomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\DB;


class ServiceController extends Controller
{

        private function resolveUserId(Request $request): ?int
    {
        // 1) explicit user_id (query/body)
        $uid = $request->input('user_id') ?? $request->query('user_id');
        if ($uid !== null && $uid !== '') {
            return (int) $uid;
        }
                     
        // 2) vet_slug => take the row id from vet_registerations_temp (slug column exists)
        $slug = $request->query('vet_slug') ?? $request->query('clinic_slug');
                                      
        if ($slug) {
            $row = DB::table('vet_registerations_temp')
                ->select('id')
                ->whereRaw('LOWER(slug) = ?', [strtolower($slug)])
                ->first();
                  //dd($row);   

            if ($row) {
                return (int) $row->id;   // <-- this id will be used as user_id
            }
            // slug diya hai par match nahi mila
            return null;
        }

        // 3) headers (frontend bhej sakta hai)
        $h = $request->header('X-User-Id')
            ?? $request->header('X-Acting-User')
            ?? $request->header('X-Session-User');
        if ($h) {
            return (int) $h;
        }

        // 4) last resort: session
        $sid = session('user_id') ?? data_get(session('user'), 'id');
        return $sid ? (int) $sid : null;
    }

 public function get(Request $request)
{
    try {
     
        $uid = null;

        // if vet_slug passed
        if ($request->has('vet_slug')) {
            $slug = strtolower($request->get('vet_slug'));

            $vet = \DB::table('vet_registerations_temp')
                ->whereRaw('LOWER(slug) = ?', [$slug])
                ->first();
                   

            if ($vet) {
                $uid = $vet->id; // 👈 yahan user_id ki jagah vet ka id use karna hoga
            }
        }

        // fallback: user_id param
        if (!$uid && $request->has('user_id')) {
            $uid = $request->get('user_id');
        }

        if (!$uid) {
            return response()->json([
                'status' => false,
                'message' => 'user_id missing (slug not found)'
            ], 422);
        }

        $services = GroomerService::where('user_id', $uid)->get();
        dd($vet);

        return response()->json([
            'status' => true,
            'message' => 'Services retrieved successfully',
            'data' => $services,
        ], 200);

    } catch (\Throwable $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to retrieve services',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Resolve user id strictly from the request (frontend),
     * with very last fallback to auth/session if present.
     */

    
    protected function resolveUserId__old(Request $request): ?int
    {
        $vetSlug = $request->input('vet_slug')
            ?? $request->query('vet_slug')
            ?? $request->header('X-Vet-Slug');

        if ($vetSlug) {
            // slug OR vet_slug column—use whichever exists in your table
            $uid = DB::table('vet_registerations_temp')
                ->where(function ($q) use ($vetSlug) {
                    $q->where('slug', $vetSlug)
                      ->orWhere('vet_slug', $vetSlug);
                })
                ->value('user_id');

            if ($uid) {
                return (int) $uid;
            }
        }

        $id = $request->input('user_id')
            ?? $request->query('user_id')
            ?? $request->header('X-User-Id')
            ?? optional($request->user())->id
            ?? session('user_id');

        return $id ? (int) $id : null;
    }
    

    public function get__old(Request $request)
    {
        try {
            $uid = $this->resolveUserId($request);
            if (!$uid) {
                return response()->json([
                    'status'  => false,
                    'message' => 'user_id missing',
                ], 422);
            }

            $services = GroomerService::where('user_id', $uid)
                ->with('category')
                ->get();

            return response()->json([
                'status'  => true,
                'message' => 'Services retrieved successfully',
                'data'    => $services,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to retrieve services',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }



    public function store(Request $request)
    {
        try {
            $request->validate([
                'serviceName'     => 'required|string',
                'petType'         => 'required|string',
                'price'           => 'required|numeric',
                'duration'        => 'required|integer',
                'main_service'    => 'required|string',
                'status'          => 'required|string',
                'serviceCategory' => 'nullable|integer',
                'description'     => 'nullable|string',
                'servicePic'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'user_id'         => 'nullable|integer',
            ]);

            $uid = $this->resolveUserId($request);
            if (!$uid) {
                return response()->json([
                    'status'  => false,
                    'message' => 'user_id missing',
                ], 422);
            }

            $data = [
                'user_id'       => $uid,
                'name'          => $request->serviceName,
                'description'   => $request->description,
                'pet_type'      => $request->petType,
                'price'         => $request->price,
                'duration'      => $request->duration,
                'main_service'  => $request->main_service,
                'status'        => $request->status,
            ];

            if ($request->hasFile('servicePic')) {
                $directory = public_path('service_pics');
                if (!File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }
                $file = $request->file('servicePic');
                $uniqueName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $file->move($directory, $uniqueName);
                $data['service_pic'] = 'service_pics/' . $uniqueName;
            }

            $service = GroomerService::create($data);

            return response()->json([
                'status'  => true,
                'message' => 'Service created successfully',
                'data'    => $service,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to create service',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function view(Request $request, $id)
    {
        try {
            $uid = $this->resolveUserId($request);
            if (!$uid) {
                return response()->json([
                    'status'  => false,
                    'message' => 'user_id missing',
                ], 422);
            }

            $service = GroomerService::where('user_id', $uid)->findOrFail($id);

            return response()->json([
                'status'  => true,
                'message' => 'Service retrieved successfully',
                'data'    => $service,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Service not found',
                'error'   => $e->getMessage(),
            ], 404);
        }
    }

    public function edit(Request $request, $id)
    {
        try {
            $uid = $this->resolveUserId($request);
            if (!$uid) {
                return response()->json([
                    'status'  => false,
                    'message' => 'user_id missing',
                ], 422);
            }

            $service = GroomerService::where('user_id', $uid)->findOrFail($id);

            return response()->json([
                'status'  => true,
                'message' => 'Service retrieved for editing',
                'data'    => $service,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Service not found',
                'error'   => $e->getMessage(),
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'serviceName'     => 'required|string|max:255',
                'serviceCategory' => 'nullable|integer',
                'description'     => 'nullable|string',
                'petType'         => 'required',
                'price'           => 'required|numeric|min:0',
                'duration'        => 'required|integer|min:1',
                'status'          => 'required',
                'main_service'    => 'required',
                'servicePic'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'user_id'         => 'nullable|integer',
            ]);

            $uid = $this->resolveUserId($request);
            if (!$uid) {
                return response()->json([
                    'status'  => false,
                    'message' => 'user_id missing',
                ], 422);
            }

            $service = GroomerService::where('user_id', $uid)->findOrFail($id);

            $data = [
                'name'          => $request->serviceName,
                'description'   => $request->description,
                'pet_type'      => $request->petType,
                'price'         => $request->price,
                'duration'      => $request->duration,
                'main_service'  => $request->main_service,
                'status'        => $request->status,
            ];

            if ($request->hasFile('servicePic')) {
                if ($service->service_pic && File::exists(public_path($service->service_pic))) {
                    File::delete(public_path($service->service_pic));
                }

                $directory = public_path('service_pics');
                if (!File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }
                $file = $request->file('servicePic');
                $uniqueName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $file->move($directory, $uniqueName);
                $data['service_pic'] = 'service_pics/' . $uniqueName;
            }

            $service->update($data);

            return response()->json([
                'status'  => true,
                'message' => 'Service updated successfully',
                'data'    => $service,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to update service',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $uid = $this->resolveUserId($request);
            if (!$uid) {
                return response()->json([
                    'status'  => false,
                    'message' => 'user_id missing',
                ], 422);
            }

            $service = GroomerService::where('user_id', $uid)->findOrFail($id);
            $service->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Service deleted successfully',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Service not found',
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to delete service',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
