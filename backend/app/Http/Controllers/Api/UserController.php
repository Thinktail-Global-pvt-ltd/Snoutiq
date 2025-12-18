<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\GroomerBooking;
use App\Models\GroomerEmployee;
use App\Models\GroomerProfile;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserPet;
use App\Models\GroomerClientPet;
use App\Models\GroomerClient;
use App\Models\GroomerService;
use App\Models\UserRating;
use Illuminate\Support\Str;
use Carbon\Carbon;

use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\DB;
class UserController extends Controller
{
    //
  public function profile(Request $request)
{
    $user = $request->user();

    $profile = UserProfile::where('user_id', $user->id)->first();

    if (!$profile) {
        return response()->json([
            'message' => 'Profile not found.',
        ], 404);
    }

    
    $profileData = $profile->toArray();
    if ($profile->profile_pic_link) {
        $profileData['profile_pic_link'] = url($profile->profile_pic_link);
    }

    return response()->json([
        'message' => 'Profile fetched successfully.',
        'profile' => $profileData
    ]);
}
    public function my_bookings(Request $request){
        $limit = filled($request->limit) ? (int)$request->limit : 1000;

        // Resolve user id from auth, session, header or query
        $uid = optional($request->user())->id ?? (int) ($request->session()->get('user_id') ?? 0);
        if (!$uid) { $uid = (int) ($request->header('X-Session-User') ?? $request->query('user_id', 0)); }
        if (!$uid) { return response()->json(['message' => 'Not authenticated'], 401); }

        $rows = DB::table('bookings')
            ->where('user_id', $uid)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function($b){
                $out = [];
                $out['id'] = $b->id;
                $status = $b->status ?? 'pending';
                $out['status'] = $status;
                $sl = strtolower((string)$status);
                // Date classification: if scheduled time is past, treat as Completed
                $when = $b->scheduled_for ?? $b->booking_created_at ?? null;
                $isPast = false;
                if ($when) {
                    try { $isPast = \Carbon\Carbon::parse($when)->lt(\Carbon\Carbon::now()); } catch (\Throwable $e) {}
                }
                if ($sl === 'completed' || $isPast) {
                    $out['tab'] = 'Completed';
                } else {
                    // Do not expose Cancelled tab; keep only Upcoming/Completed
                    $out['tab'] = 'Upcoming';
                }
                $out['doctorName'] = isset($b->assigned_doctor_id) && $b->assigned_doctor_id
                    ? (DB::table('doctors')->where('id',$b->assigned_doctor_id)->value('doctor_name') ?? ('Doctor #'.$b->assigned_doctor_id))
                    : 'Doctor';
                if (isset($b->clinic_id) && $b->clinic_id) {
                    $clinicName = DB::table('vet_registerations_temp')->where('id',$b->clinic_id)->value('name');
                    if (!$clinicName) {
                        $clinicSlug = DB::table('vet_registerations_temp')->where('id',$b->clinic_id)->value('slug');
                        $clinicName = $clinicSlug ?: ('Clinic #'.$b->clinic_id);
                    }
                    $out['clinic'] = $clinicName;
                } else {
                    $out['clinic'] = 'Clinic';
                }
                $dt = $b->scheduled_for ?? $b->booking_created_at ?? null;
                $out['date'] = $dt ? substr($dt,0,10) : '';
                $out['time'] = $dt ? substr($dt,11,5) : '';
                if (isset($b->pet_id) && $b->pet_id) {
                    $pet = DB::table('user_pets')->where('id',$b->pet_id)->first();
                    $out['petName'] = $pet->name ?? '—';
                    $out['petBreed'] = ($pet->type ?? '').($pet->breed ? (' - '.$pet->breed) : '');
                }
                return $out;
            });

        return response()->json(['data' => $rows]);
    }
   public function my_booking($id, Request $request)
{
    // Resolve user id similarly as above
    $uid = optional($request->user())->id
        ?? (int) ($request->session()->get('user_id') ?? 0)
        ?? 0;
    if (!$uid) {
        $uid = (int) ($request->header('X-Session-User') ?? $request->query('user_id', 0));
    }

    $booking = GroomerBooking::where('customer_type', 'online')
        ->where('customer_id', $uid)
        ->where('id', $id)
        ->first();

    if (!$booking) {
        return response()->json(['error' => 'Booking not found'], 404);
    }

    $response = [];

    // Doctor/Employee
    $response['doctorName'] = $booking->groomer_employees_id == 0
        ? 'Not Assigned'
        : GroomerEmployee::where('id', $booking->groomer_employees_id)->first()?->name;

    // Clinic
    $response['clinic'] = GroomerProfile::where('user_id', $booking->user_id)->first()?->name;

    // Date
    $response['date'] = $booking->date;
    $response['time'] = $booking->start_time . ' - ' . $booking->end_time;

    // Owner & Pet details
    if ($booking->customer_type == "Groomer" || $booking->customer_type == "groomer") {
        $GroomerClientPet = GroomerClientPet::where('id', $booking->customer_pet_id)->first();
        $cust = GroomerClient::where('id', $booking->customer_id)->first();

        $response['ownerName']   = $cust?->name;
        $response['ownerMobile'] = $cust?->phone;
        $response['petName']     = $GroomerClientPet?->name;
        $response['petBreed']    = $GroomerClientPet?->type . ' - ' . $GroomerClientPet?->breed;
    } else {
        $cust = UserProfile::where('user_id', $booking->customer_id)->first();
        $pett = UserPet::where('user_id', $booking->customer_id)
            ->where('id', $booking->customer_pet_id)
            ->first();

        $response['petName']     = $pett?->name ?? '---';
        $response['petBreed']    = $pett ? $pett->type . ' - ' . $pett->breed : '---';
        $response['ownerName']   = $cust?->name ?? '---';
        $response['ownerMobile'] = $cust?->user?->mobile;
    }

    // Status + Tab
    $response['status'] = $booking->status;
    switch ($booking->status) {
        case 'completed':
            $response['tab'] = 'Completed';
            break;
        case 'Rejected':
            $response['tab'] = 'Cancelled';
            break;
        default:
            $response['tab'] = 'Upcoming';
            break;
    }
$response['rating']=UserRating::where('servicer_id', $booking->user_id)->avg('rating');

    // Check if booking has a video call service
    $is_videoCall = false;
    foreach (json_decode($booking->services) as $ddd) {
        $service = GroomerService::where('id', $ddd->service_id)->first();
        if ($service && $service->main_service == "video_call") {
            $is_videoCall = true;
        }
    }
    $response['is_videoCall'] = $is_videoCall;

    // Booking ID
    $response['id'] = $booking->id;

    return response()->json(['data' => $response]);
}

    public function profile_update(Request $request)
{
    $request->validate([
        'name'         => 'required|string|max:255',
        'address'      => 'nullable|string|max:255',
        'city'         => 'nullable|string|max:100',
        'pincode'      => 'nullable|string|max:20',
         'profile_pic'  => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    ]);

    $user = $request->user(); // Get authenticated user from request

    $profileData = [
        'name'         => $request->name,
        'address'      => $request->address,
        'city'         => $request->city,
        'pincode'      => $request->pincode,
     ];

    if ($request->hasFile('profile_pic')) {
        $image     = $request->file('profile_pic');
        $imageName = 'profile_' . $user->id . '_' . time() . '.' . $image->getClientOriginalExtension();
        $path      = public_path('profile_pics');

        // Create the directory if it doesn't exist
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $image->move($path, $imageName);

        // Save relative path like "profile_pics/filename.jpg"
        $profileData['profile_pic_link'] = 'profile_pics/' . $imageName;
    }

    $profile = UserProfile::updateOrCreate(
        ['user_id' => $user->id],
        $profileData
    );
    $profileData=$profile->toArray();
   if ($profile->profile_pic_link) {
        $profileData['profile_pic_link'] = url($profile->profile_pic_link);
    }
    return response()->json([
        'message' => 'Profile updated successfully.',
        'profile' => $profileData
    ]);
}

    public function updatePhone(Request $request)
    {
        $payload = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'phone' => 'required|string|max:20',
        ]);

        $user = User::find($payload['user_id']);
        $user->phone = $payload['phone'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Phone number updated successfully.',
            'user_id' => $user->id,
            'phone' => $user->phone,
        ]);
    }
public function add_pet(Request $request)
{
    $request->validate([
        'name'        => 'required|string|max:255',
        'type'        => 'required|string|max:100',
        'breed'       => 'nullable|string|max:100',
        'dob'         => 'nullable|date',
        'gender'      => 'nullable|string|in:male,female,other',
        'pet_pic'     => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        'medical_history' => 'required|string',
'vaccination_log'=>'required|string'
    ]);

    $user = $request->user();

    $petData = [
        'user_id' => $user->id,
        'name'    => $request->name,
        'type'    => $request->type,
        'breed'   => $request->breed,
        'dob'     => $request->dob,
        'gender'  => $request->gender,
        'medical_history'  => $request->medical_history,
        'vaccination_log'  => $request->vaccination_log,
    ];

    if ($request->hasFile('pet_pic')) {
        $image     = $request->file('pet_pic');
        $imageName = 'pet_' . $user->id . '_' . time() . '.' . $image->getClientOriginalExtension();
        $path      = public_path('pet_pics');

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $image->move($path, $imageName);

        $petData['pic_link'] = 'pet_pics/' . $imageName;
    }

    // Assuming you have a Pet model
    $pet = UserPet::create($petData);

    $petData = $pet->toArray();
    if (isset($pet->pet_pic_link)) {
        $petData['pic_link'] = url($pet->pet_pic_link);
    }

    return response()->json([
        'message' => 'Pet added successfully.',
        'pet'     => $petData
    ]);
}
public function my_pets(Request $request){
    $pets = UserPet::where('user_id',$request->user()->id)->get()->map(function($d){
return [
'id'=>$d->id,
'petName'=>$d->name,
'breed'=>$d->breed,
'dateOfBirth'=>$d->dob,
'gender'=>$d->gender,
'petPicture'=>$d->pic_link!=""? url($d->pic_link):'https://placehold.co/50x50?text=Pet',
'medicalHistory'=>json_decode($d->medical_history,true),
'vaccinationLog'=>json_decode($d->vaccination_log,true),
];
// $arr = $d->toArray();
// $arr['pic_link']=$d->pic_link!=""? url($d->pic_link):'https://placehold.co/50x50?text=Pet';
// return $arr;
    });
    return response()->json(['data'=>$pets]);
}
public function pet_profile(Request $request, $id)
{
    $pet = UserPet::where('user_id', $request->user()->id)->find($id);

    if (!$pet) {
        return response()->json([
            'message' => 'Pet not found.',
        ], 404);
    }

    $petData = $pet->toArray();
    if ($pet->pic_link) {
        $petData['pic_link'] = url($pet->pic_link);
    }else {
        $petData['pic_link'] = 'https://placehold.co/50x50?text=Pet';
    }
     $petData['medical_history'] = json_decode($pet->medical_history, true);
    $petData['vaccination_log'] = json_decode($pet->vaccination_log, true);
    return response()->json([
        'message' => 'Pet profile fetched successfully.',
        'pet'     => $petData
    ]);
} 
public function pet_update(Request $request, $id)
{
    $request->validate([
        'name'        => 'required|string|max:255',
        'type'        => 'required|string|max:100',
        'breed'       => 'nullable|string|max:100',
        'dob'         => 'nullable|date',
        'gender'      => 'nullable|string|in:male,female,other',
        'pet_pic'     => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        'medical_history' => 'required|string',
        'vaccination_log' => 'required|string'
    ]);

    $user = $request->user();

    $pet = UserPet::where('user_id', $user->id)->find($id);

    if (!$pet) {
        return response()->json([
            'message' => 'Pet not found.',
        ], 404);
    }

    $pet->name = $request->name;
    $pet->type = $request->type;
    $pet->breed = $request->breed;
    $pet->dob = $request->dob;
    $pet->gender = $request->gender;
    $pet->medical_history = $request->medical_history;
    $pet->vaccination_log = $request->vaccination_log;

    if ($request->hasFile('pet_pic')) {
        $image     = $request->file('pet_pic');
        $imageName = 'pet_' . $user->id . '_' . time() . '.' . $image->getClientOriginalExtension();
        $path      = public_path('pet_pics');

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $image->move($path, $imageName);

        $pet->pic_link = 'pet_pics/' . $imageName;
    }

    $pet->save();

    $petData = $pet->toArray();
    if ($pet->pic_link) {
        $petData['pic_link'] = url($pet->pic_link);
    } else {
        $petData['pic_link'] = 'https://placehold.co/50x50?text=Pet';
    }
    $petData['medical_history'] = json_decode($pet->medical_history, true);
    $petData['vaccination_log'] = json_decode($pet->vaccination_log, true);

    return response()->json([
        'message' => 'Pet updated successfully.',
        'pet'     => $petData
    ]);
} 

    /**
     * PUT /api/pets/{id}/extras
     * Stores extra pet details (weight, temprature, vaccination status/date).
     */
    public function petExtrasUpdate(Request $request, int $id)
    {
        $data = $request->validate([
            'weight' => 'sometimes|numeric',
            'temprature' => 'sometimes|numeric',
            'vaccenated_yes_no' => 'sometimes|boolean',
            'last_vaccenated_date' => 'sometimes|date',
        ]);

        $pet = UserPet::find($id);
        if (!$pet) {
            return response()->json(['message' => 'Pet not found'], 404);
        }

        foreach ($data as $key => $value) {
            $pet->{$key} = $value;
        }
        $pet->save();

        return response()->json([
            'message' => 'Pet details updated',
            'pet' => [
                'id' => $pet->id,
                'weight' => $pet->weight,
                'temprature' => $pet->temprature,
                'vaccenated_yes_no' => (bool) $pet->vaccenated_yes_no,
                'last_vaccenated_date' => $pet->last_vaccenated_date,
            ],
        ]);
    }
}

