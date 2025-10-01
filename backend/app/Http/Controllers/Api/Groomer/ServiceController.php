<?php

namespace App\Http\Controllers\Api\Groomer;

use App\Http\Controllers\Controller;
use App\Models\GroomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    /**
     * Resolve user id strictly from the request (frontend),
     * with very last fallback to auth/session if present.
     */
    protected function resolveUserId(Request $request): ?int
    {
        $id = $request->input('user_id')
            ?? $request->query('user_id')
            ?? $request->header('X-User-Id')
            ?? optional($request->user())->id
            ?? session('user_id');

        return $id ? (int) $id : null;
    }

    public function get(Request $request)
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
