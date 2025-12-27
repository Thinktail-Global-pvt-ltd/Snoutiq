<?php

namespace App\Http\Controllers\Api\Groomer;

use App\Http\Controllers\Controller;
use App\Models\GroomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Models\GroomerServiceCategory;
use Illuminate\Support\Facades\Schema;


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
        $role = session('role')
            ?? data_get(session('user'), 'role')
            ?? data_get(session('auth_full'), 'role');

        if ($role === 'doctor' || $role === 'receptionist') {
            $clinicId = session('clinic_id')
                ?? session('vet_registerations_temp_id')
                ?? session('vet_registeration_id')
                ?? session('vet_id')
                ?? data_get(session('user'), 'clinic_id')
                ?? data_get(session('auth_full'), 'clinic_id')
                ?? data_get(session('auth_full'), 'user.clinic_id');

            if ($clinicId) {
                return (int) $clinicId;
            }
        }

        $sid = session('user_id') ?? data_get(session('user'), 'id');
        return $sid ? (int) $sid : null;
    }

    /**
     * Normalize min/max price inputs and ensure a valid range is provided.
     *
     * @return array{0: float|null, 1: float|null, 2: float}
     *
     * @throws ValidationException
     */
    private function resolvePriceRange(Request $request, bool $priceAfterService = false): array
    {
        if ($priceAfterService) {
            return [null, null, null];
        }

        $price = $request->input('price');
        $priceMin = $request->input('price_min', $price);
        $priceMax = $request->input('price_max', $price);

        $price    = $price === '' ? null : $price;
        $priceMin = $priceMin === '' ? null : $priceMin;
        $priceMax = $priceMax === '' ? null : $priceMax;

        if ($priceMin === null && $priceMax === null) {
            throw ValidationException::withMessages([
                'price_min' => ['Price range is required.'],
            ]);
        }

        $priceMin = $priceMin !== null ? (float) $priceMin : null;
        $priceMax = $priceMax !== null ? (float) $priceMax : null;

        if ($priceMin !== null && $priceMax === null) {
            $priceMax = $priceMin;
        } elseif ($priceMax !== null && $priceMin === null) {
            $priceMin = $priceMax;
        }

        if ($priceMin !== null && $priceMax !== null && $priceMax < $priceMin) {
            throw ValidationException::withMessages([
                'price_max' => ['Max price must be greater than or equal to min price.'],
            ]);
        }

        $priceValue = $priceMax ?? $priceMin ?? 0;

        return [$priceMin, $priceMax, $priceValue];
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
      //  dd($services);

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
                'price'           => 'nullable|numeric|min:0',
                'price_min'       => 'nullable|numeric|min:0',
                'price_max'       => 'nullable|numeric|min:0',
                'price_after_service' => 'nullable|boolean',
                'duration'        => 'required|integer|min:1',
                'main_service'    => 'required|string',
                'status'          => 'required|string',
                'serviceCategory' => 'sometimes|nullable|integer|exists:groomer_service_categories,id',
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

            $priceAfterService = $request->boolean('price_after_service');
            [$priceMin, $priceMax, $priceValue] = $this->resolvePriceRange($request, $priceAfterService);

            $hasPriceMin = Schema::hasColumn('groomer_services', 'price_min');
            $hasPriceMax = Schema::hasColumn('groomer_services', 'price_max');
            $hasPriceAfterService = Schema::hasColumn('groomer_services', 'price_after_service');

            $serviceCategoryId = $request->input('serviceCategory');
            if (!$serviceCategoryId) {
                // if no category passed, pick the first for this user
                $serviceCategoryId = GroomerServiceCategory::where('user_id', $uid)->value('id');
                if (!$serviceCategoryId) {
                    // else pick any existing category as a global fallback
                    $serviceCategoryId = GroomerServiceCategory::value('id');
                }
                if (!$serviceCategoryId) {
                    // still none: create a shared default category on a safe existing user to avoid FK errors
                    $safeUserId = DB::table('users')->min('id');
                    if (!$safeUserId) {
                        return response()->json([
                            'status' => false,
                            'message' => 'No users available to assign a default service category.',
                        ], 422);
                    }
                    $defaultCategory = GroomerServiceCategory::firstOrCreate(
                        ['user_id' => $safeUserId, 'name' => 'Default'],
                        ['name' => 'Default']
                    );
                    $serviceCategoryId = $defaultCategory->id;
                }
            }

            $data = [
                'user_id'       => $uid,
                'name'          => $request->serviceName,
                'description'   => $request->description,
                'pet_type'      => $request->petType,
                'price'         => $priceAfterService ? null : $priceValue,
                'duration'      => $request->duration,
                'groomer_service_category_id' => $serviceCategoryId,
                'main_service'  => $request->main_service,
                'status'        => $request->status,
            ];

            // Only set optional pricing columns if the table has them
            if ($hasPriceMin) {
                $data['price_min'] = $priceAfterService ? null : $priceMin;
            }
            if ($hasPriceMax) {
                $data['price_max'] = $priceAfterService ? null : $priceMax;
            }
            if ($hasPriceAfterService) {
                $data['price_after_service'] = $priceAfterService;
            }

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
        } catch (ValidationException $e) {
            Log::warning('Groomer service validation failed', [
                'errors' => $e->errors(),
                'user_id' => $this->resolveUserId($request),
                'inputs' => $request->except(['servicePic']),
                'ip' => $request->ip(),
                'route' => $request->path(),
            ]);
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Groomer service creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $this->resolveUserId($request),
                'inputs' => $request->except(['servicePic']),
                'ip' => $request->ip(),
                'route' => $request->path(),
            ]);
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
                'serviceCategory' => 'sometimes|nullable|integer|exists:groomer_service_categories,id',
                'description'     => 'nullable|string',
                'petType'         => 'required',
                'price'           => 'nullable|numeric|min:0',
                'price_min'       => 'nullable|numeric|min:0',
                'price_max'       => 'nullable|numeric|min:0',
                'price_after_service' => 'nullable|boolean',
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

            $priceAfterService = $request->boolean('price_after_service');
            [$priceMin, $priceMax, $priceValue] = $this->resolvePriceRange($request, $priceAfterService);

            $hasPriceMin = Schema::hasColumn('groomer_services', 'price_min');
            $hasPriceMax = Schema::hasColumn('groomer_services', 'price_max');
            $hasPriceAfterService = Schema::hasColumn('groomer_services', 'price_after_service');

            $data = [
                'name'          => $request->serviceName,
                'description'   => $request->description,
                'pet_type'      => $request->petType,
                'price'         => $priceAfterService ? null : $priceValue,
                'duration'      => $request->duration,
                'main_service'  => $request->main_service,
                'status'        => $request->status,
            ];

            if ($hasPriceMin) {
                $data['price_min'] = $priceAfterService ? null : $priceMin;
            }
            if ($hasPriceMax) {
                $data['price_max'] = $priceAfterService ? null : $priceMax;
            }
            if ($hasPriceAfterService) {
                $data['price_after_service'] = $priceAfterService;
            }

            // Only override category if explicitly provided; otherwise keep current value
            if ($request->filled('serviceCategory')) {
                $data['groomer_service_category_id'] = $request->serviceCategory;
            }

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
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], 422);
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
