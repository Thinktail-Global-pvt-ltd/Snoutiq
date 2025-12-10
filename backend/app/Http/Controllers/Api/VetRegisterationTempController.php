<?php



namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\Request;
use App\Models\Doctor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VetRegisterationTempController extends Controller
{
    public function show(VetRegisterationTemp $vet)
    {
        return response()->json([
            'id' => $vet->id,
            'name' => $vet->name,
            'city' => $vet->city,
        ]);
    }

    /** Make a unique slug from a name */
    private function makeUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = Str::random(6);
        }

        $slug = $base;
        $i = 1;
        while (VetRegisterationTemp::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $claimToken = $request->input('claim_token');
            $draftSlugInput = trim((string) $request->input('draft_slug'));
            $isClaim = filled($claimToken);
            $draftClinic = null;

            if ($isClaim) {
                $draftClinic = VetRegisterationTemp::where('claim_token', $claimToken)->first();

                if (! $draftClinic) {
                    return response()->json([
                        'message' => 'Draft clinic not found for the supplied claim token.',
                    ], 404);
                }

                if ($draftClinic->status !== 'draft') {
                    return response()->json([
                        'message' => 'This clinic has already been claimed.',
                    ], 409);
                }

                if ($draftClinic->draft_expires_at && $draftClinic->draft_expires_at->isPast()) {
                    return response()->json([
                        'message' => 'This draft clinic has expired. Please contact sales for a fresh invite.',
                    ], 410);
                }
            }

            if (! $isClaim && $draftSlugInput !== '') {
                $draftClinic = VetRegisterationTemp::where('slug', $draftSlugInput)->first();

                if (! $draftClinic) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Draft clinic not found for the supplied slug.',
                    ], 404);
                }

                if ($draftClinic->status !== 'draft') {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'This clinic slug is not available for claiming.',
                    ], 409);
                }

                if ($draftClinic->draft_expires_at && $draftClinic->draft_expires_at->isPast()) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'This draft clinic has expired. Please contact sales for a fresh invite.',
                    ], 410);
                }

                $isClaim = true;
            }

            // ✅ Validation
            $data = $request->validate([
                'draft_slug'       => 'nullable|string|max:255',
                'email'            => 'nullable|email|max:255',
                'mobile'           => 'nullable|string|max:15',
                'employee_id'      => 'nullable|string|max:255',
                'name'             => 'required|string|max:255',
                'city'             => 'required|string|max:255',
                'pincode'          => 'required|string|max:20',
               // 'license_no'       => 'required|string|max:255',
                'coordinates'      => 'nullable|array',
                'address'          => 'nullable|string',
                'chat_price'       => 'nullable|numeric',
                'bio'              => 'nullable|string',
                'license_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'password'         => 'nullable|string|min:6',

                // Image fields
                'hospital_profile' => 'nullable',
                'clinic_profile'   => 'nullable',

                // Doctors array
                'doctors'                  => 'nullable|array',
                'doctors.*.doctor_name'    => 'required|string|max:255',
                'doctors.*.doctor_email'   => 'nullable|email|max:255',
                'doctors.*.doctor_mobile'  => 'nullable|string|max:15',
                'doctors.*.doctor_license' => 'nullable|string|max:255',
                'doctors.*.doctor_image'   => 'nullable',
                'doctors.*.doctor_document'=> 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',

                // Google place fields
                'business_status'      => 'nullable|string|max:255',
                'formatted_address'    => 'nullable|string',
                'lat'                  => 'nullable|numeric',
                'lng'                  => 'nullable|numeric',
                'viewport_ne_lat'      => 'nullable|numeric',
                'viewport_ne_lng'      => 'nullable|numeric',
                'viewport_sw_lat'      => 'nullable|numeric',
                'viewport_sw_lng'      => 'nullable|numeric',
                'icon'                 => 'nullable|string',
                'icon_background_color'=> 'nullable|string',
                'icon_mask_base_uri'   => 'nullable|string',
                'open_now'             => 'nullable|boolean',
                'photos'               => 'nullable|array',
                'types'                => 'nullable|array',
                'compound_code'        => 'nullable|string',
                'global_code'          => 'nullable|string',
                'rating'               => 'nullable|numeric',
                'user_ratings_total'   => 'nullable|integer',
            ]);

            $data['password'] = $data['password'] ?? '123456';

            $draftSlug = $data['draft_slug'] ?? null;
            unset($data['draft_slug']);

            if (! $isClaim && empty($draftSlug)) {
                $data['slug'] = $this->makeUniqueSlug($data['name']);
            }

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

            if ($request->hasFile('license_document')) {
                File::ensureDirectoryExists(public_path('documents'));
                $file = $request->file('license_document');
                $fileName = 'license_' . time() . '_' . Str::random(6) . '.' . $file->extension();
                $file->move(public_path('documents'), $fileName);
                $data['license_document'] = 'documents/' . $fileName;
            }

            // ✅ Convert arrays to JSON
            if (isset($data['coordinates'])) $data['coordinates'] = json_encode($data['coordinates']);
            if (isset($data['photos']))      $data['photos']      = json_encode($data['photos']);
            if (isset($data['types']))       $data['types']       = json_encode($data['types']);

            if (! $isClaim) {
                $data['status'] = 'active';
                $data['claim_token'] = null;
                $data['draft_expires_at'] = null;
                $data['claimed_at'] = now();
            }

            // ✅ Save clinic data
            if ($isClaim) {
                $vet = $draftClinic;
                $vet->fill($data);
                $vet->status = 'active';
                $vet->claimed_at = now();
                $vet->owner_user_id = $vet->owner_user_id ?? $vet->id;
                $vet->claim_token = null;
                $vet->draft_expires_at = null;
                $vet->save();

                // clear stale doctors before replacing
                $vet->doctors()->delete();
            } else {
                $vet = VetRegisterationTemp::create($data);
                if (! $vet->owner_user_id) {
                    $vet->owner_user_id = $vet->id;
                    $vet->save();
                }
            }

            // ✅ Save doctors
            if ($request->has('doctors')) {
                foreach ($request->doctors as $index => $doc) {
                    $doctorData = [
                        'vet_registeration_id' => $vet->id,
                        'doctor_name'    => $doc['doctor_name'],
                        'doctor_email'   => $doc['doctor_email']  ?? null,
                        'doctor_mobile'  => $doc['doctor_mobile'] ?? null,
                        'doctor_license' => $doc['doctor_license']?? null,
                    ];

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

                    if (isset($doc['doctor_document']) && $doc['doctor_document'] instanceof \Illuminate\Http\UploadedFile) {
                        File::ensureDirectoryExists(public_path('documents/doctors'));
                        $docFile = $doc['doctor_document'];
                        $documentName = 'doctor_document_' . $vet->id . '_' . $index . '_' . time() . '.' . $docFile->extension();
                        $docFile->move(public_path('documents/doctors'), $documentName);
                        $doctorData['doctor_document'] = 'documents/doctors/' . $documentName;
                    }

                    Doctor::create($doctorData);
                }
            }

            DB::commit();

            // If this is a browser (non-JSON) request, redirect to landing page by slug
            if (! $request->wantsJson()) {
                return redirect()->away('https://snoutiq.com/backend/vet/' . $vet->slug);
            }

            return response()->json([
                'message' => $isClaim
                    ? 'Draft clinic claimed successfully!'
                    : 'Vet registration stored successfully!',
                'data'    => $vet->load('doctors')
            ], $isClaim ? 200 : 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Validation failed!',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Something went wrong!',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}


// namespace App\Http\Controllers\Api;

// use App\Http\Controllers\Controller;
// use App\Models\VetRegisterationTemp;
// use Illuminate\Http\Request;


// use App\Models\Doctor;

// use Illuminate\Support\Facades\File;

// class VetRegisterationTempController extends Controller
// {
//    public function store(Request $request)
//     {
//         try {
//             // ✅ Validation
//             $data = $request->validate([
//                 'email'            => 'nullable|email|max:255',
//                 'mobile'           => 'nullable|string|max:15',
//                 'employee_id'      => 'nullable|string|max:255',
//                 'name'      => 'required|string|max:255',
//                 'city'             => 'required|string|max:255',
//                 'pincode'          => 'required|string|max:20',
//                 'coordinates'      => 'nullable|array',
//                 'address'          => 'nullable|string',
//                 'chat_price'       => 'nullable|numeric',
//                 'bio'              => 'nullable|string',

//                 // Image fields
//                 'hospital_profile' => 'nullable',
//                 'clinic_profile'   => 'nullable',

//                 // Doctors array
//                 'doctors'                        => 'nullable|array',
//                 'doctors.*.doctor_name'          => 'required|string|max:255',
//                 'doctors.*.doctor_email'         => 'nullable|email|max:255',
//                 'doctors.*.doctor_mobile'        => 'nullable|string|max:15',
//                 'doctors.*.doctor_license'       => 'nullable|string|max:255',
//                 'doctors.*.doctor_image'         => 'nullable',

//                 // Google place fields
//                 //'place_id'          => 'nullable|string|max:255',
//                 'business_status'   => 'nullable|string|max:255',
//                 'formatted_address' => 'nullable|string',
//                 'lat'               => 'nullable|numeric',
//                 'lng'               => 'nullable|numeric',
//                 'viewport_ne_lat'   => 'nullable|numeric',
//                 'viewport_ne_lng'   => 'nullable|numeric',
//                 'viewport_sw_lat'   => 'nullable|numeric',
//                 'viewport_sw_lng'   => 'nullable|numeric',
//                 'icon'              => 'nullable|string',
//                 'icon_background_color' => 'nullable|string',
//                 'icon_mask_base_uri'=> 'nullable|string',
//                 'open_now'          => 'nullable|boolean',
//                 'photos'            => 'nullable|array',
//                 'types'             => 'nullable|array',
//                 'compound_code'     => 'nullable|string',
//                 'global_code'       => 'nullable|string',
//                 'rating'            => 'nullable|numeric',
//                 'user_ratings_total'=> 'nullable|integer',
//             ]);

           

//             // ✅ Handle clinic/hospital profile image upload
//             foreach (['hospital_profile', 'clinic_profile'] as $field) {
//                 if ($request->hasFile($field)) {
//                     $fileName = $field . '_' . time() . '.' . $request->$field->extension();
//                     $request->$field->move(public_path('photo'), $fileName);
//                     $data[$field] = 'photo/' . $fileName;
//                 } elseif ($request->filled($field) && str_starts_with($request->$field, 'data:image')) {
//                     $fileName = $field . '_' . time() . '.png';
//                     $img = preg_replace('/^data:image\/\w+;base64,/', '', $request->$field);
//                     $img = str_replace(' ', '+', $img);
//                     File::put(public_path('photo/') . $fileName, base64_decode($img));
//                     $data[$field] = 'photo/' . $fileName;
//                 }
//             }

//             // ✅ Convert arrays to JSON
//             if (isset($data['coordinates'])) $data['coordinates'] = json_encode($data['coordinates']);
//             if (isset($data['photos'])) $data['photos'] = json_encode($data['photos']);
//             if (isset($data['types'])) $data['types'] = json_encode($data['types']);

//             // ✅ Save clinic data
//             $vet = VetRegisterationTemp::create($data);

//             // ✅ Save doctors in doctors table
//             if ($request->has('doctors')) {
//                 foreach ($request->doctors as $index => $doc) {
//                     $doctorData = [
//                         'vet_registeration_id' => $vet->id,
//                         'doctor_name'    => $doc['doctor_name'],
//                         'doctor_email'   => $doc['doctor_email'] ?? null,
//                         'doctor_mobile'  => $doc['doctor_mobile'] ?? null,
//                         'doctor_license' => $doc['doctor_license'] ?? null,
//                     ];

//                     // doctor image (file or base64)
//                     if (isset($doc['doctor_image'])) {
//                         if ($doc['doctor_image'] instanceof \Illuminate\Http\UploadedFile) {
//                             $fileName = 'doctor_' . $index . '_' . time() . '.' . $doc['doctor_image']->extension();
//                             $doc['doctor_image']->move(public_path('photo'), $fileName);
//                             $doctorData['doctor_image'] = 'photo/' . $fileName;
//                         } elseif (str_starts_with($doc['doctor_image'], 'data:image')) {
//                             $fileName = 'doctor_' . $index . '_' . time() . '.png';
//                             $img = preg_replace('/^data:image\/\w+;base64,/', '', $doc['doctor_image']);
//                             $img = str_replace(' ', '+', $img);
//                             File::put(public_path('photo/') . $fileName, base64_decode($img));
//                             $doctorData['doctor_image'] = 'photo/' . $fileName;
//                         }
//                     }

//                     Doctor::create($doctorData);
//                 }
//             }

//             // ✅ Response with doctors
//             return response()->json([
//                 'message' => 'Vet registration stored successfully!',
//                 'data'    => $vet->load('doctors')
//             ], 201);

//         } catch (\Illuminate\Validation\ValidationException $e) {
//             return response()->json([
//                 'message' => 'Validation failed!',
//                 'errors'  => $e->errors()
//             ], 422);
//         } catch (\Exception $e) {
//             return response()->json([
//                 'message' => 'Something went wrong!',
//                 'error'   => $e->getMessage()
//             ], 500);
//         }
//     }

// }
