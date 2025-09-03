<?php

namespace App\Http\Controllers\Api\Groomer;

use App\Http\Controllers\Controller;
use App\Models\GroomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function get(Request $request){
       try {
            $services = GroomerService::where('user_id', $request->user()->id)
                ->with('category')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Services retrieved successfully',
                'data' => $services,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve services',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function store(Request $request)
    {
        $request->validate([
            'serviceName' => 'required|string|max:255',
            'serviceCategory' => 'required|exists:groomer_service_categories,id',
            'description' => 'nullable|string',
            'petType' => 'required',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'status' => 'required',
            'main_service' => 'required',
            'servicePic' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $data = [
                'user_id' => $request->user()->id,
                'groomer_service_category_id' => $request->serviceCategory,
                'name' => $request->serviceName,
                'description' => $request->description,
                'pet_type' => $request->petType,
                'price' => $request->price,
                'duration' => $request->duration,
                'main_service' => $request->main_service,
                
                'status' => $request->status,
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
                'status' => true,
                'message' => 'Service created successfully',
                'data' => $service,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create service',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function view(Request $request, $id)
    {
        try {
            $service = GroomerService::where('user_id', $request->user()->id)->findOrFail($id);

            return response()->json([
                'status' => true,
                'message' => 'Service retrieved successfully',
                'data' => $service,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Service not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function edit(Request $request, $id)
    {
        try {
            $service = GroomerService::where('user_id', $request->user()->id)->findOrFail($id);

            return response()->json([
                'status' => true,
                'message' => 'Service retrieved for editing',
                'data' => $service,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Service not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'serviceName' => 'required|string|max:255',
            'serviceCategory' => 'required|exists:groomer_service_categories,id',
            'description' => 'nullable|string',
            'petType' => 'required',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'status' => 'required',
            'main_service' => 'required',
            'servicePic' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $service = GroomerService::where('user_id', $request->user()->id)->findOrFail($id);

            $data = [
                'groomer_service_category_id' => $request->serviceCategory,
                'name' => $request->serviceName,
                'description' => $request->description,
                'pet_type' => $request->petType,
                'price' => $request->price,
                'duration' => $request->duration,
                'main_service' => $request->main_service,
                'status' => $request->status,
            ];

            if ($request->hasFile('servicePic')) {
                // Delete old image if exists
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
                'status' => true,
                'message' => 'Service updated successfully',
                'data' => $service,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update service',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}