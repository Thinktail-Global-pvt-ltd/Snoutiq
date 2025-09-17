<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\File;


use Illuminate\Support\Facades\DB;

use App\Models\ChatRoom;


use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    
    public function describePetImage()
    {
        // Gemini API key (direct use)
        $apiKey = "AIzaSyALZDZm-pEK3mtcK9PG9ftz6xyGemEHQ3k";

        // Static image ka path
        $imagePath = public_path('pet_pics/pet_1_1753728813.png');

        if (!file_exists($imagePath)) {
            return response()->json(['error' => 'Image not found at '.$imagePath], 404);
        }

        // Image ko base64 encode karna
        $imageData = base64_encode(file_get_contents($imagePath));

        // Gemini API call
        $response = Http::withHeaders([
            'Content-Type'   => 'application/json',
            'X-goog-api-key' => $apiKey,
        ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent", [
            'contents' => [[
                'parts' => [
                    [
                        'inline_data' => [
                            'mime_type' => 'image/png',
                            'data' => $imageData,
                        ]
                    ],
                    [
                        'text' => 'Describe this pet image in detail: breed, color, facial expression, mood, and any other details you can observe.'
                    ]
                ]
            ]]
        ]);

        if (!$response->successful()) {
            return response()->json([
                'error' => 'Gemini API call failed',
                'details' => $response->json()
            ], 500);
        }

        $summary = $response->json('candidates.0.content.parts.0.text');

        return response()->json([
            'message' => 'Pet image description generated successfully',
            'summary' => $summary,
        ]);
    }

    // -------- Helper: Bearer se user nikaalo (SHA-256 hash match) ----------
    private function userFromBearer(Request $request): ?User
    {
        $auth = $request->header('Authorization');
        if (!$auth || !preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) return null;
        $hash = hash('sha256', $m[1]);
        return User::where('api_token_hash', $hash)->first();
    }

    // -------------------------- OTP SEND -----------------------------------
    // public function send_otp(Request $request){
    //     $request->validate([
    //         'type'  => 'required|in:email,phone',
    //         'value' => 'required|string'
    //     ]);

       
    //     $type  = $request->input('type');
    //     $value = $request->input('value');
    //     $otp   = rand(1000, 9999);
    //     $token = Str::uuid(); // track request
    //     $expiresAt = Carbon::now()->addMinutes(10); // SMS me 10 mins likha hai

    //     if ($request->input("unique") === "yes") {
    //         $user = User::where($type, $value)->first();
    //         if ($user) {
    //             return response()->json([
    //                 'message' => ucfirst($type).' is already registered with us',
    //             ], 401);
    //         }
    //     }

    //     if ($type === "email") {
    //         Mail::to($value)->send(new OtpMail($otp));
    //     } else {
    //         $message = "Dear user, your verification code is ".$otp.". valid for 10 minutes. WEBSPOOL";
    //         // NOTE: credentials ko .env me le jao (security best practice)
    //         $ch = curl_init('https://sms.bulksmslab.com/SMSApi/send?');
    //         curl_setopt($ch, CURLOPT_POST, 1);
    //         curl_setopt($ch, CURLOPT_POSTFIELDS, "userid=webspool&password=NavRSc7x&mobile=".$value."&msg=$message&senderid=WPLHRY&msgType=text&dltEntityId=1501578870000012717&dltTemplateId=1507162701210810645&duplicatecheck=true&output=json&sendMethod=quick");
    //         curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    //         $data = curl_exec($ch);
    //     }

    //     Otp::create([
    //         'token'      => $token,
    //         'type'       => $type,
    //         'value'      => $value,
    //         'otp'        => $otp,
    //         'expires_at' => $expiresAt,
    //         'is_verified'=> 0,
    //     ]);

    //     return response()->json([
    //         'message' => 'OTP sent successfully',
    //         'otp'     => $type==="phone" ? $otp : 'hidden', // ⚠️ prod me mat bhejna
    //         'token'   => $token
    //     ]);
    // }


    public function send_otp(Request $request)
{
    try {
        $request->validate([
            'value' => 'required|email'
        ]);

        $value     = $request->input('value');   // email address
        $otp       = rand(1000, 9999);
        $token     = Str::uuid(); // track request
        $expiresAt = Carbon::now()->addMinutes(10);

        if ($request->input("unique") === "yes") {
            $user = User::where('email', $value)->first();
            if ($user) {
                return response()->json([
                    'message' => 'Email is already registered with us',
                ], 401);
            }
        }

        // Send OTP via Email
        Mail::to($value)->send(new OtpMail($otp));

        // Save OTP record in DB
        Otp::create([
            'token'       => $token,
            'type'        => 'email',
            'value'       => $value,
            'otp'         => $otp,
            'expires_at'  => $expiresAt,
            'is_verified' => 0,
        ]);

        return response()->json([
            'message' => 'OTP sent successfully',
            'otp'     => 'hidden', // ⚠️ Debug ke liye rakh sakte ho, prod me hata do
            'token'   => $token
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Something went wrong while sending OTP',
            'error'   => $e->getMessage(), // ⚠️ Prod me isko hata dena, sirf dev me rakhna
        ], 500);
    }
}

//     public function send_otp(Request $request)
// {
//     $request->validate([
//         'value' => 'required|email'
//     ]);

//     $value  = $request->input('value');   // email address
//     $otp    = rand(1000, 9999);
//     $token  = Str::uuid(); // track request
//     $expiresAt = Carbon::now()->addMinutes(10);

//     if ($request->input("unique") === "yes") {
//         $user = User::where('email', $value)->first();
//         if ($user) {
//             return response()->json([
//                 'message' => 'Email is already registered with us',
//             ], 401);
//         }
//     }

//     // Send OTP via Email
//     Mail::to($value)->send(new OtpMail($otp));

//     // Save OTP record in DB
//     Otp::create([
//         'token'      => $token,
//         'type'       => 'email',
//         'value'      => $value,
//         'otp'        => $otp,
//         'expires_at' => $expiresAt,
//         'is_verified'=> 0,
//     ]);

//     return response()->json([
//         'message' => 'OTP sent successfully',
//         'otp'     => 'hidden', // ⚠️ debug ke liye show kar sakte ho, prod me hata do
//         'token'   => $token
//     ]);
// }


    // -------------------------- OTP VERIFY ---------------------------------
    public function verify_otp(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
          //'value' => 'required|string',
            'otp'   => 'required',
        ]);

        $otpEntry = Otp::where('token', $request->token)
          
            ->where('otp', $request->otp)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpEntry) {
            return response()->json(['error' => 'Invalid or expired OTP'], 401);
        }

        if ($otpEntry->is_verified) {
            return response()->json(['message' => 'OTP already verified'], 200);
        }

        $otpEntry->update(['is_verified' => 1]);
        return response()->json(['message' => 'OTP verified successfully']);
    }

    // --------------------------- REGISTER -----------------------------------

public function createInitialRegistration(Request $request)
{
    // ✅ check agar email ya phone already exist hai
    $emailExists = DB::table('users')
        ->where('email', $request->email)
        ->exists();

    $mobileExists = DB::table('users')
        ->where('phone', $request->mobileNumber)
        ->exists();

    if ($emailExists || $mobileExists) {
        return response()->json([
            'status'  => 'error',
            'message' => 'enter unique mobile or email'
        ], 422);
    }

    // ✅ sirf basic fields save karo
    $user = User::create([
        'name'     => $request->fullName,
        'email'    => $request->email,
        'phone'    => $request->mobileNumber,
        'password' => null, // abhi blank rakho
    ]);

    return response()->json([
        'message' => 'Initial registration created',
        'user_id' => $user->id,   // ye id next step me use hogi
        'user'    => $user,
    ], 201);
}
public function register(Request $request)
{
    // ✅ user find karo id se jo initial step me aayi thi
    $user = User::find($request->user_id);

    if (!$user) {
        return response()->json([
            'status'  => 'error',
            'message' => 'User not found for update'
        ], 404);
    }

    $doc1Path = null;
    $doc2Path = null;
    $summaryText = null;

    // ✅ ensure directory exists
    $uploadPath = public_path('uploads/pet_docs');
    if (!File::exists($uploadPath)) {
        File::makeDirectory($uploadPath, 0777, true, true);
    }

    // ✅ file upload
    if ($request->hasFile('pet_doc1')) {
        $doc1Name = time().'_'.uniqid().'_'.$request->file('pet_doc1')->getClientOriginalName();
        File::put($uploadPath.'/'.$doc1Name, file_get_contents($request->file('pet_doc1')->getRealPath()));
        $doc1Path = $uploadPath.'/'.$doc1Name;
    }

    if ($request->hasFile('pet_doc2')) {
        $doc2Name = time().'_'.uniqid().'_'.$request->file('pet_doc2')->getClientOriginalName();
        File::put($uploadPath.'/'.$doc2Name, file_get_contents($request->file('pet_doc2')->getRealPath()));
        $doc2Path = $uploadPath.'/'.$doc2Name;
    }

    // ✅ Gemini summary
    if ($doc1Path || $doc2Path) {
        $imagePath = $doc1Path ?? $doc2Path;
        $summaryText = $this->describePetImageDynamic($imagePath);
    }

    // ✅ Update user with final details
    $user->update([
        'name'        => $request->fullName,
        'email'       => $request->email,
        'phone'       => $request->mobileNumber,
        'password'    => $request->password, // ⚠ plain (unsafe in prod)
        'pet_name'    => $request->pet_name,
        'pet_gender'  => $request->pet_gender,
        'pet_age'     => $request->pet_age,
        'pet_doc1'    => $doc1Path,
        'pet_doc2'    => $doc2Path,
        'summary'     => $summaryText,
        'google_token'=> $request->google_token,
        'breed'       => $request->breed,
        'latitude'    => $request->latitude,
        'longitude'   => $request->longitude,
    ]);

    // ✅ plain token generate and save
    $plainToken = bin2hex(random_bytes(32));
    $user->api_token_hash = $plainToken;
    $user->save();

    return response()->json([
        'message'    => 'User registered successfully (updated)',
        'user'       => $user,
        'token'      => $plainToken,
        'token_type' => 'Bearer',
    ], 200);
}


public function register_latest_backup(Request $request)
{
    
    // check email
    $emailExists = DB::table('users')
        ->where('email', $request->email)
        ->exists();

    // check mobile (phone column)
    $mobileExists = DB::table('users')
        ->where('phone', $request->mobileNumber)
        ->exists();

    if ($emailExists || $mobileExists) {
        return response()->json([
            'status'  => 'error',
            'message' => 'enter unique mobile or email'
        ], 422);
    }




    $doc1Path = null;
    $doc2Path = null;
    $summaryText = null;

    // ✅ ensure directory exists
    $uploadPath = public_path('uploads/pet_docs');
    if (!File::exists($uploadPath)) {
        File::makeDirectory($uploadPath, 0777, true, true);
    }

    // ✅ file upload using File::put
    if ($request->hasFile('pet_doc1')) {
        $doc1Name = time().'_'.uniqid().'_'.$request->file('pet_doc1')->getClientOriginalName();
        $fileContent = file_get_contents($request->file('pet_doc1')->getRealPath());
        File::put($uploadPath.'/'.$doc1Name, $fileContent);
        $doc1Path = $uploadPath.'/'.$doc1Name;
    }

    if ($request->hasFile('pet_doc2')) {
        $doc2Name = time().'_'.uniqid().'_'.$request->file('pet_doc2')->getClientOriginalName();
        $fileContent = file_get_contents($request->file('pet_doc2')->getRealPath());
        File::put($uploadPath.'/'.$doc2Name, $fileContent);
        $doc2Path = $uploadPath.'/'.$doc2Name;
    }

    // ✅ Gemini call agar koi image upload hui hai
    if ($doc1Path || $doc2Path) {
        $imagePath = $doc1Path ?? $doc2Path; // agar dono hain to pehle doc1 lo
        $summaryText = $this->describePetImageDynamic($imagePath);
    }

    // ✅ user create (password bina hash)
    $user = User::create([
        'name'        => $request->fullName,
        'email'       => $request->email,
        'phone'       => $request->mobileNumber,
        'password'    => $request->password, // ⚠ plain text (unsafe in prod)
        'pet_name'    => $request->pet_name,
        'pet_gender'  => $request->pet_gender,
        'pet_age'     => $request->pet_age,
        'pet_doc1'    => $doc1Path,
        'pet_doc2'    => $doc2Path,
        'summary'     => $summaryText,
        'google_token'     => $request->google_token,
            'breed'       => $request->breed,        // ✅ new
    'latitude'    => $request->latitude,     // ✅ new
    'longitude'   => $request->longitude,  
          // Gemini se jo summary aayi usko save karo
    ]);

    // ✅ plain token generate and save
    $plainToken = bin2hex(random_bytes(32));
    $user->api_token_hash = $plainToken;
    $user->save();

    return response()->json([
        'message'    => 'User registered successfully',
        'user'       => $user,
        'token'      => $plainToken,
        'token_type' => 'Bearer',
    ], 201);
}

/**
 * Gemini se image ka description nikalna (dynamic image path ke liye)
 */
private function describePetImageDynamic($imagePath)
{
    $apiKey = "AIzaSyALZDZm-pEK3mtcK9PG9ftz6xyGemEHQ3k";

    if (!file_exists($imagePath)) {
        return null;
    }

    $imageData = base64_encode(file_get_contents($imagePath));

    $response = Http::withHeaders([
        'Content-Type'   => 'application/json',
        'X-goog-api-key' => $apiKey,
    ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent", [
        'contents' => [[
            'parts' => [
                [
                    'inline_data' => [
                        'mime_type' => mime_content_type($imagePath),
                        'data' => $imageData,
                    ]
                ],
                [
                    'text' => 'Describe this pet image in detail: breed, appearance, color, mood, and context.'
                ]
            ]
        ]]
    ]);

    if (!$response->successful()) {
        return null;
    }

    return $response->json('candidates.0.content.parts.0.text');
}





// public function register(Request $request)
// {
//     // $request->validate([
//     //     'fullName'        => 'required|string|max:255',
//     //     'email'           => 'required|email|max:255|unique:users,email',
//     //     'mobileNumber'    => 'required|string|max:20|unique:users,phone',

//     //     'pet_doc1'        => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
//     //     'pet_doc2'        => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',

//     //     'password'        => 'required|string|min:6',
//     //     'confirmPassword' => 'required|same:password',
//     // ]);

//     $doc1Path = null;
//     $doc2Path = null;

//     // ✅ ensure directory exists
//     $uploadPath = public_path('uploads/pet_docs');
//     if (!File::exists($uploadPath)) {
//         File::makeDirectory($uploadPath, 0777, true, true);
//     }

//     // ✅ file upload using File::put
//     if ($request->hasFile('pet_doc1')) {
//         $doc1Name = time().'_'.uniqid().'_'.$request->file('pet_doc1')->getClientOriginalName();
//         $fileContent = file_get_contents($request->file('pet_doc1')->getRealPath());
//         File::put($uploadPath.'/'.$doc1Name, $fileContent);
//         $doc1Path = 'uploads/pet_docs/'.$doc1Name;
//     }

//     if ($request->hasFile('pet_doc2')) {
//         $doc2Name = time().'_'.uniqid().'_'.$request->file('pet_doc2')->getClientOriginalName();
//         $fileContent = file_get_contents($request->file('pet_doc2')->getRealPath());
//         File::put($uploadPath.'/'.$doc2Name, $fileContent);
//         $doc2Path = 'uploads/pet_docs/'.$doc2Name;
//     }

//     // ✅ user create (password bina hash)
//     $user = User::create([
//         'name'     => $request->fullName,
//         'email'    => $request->email,
//         'phone'    => $request->mobileNumber,
//         'password' => $request->password, // plain text (⚠️ unsafe in production) 
//         "pet_name"=> $request->pet_name,
//         "pet_gender"=> $request->pet_gender,
//         "pet_age"=> $request->pet_age,
//         'pet_doc1' => $doc1Path,
//         'pet_doc2' => $doc2Path,
//     ]);

//     // ✅ plain token generate and save
//     $plainToken = bin2hex(random_bytes(32));
//     $user->api_token_hash = $plainToken;
//     $user->save();

//     return response()->json([
//         'message'    => 'User registered successfully',
//         'user'       => $user,
//         'token'      => $plainToken,
//         'token_type' => 'Bearer',
//     ], 201);
// }

// public function login(Request $request)
// {

//     $request->validate([
//         'login'    => 'required|string', // email or phone
//         'password' => 'required|string',
//     ]);

//     // try to find user by email or phone
//     $user = User::where('email', $request->login)
//                 ->orWhere('phone', $request->login)
//                 ->first();
   
//     if (!$user) {
//         return response()->json(['message' => 'User not found'], 404);
//     }

//     // password check (since plain text)
//     if ($user->password !== $request->password) {
//         return response()->json(['message' => 'Invalid credentials'], 401);
//     }

//     // regenerate plain token
//     $plainToken = bin2hex(random_bytes(32));
//     $user->api_token_hash = $plainToken;
//     $user->save();

//     return response()->json([
//         'message'    => 'Login successful',
//         'user'       => $user,
//         'token'      => $plainToken,
//         'token_type' => 'Bearer',
//     ], 200);
// }


public function login(Request $request)
{
    try {
        $email = $request->input('email') ?? $request->input('login');
        $role  = $request->input('role'); // 👈 role pick karo
        $roomTitle = $request->input('room_title');

        if (empty($email) || empty($role)) {
            return response()->json(['message' => 'Email or role missing'], 422);
        }

        $room = null;
        $plainToken = null;

        if ($role === 'pet') {
            // 🔹 Search in users table
            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            DB::transaction(function () use (&$plainToken, &$room, $user, $roomTitle) {
                $plainToken = bin2hex(random_bytes(32));
                $user->api_token_hash = $plainToken;
                $user->save();

                $room = ChatRoom::create([
                    'user_id'         => $user->id,
                    'chat_room_token' => 'room_' . Str::uuid()->toString(),
                    'name'            => $roomTitle ?? ('New chat - ' . now()->format('d M Y H:i')),
                ]);
            });

            // ✅ Password exclude karo
            $userData = $user->toArray();
            unset($userData['password']);
            $userData['role'] = 'pet';

            return response()->json([
                'message'    => 'Login successful',
                'role'       => 'pet',
                'email'      => $user->email,
                'token'      => $plainToken,
                'token_type' => 'Bearer',
                'chat_room'  => [
                    'id'    => $room->id,
                    'token' => $room->chat_room_token,
                    'name'  => $room->name,
                ],
                'user'       => $userData,
            ], 200);

        } elseif ($role === 'vet') {
            $tempVet = DB::table('vet_registerations_temp')
                ->where('email', $email)
                ->first();

            if (!$tempVet) {
                return response()->json(['message' => 'Vet not found'], 404);
            }

            DB::transaction(function () use (&$plainToken, &$room, $tempVet, $roomTitle) {
                $plainToken = bin2hex(random_bytes(32));

                // 🟢 Update token for vet
                DB::table('vet_registerations_temp')
                    ->where('id', $tempVet->id)
                    ->update(['api_token_hash' => $plainToken]);

                $room = ChatRoom::create([
                    'user_id'         => $tempVet->id,
                    'chat_room_token' => 'room_' . Str::uuid()->toString(),
                    'name'            => $roomTitle ?? ('New chat - ' . now()->format('d M Y H:i')),
                ]);
            });

            // ✅ Password exclude karo
            $vetData = (array) $tempVet;
            unset($vetData['password']);
            $vetData['role'] = 'vet';

            return response()->json([
                'message'    => 'Login successful',
                'role'       => 'vet',
                'email'      => $tempVet->email,
                'token'      => $plainToken,
                'token_type' => 'Bearer',
                'chat_room'  => [
                    'id'    => $room->id,
                    'token' => $room->chat_room_token,
                    'name'  => $room->name,
                ],
                'user'        => $vetData,
            ], 200);
        }

        return response()->json(['message' => 'Invalid role'], 400);

    } catch (\Throwable $e) {
        // ⚠️ Exception handle karo
        return response()->json([
            'success' => false,
            'message' => 'Login failed. Please try again later.',
            'error'   => $e->getMessage(), // 👉 debug ke liye
        ], 500);
    }
}



public function login_bkp(Request $request)
{
    $email = $request->input('email') ?? $request->input('login');
    $role  = $request->input('role'); // 👈 role pick karo
    $roomTitle = $request->input('room_title');

    if (empty($email) || empty($role)) {
        return response()->json(['message' => 'Email or role missing'], 422);
    }

    $room = null;
    $plainToken = null;

    if ($role === 'pet') {
        // 🔹 Search in users table
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        DB::transaction(function () use (&$plainToken, &$room, $user, $roomTitle) {
            $plainToken = bin2hex(random_bytes(32));
            $user->api_token_hash = $plainToken;
            $user->save();

            $room = ChatRoom::create([
                'user_id'         => $user->id,
                'chat_room_token' => 'room_' . Str::uuid()->toString(),
                'name'            => $roomTitle ?? ('New chat - ' . now()->format('d M Y H:i')),
            ]);
        });

        // ✅ Password exclude karo
        $userData = $user->toArray();
        unset($userData['password']);
        $userData['role'] = 'pet';

        return response()->json([
            'message'    => 'Login successful',
            'role'       => 'pet',
            'email'      => $user->email,
            'token'      => $plainToken,
            'token_type' => 'Bearer',
            'chat_room'  => [
                'id'    => $room->id,
                'token' => $room->chat_room_token,
                'name'  => $room->name,
            ],
            'user'       => $userData,
        ], 200);

    } elseif ($role === 'vet') {
        // 🔹 Search in vet_registerations_temp
        $tempVet = DB::table('vet_registerations_temp')
            ->where('email', $email)
            ->first();

        if (!$tempVet) {
            return response()->json(['message' => 'Vet not found'], 404);
        }

        // ✅ Password exclude karo
        $vetData = (array) $tempVet;
        unset($vetData['password']);
        $vetData['role'] = 'vet';

        return response()->json([
            'message' => 'Login successful',
            'role'    => 'vet',
            'email'   => $tempVet->email,
            'vet'     => $vetData,
        ], 200);
    }

    return response()->json(['message' => 'Invalid role'], 400);
}










//     public function googleLogin(Request $request)
// {
//     $request->validate([
//         'email'        => 'required|email',
//         'google_token' => 'required|string',
//     ]);

//     try {
//         // ✅ Check user with email & google_token
//         $user = User::where('email', $request->email)
//                     ->where('google_token', $request->google_token)
//                     ->first();

//         if (!$user) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Invalid credentials',
//             ], 401);
//         }

//         // ✅ If found → success
//         return response()->json([
//             'success' => true,
//             'message' => 'Login success',
//             'user'    => $user,  // full row
//         ]);

//     } catch (\Exception $e) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Google login failed',
//             'error'   => $e->getMessage(),
//         ], 500);
//     }
// }


public function googleLogin(Request $request)
{
    $request->validate([
        'email'        => 'required|email',
        'google_token' => 'required|string',
       // 'room_title'   => 'nullable|string', // optional
    ]);

    try {
        // ✅ User check
        $user = User::where('email', $request->email)
                    ->where('google_token', $request->google_token)
                    ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        $plainToken = null;
        $room = null;

        DB::transaction(function () use (&$plainToken, &$room, $user, $request) {
            // ✅ API token regenerate
            $plainToken = bin2hex(random_bytes(32));
            $user->api_token_hash = $plainToken; // (prod: hash store karein)
            $user->save();

            // ✅ Create NEW chat room on login
            $room = ChatRoom::create([
                'user_id'         => $user->id,
                'chat_room_token' => 'room_' . Str::uuid()->toString(),
                'name'            => $request->room_title ?? ('New chat - ' . now()->format('d M Y H:i')),
            ]);
        });

        return response()->json([
            'success'    => true,
            'message'    => 'Login success',
            'user'       => $user,
            'token'      => $plainToken,
            'token_type' => 'Bearer',
            'chat_room'  => [
                'id'    => $room->id,
                'token' => $room->chat_room_token,
                'name'  => $room->name,
            ],
            'note'       => 'Use this chat_room_token for all messages in this new room.',
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Google login failed',
            'error'   => $e->getMessage(),
        ], 500);
    }
}




    // --------------------------- SESSION CHECK ------------------------------
    public function me(Request $request)
    {
        $user = $this->userFromBearer($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);
        return response()->json($user);
    }

    // ------------------------------ LOGOUT ---------------------------------
    public function logout(Request $request)
    {
        $user = $this->userFromBearer($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $user->api_token_hash = null; // invalidate
        $user->save();

        return response()->json(['message' => 'Logged out']);
    }

    

}
