<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\Request;


use App\Models\Doctor;

use Illuminate\Support\Facades\File;

class VetRegisterationTempController extends Controller
{
   public function store(Request $request)
    {
        try {
            // ✅ Validation
            $data = $request->validate([
                'email'            => 'nullable|email|max:255',
                'mobile'           => 'nullable|string|max:15',
                'employee_id'      => 'nullable|string|max:255',
                'clinic_name'      => 'required|string|max:255',
                'city'             => 'required|string|max:255',
                'pincode'          => 'required|string|max:20',
                'coordinates'      => 'nullable|array',
                'address'          => 'nullable|string',
                'chat_price'       => 'nullable|numeric',
                'bio'              => 'nullable|string',

                // Image fields
                'hospital_profile' => 'nullable',
                'clinic_profile'   => 'nullable',

                // Doctors array
                'doctors'                        => 'nullable|array',
                'doctors.*.doctor_name'          => 'required|string|max:255',
                'doctors.*.doctor_email'         => 'nullable|email|max:255',
                'doctors.*.doctor_mobile'        => 'nullable|string|max:15',
                'doctors.*.doctor_license'       => 'nullable|string|max:255',
                'doctors.*.doctor_image'         => 'nullable',

                // Google place fields
                //'place_id'          => 'nullable|string|max:255',
                'business_status'   => 'nullable|string|max:255',
                'formatted_address' => 'nullable|string',
                'lat'               => 'nullable|numeric',
                'lng'               => 'nullable|numeric',
                'viewport_ne_lat'   => 'nullable|numeric',
                'viewport_ne_lng'   => 'nullable|numeric',
                'viewport_sw_lat'   => 'nullable|numeric',
                'viewport_sw_lng'   => 'nullable|numeric',
                'icon'              => 'nullable|string',
                'icon_background_color' => 'nullable|string',
                'icon_mask_base_uri'=> 'nullable|string',
                'open_now'          => 'nullable|boolean',
                'photos'            => 'nullable|array',
                'types'             => 'nullable|array',
                'compound_code'     => 'nullable|string',
                'global_code'       => 'nullable|string',
                'rating'            => 'nullable|numeric',
                'user_ratings_total'=> 'nullable|integer',
            ]);

            // ✅ Handle clinic/hospital profile image upload
            foreach (['hospital_profile', 'clinic_profile'] as $field) {
                if ($request->hasFile($field)) {
                    $fileName = $field . '_' . time() . '.' . $request->$field->extension();
                    $request->$field->move(public_path('photo'), $fileName);
                    $data[$field] = 'photo/' . $fileName;
                } elseif ($request->filled($field) && str_starts_with($request->$field, 'data:image')) {
                    $fileName = $field . '_' . time() . '.png';
                    $img = preg_replace('/^data:image\/\w+;base64,/', '', $request->$field);
                    $img = str_replace(' ', '+', $img);
                    File::put(public_path('photo/') . $fileName, base64_decode($img));
                    $data[$field] = 'photo/' . $fileName;
                }
            }

            // ✅ Convert arrays to JSON
            if (isset($data['coordinates'])) $data['coordinates'] = json_encode($data['coordinates']);
            if (isset($data['photos'])) $data['photos'] = json_encode($data['photos']);
            if (isset($data['types'])) $data['types'] = json_encode($data['types']);

            // ✅ Save clinic data
            $vet = VetRegisterationTemp::create($data);

            // ✅ Save doctors in doctors table
            if ($request->has('doctors')) {
                foreach ($request->doctors as $index => $doc) {
                    $doctorData = [
                        'vet_registeration_id' => $vet->id,
                        'doctor_name'    => $doc['doctor_name'],
                        'doctor_email'   => $doc['doctor_email'] ?? null,
                        'doctor_mobile'  => $doc['doctor_mobile'] ?? null,
                        'doctor_license' => $doc['doctor_license'] ?? null,
                    ];

                    // doctor image (file or base64)
                    if (isset($doc['doctor_image'])) {
                        if ($doc['doctor_image'] instanceof \Illuminate\Http\UploadedFile) {
                            $fileName = 'doctor_' . $index . '_' . time() . '.' . $doc['doctor_image']->extension();
                            $doc['doctor_image']->move(public_path('photo'), $fileName);
                            $doctorData['doctor_image'] = 'photo/' . $fileName;
                        } elseif (str_starts_with($doc['doctor_image'], 'data:image')) {
                            $fileName = 'doctor_' . $index . '_' . time() . '.png';
                            $img = preg_replace('/^data:image\/\w+;base64,/', '', $doc['doctor_image']);
                            $img = str_replace(' ', '+', $img);
                            File::put(public_path('photo/') . $fileName, base64_decode($img));
                            $doctorData['doctor_image'] = 'photo/' . $fileName;
                        }
                    }

                    Doctor::create($doctorData);
                }
            }

            // ✅ Response with doctors
            return response()->json([
                'message' => 'Vet registration stored successfully!',
                'data'    => $vet->load('doctors')
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed!',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong!',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

}
