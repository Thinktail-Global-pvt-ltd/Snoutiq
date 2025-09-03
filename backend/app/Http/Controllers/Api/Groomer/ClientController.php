<?php

namespace App\Http\Controllers\Api\Groomer;
use App\Http\Controllers\Controller;

use App\Models\GroomerClient;
use App\Models\GroomerClientPet;
use Illuminate\Http\Request;
use App\Models\GroomerBooking;

class ClientController extends Controller
{
    
    public function get(Request $request)
    {
return response()->json([
    'status'=>true,
    'data'=>GroomerClient::where('user_id',$request->user()->id)->with("pets")->orderBy('id','desc')->get()->map(function ($data)  {
      $lastVisit = GroomerBooking::where('customer_id',$data->id)->where('customer_type','online')->latest()->first()?->date??'---';
      $totalVisit = GroomerBooking::where('customer_id',$data->id)->where('customer_type','online')->count();
        return array_merge(['totalVisits'=>$totalVisit,'lastVisit'=>$lastVisit],$data->toArray());
    })
]);
    }
      public function single($id, Request $request)
    {
        $profile = GroomerClient::where('user_id', $request->user()->id)->where('id', $id)->first();
        if (!$profile) {
            return response()->json([
                'status' => false,
                'message' => 'Client not found!'
            ], 404);
        }
        $pets = GroomerClientPet::where('user_id', $request->user()->id)->where('groomer_client_id', $id)->get();
        return response()->json([
            'status' => true,
            'data' => $profile,
            'pets' => $pets
        ]);
    }
    public function store(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'tag' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'pincode' => 'required|string|max:20',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'pets' => 'required|array|min:1',
            'pets.*.petName' => 'required|string|max:255',
            'pets.*.petType' => 'required|string|max:255',
            'pets.*.breed' => 'required|string|max:255',
            'pets.*.dob' => 'required|date',
            'pets.*.gender' => 'required|in:Male,Female',
            'pets.*.medicalHistory' => 'nullable|array',
            'pets.*.medicalHistory.*.condition' => 'nullable|string|max:255',
            'pets.*.medicalHistory.*.date' => 'nullable|date',
            'pets.*.medicalHistory.*.is_recovered' => 'nullable|boolean',
            'pets.*.vaccinationLog' => 'nullable|array',
            'pets.*.vaccinationLog.*.name' => 'nullable|string|max:255',
            'pets.*.vaccinationLog.*.date' => 'nullable|date',
        ]);

        try {
            // Get the authenticated user's ID
            $userId = $request->user()->id;

            // Create the client
            $client = GroomerClient::create([
                'tag' => $validated['tag'],
                'name' => $validated['name'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'pincode' => $validated['pincode'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'user_id' => $userId,
            ]);

            // Create pets
            foreach ($validated['pets'] as $petData) {
                GroomerClientPet::create([
                    'name' => $petData['petName'],
                    'type' => $petData['petType'],
                    'breed' => $petData['breed'],
                    'dob' => $petData['dob'],
                    'gender' => $petData['gender'],
                    'medicalHistory' => !empty($petData['medicalHistory']) ? json_encode($petData['medicalHistory']) : null,
                    'vaccinationLog' => !empty($petData['vaccinationLog']) ? json_encode($petData['vaccinationLog']) : null,
                    'user_id' => $userId,
                    'groomer_client_id' => $client->id,
                ]);
            }

            return response()->json([
                'message' => 'Client and pets created successfully',
                'client' => $client,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating client: '. $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
  public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'tag' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'pincode' => 'required|string|max:10',
            'pets' => 'required|array|min:1',
            'pets.*.id' => 'nullable|integer|exists:groomer_client_pets,id',
            'pets.*.name' => 'required|string|max:255',
            'pets.*.type' => 'required|string|max:100',
            'pets.*.breed' => 'required|string|max:100',
            'pets.*.dob' => 'required|date',
            'pets.*.gender' => 'required|in:Male,Female',
            'pets.*.medicalHistory' => 'nullable|string',
            'pets.*.vaccinationLog' => 'nullable|string',
        ]);

        try {
            $client = GroomerClient::where('id', $id)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$client) {
                return response()->json([
                    'status' => false,
                    'message' => 'Client not found or unauthorized',
                ], 404);
            }

            $client->update([
                'tag' => $validated['tag'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'pincode' => $validated['pincode'],
                'user_id' => $request->user()->id,
            ]);

            $existingPetIds = GroomerClientPet::where('groomer_client_id', $client->id)
                ->pluck('id')
                ->toArray();
            $submittedPetIds = array_filter(array_column($validated['pets'], 'id'));

            GroomerClientPet::where('groomer_client_id', $client->id)
                ->whereNotIn('id', $submittedPetIds)
                ->delete();

            $updatedPets = [];
            foreach ($validated['pets'] as $petData) {
                $pet = null;
                if (isset($petData['id']) && in_array($petData['id'], $existingPetIds)) {
                    $pet = GroomerClientPet::where('id', $petData['id'])
                        ->where('groomer_client_id', $client->id)
                        ->first();
                    if ($pet) {
                        $pet->update([
                            'name' => $petData['name'],
                            'type' => $petData['type'],
                            'breed' => $petData['breed'],
                            'dob' => $petData['dob'],
                            'gender' => $petData['gender'],
                            'medicalHistory' => $petData['medicalHistory'] ?: null,
                            'vaccinationLog' => $petData['vaccinationLog'] ?: null,
                            'user_id' => $request->user()->id,
                        ]);
                    }
                }
                if (!$pet) {
                    $pet = GroomerClientPet::create([
                        'name' => $petData['name'],
                        'type' => $petData['type'],
                        'breed' => $petData['breed'],
                        'dob' => $petData['dob'],
                        'gender' => $petData['gender'],
                        'medicalHistory' => $petData['medicalHistory'] ?: null,
                        'vaccinationLog' => $petData['vaccinationLog'] ?: null,
                        'user_id' => $request->user()->id,
                        'groomer_client_id' => $client->id,
                    ]);
                }
                $updatedPets[] = $pet;
            }

            return response()->json([
                'status' => true,
                'message' => 'Client and pets updated successfully',
                'data' => $client,
                'pets' => $updatedPets,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update client: ' . $e->getMessage(),
            ], 500);
        }
    }
}