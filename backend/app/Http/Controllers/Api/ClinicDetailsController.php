<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\Request;

class ClinicDetailsController extends Controller
{
    /**
     * Return clinic profile plus its doctors for a given clinic/vet id.
     */
    public function show(Request $request, $clinicId = null)
    {
        $clinicId = $clinicId ?? $request->input('clinic_id');

        if (!$clinicId || !is_numeric($clinicId) || (int) $clinicId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing clinic_id',
            ], 422);
        }

        $clinicId = (int) $clinicId;

        $clinic = VetRegisterationTemp::with(['doctors' => function ($q) {
            $q->orderBy('doctor_name');
        }])->find($clinicId);

        if (!$clinic) {
            return response()->json([
                'success' => false,
                'message' => 'Clinic not found',
            ], 404);
        }

        $rating = $clinic->rating !== null ? (float) $clinic->rating : null;
        $ratingsCount = $clinic->user_ratings_total !== null ? (int) $clinic->user_ratings_total : null;

        // If rating is missing and place_id is empty, use text search to find clinic
        if ($rating === null && empty($clinic->place_id) && !empty($clinic->name)) {
            try {
                $placesService = app(\App\Services\GooglePlacesLookupService::class);
                $found = $placesService->findPlaceByNameAndLocation($clinic->name, $clinic->city ?? $clinic->address);
                if ($found) {
                    $rating = $found['rating'];
                    $ratingsCount = $found['user_ratings_total'];

                    // Save cache to database
                    $clinic->place_id = $found['place_id'];
                    $clinic->rating = $rating;
                    $clinic->user_ratings_total = $ratingsCount;
                    if (empty($clinic->lat) && !empty($found['lat'])) {
                        $clinic->lat = $found['lat'];
                    }
                    if (empty($clinic->lng) && !empty($found['lng'])) {
                        $clinic->lng = $found['lng'];
                    }
                    $clinic->save();
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('google_places_text_search_failed_for_clinic_details', [
                    'clinic_id' => $clinic->id,
                    'clinic_name' => $clinic->name,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // If rating is missing but we have a place_id, query Google Places and cache in DB
        if ($rating === null && !empty($clinic->place_id)) {
            try {
                $placesService = app(\App\Services\GooglePlacesLookupService::class);
                $details = $placesService->placeDetails($clinic->place_id);
                if (!empty($details['success']) && isset($details['place']['rating'])) {
                    $rating = (float) $details['place']['rating'];
                    $ratingsCount = isset($details['place']['user_ratings_total']) ? (int) $details['place']['user_ratings_total'] : 0;

                    // Save cache to database
                    $clinic->rating = $rating;
                    $clinic->user_ratings_total = $ratingsCount;
                    $clinic->save();
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('google_rating_fetch_failed_for_clinic_details', [
                    'clinic_id' => $clinic->id,
                    'place_id' => $clinic->place_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $doctors = $clinic->doctors ?? collect();

        return response()->json([
            'success'      => true,
            'clinic_id'    => $clinicId,
            'clinic'       => $clinic,
            'doctors'      => $doctors->values(),
            'doctor_count' => $doctors->count(),
        ]);
    }
}

