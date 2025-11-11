<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;


use Illuminate\Support\Facades\DB;

use Illuminate\Validation\Rule;

use App\Services\WhatsAppService;

use App\Models\ChatRoom;
use App\Models\Pet;
use App\Models\Doctor;


use Illuminate\Support\Facades\Http;
use App\Support\GeminiConfig;

class AuthController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsApp)
    {
    }
    
    public function describePetImage()
    {
        $imagePath = public_path('pet_pics/pet_1_1753728813.png');

        if (!file_exists($imagePath)) {
            return response()->json(['error' => 'Image not found at '.$imagePath], 404);
        }

        $summary = $this->summarizeImageFile($imagePath);

        if (!$summary) {
            return response()->json([
                'error' => 'Unable to generate summary for the provided image.',
            ], 502);
        }

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

    private function shouldCheckUniqueness(Request $request): bool
    {
        $flag = $request->input('unique');

        if (is_bool($flag)) {
            return $flag;
        }

        if (is_numeric($flag)) {
            return (bool) $flag;
        }

        if (is_string($flag)) {
            return in_array(strtolower($flag), ['1', 'true', 'yes'], true);
        }

        return false;
    }

    private function normalizePhone(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw);

        if (!$digits) {
            return null;
        }

        if (str_starts_with($digits, '91') && strlen($digits) >= 12) {
            return substr($digits, 0, 12);
        }

        if (strlen($digits) === 10) {
            return '91' . $digits;
        }

        return $digits;
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
                'type' => ['nullable', Rule::in(['email', 'whatsapp'])],
                'value' => 'required|string',
            ]);

            $type = $request->input('type', 'email');
            $rawValue = trim((string) $request->input('value'));
            $otp = random_int(1000, 9999);
            $token = (string) Str::uuid();
            $expiresAt = Carbon::now()->addMinutes(10);
            $normalizedPhone = null;

            if ($type === 'email') {
                $request->merge(['value' => $rawValue]);
                $request->validate(['value' => 'email']);
            } else {
                $normalizedPhone = $this->normalizePhone($rawValue);

                if (! $normalizedPhone) {
                    return response()->json([
                        'message' => 'Invalid phone number for WhatsApp verification',
                    ], 422);
                }

                if (! $this->whatsApp->isConfigured()) {
                    return response()->json([
                        'message' => 'WhatsApp channel is temporarily unavailable',
                    ], 503);
                }
            }

            if ($this->shouldCheckUniqueness($request)) {
                $column = $type === 'email' ? 'email' : 'phone';
                $valueToCheck = $type === 'email' ? $rawValue : $normalizedPhone;

                $exists = User::query()
                    ->where($column, $valueToCheck)
                    ->when($type === 'whatsapp' && $normalizedPhone !== $rawValue, function ($query) use ($column, $rawValue) {
                        $query->orWhere($column, $rawValue);
                    })
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'message' => ucfirst($column).' is already registered with us',
                    ], 401);
                }
            }

            if ($type === 'email') {
                Mail::to($rawValue)->send(new OtpMail($otp));
            } else {
                $message = "Your SnoutIQ verification code is {$otp}. It expires in 10 minutes.";
                $this->whatsApp->sendText($normalizedPhone, $message);
            }

            Otp::create([
                'token'       => $token,
                'type'        => $type,
                'value'       => $type === 'email' ? $rawValue : $normalizedPhone,
                'otp'         => $otp,
                'expires_at'  => $expiresAt,
                'is_verified' => 0,
            ]);

            return response()->json([
                'message' => 'OTP sent successfully',
                'channel' => $type,
                'otp'     => config('app.debug') ? $otp : 'hidden',
                'token'   => $token,
            ], 200);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Something went wrong while sending OTP',
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
    try {
        // ✅ check agar email ya phone already exist hai
        $emailExists = DB::table('users')
            ->where('email', $request->email)
            ->exists();

        // $mobileExists = DB::table('users')
        //     ->where('phone', $request->mobileNumber)
        //     ->exists();

        if ($emailExists ) {
            return response()->json([
                'status'  => 'error',
                'message' => 'enter unique mobile or email'
            ], 422);
        }

        // ✅ sirf basic fields save karo
        
        $user = User::create([
            'name'         => $request->fullName,
            'email'        => $request->email,
            //'phone'      => $request->mobileNumber, // agar phone chahiye toh uncomment karo
            'password'     => null, // abhi blank rakho
            'google_token' => $request->google_token,
         'latitude'    => $request->latitude,
        'longitude'   => $request->longitude,
        ]);


        return response()->json([
            'status'  => 'success',
            'message' => 'Initial registration created',
            'user_id' => $user->id,   // ye id next step me use hogi
            'user'    => $user,
        ], 201);

    } catch (\Exception $e) {
        // ⚠️ Agar koi error aaya toh usko catch karo
        return response()->json([
            'status'  => 'error',
            'message' => 'Something went wrong while creating initial registration',
            'error'   => $e->getMessage()
        ], 500);
    }
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

    $existingDoc1 = $user->pet_doc1;
    $existingDoc2 = $user->pet_doc2;
    $existingSummary = $user->summary;

    $doc1Path = null;
    $doc2Path = null;
    $summaryText = null;
    $doc1AbsolutePath = null;
    $doc2AbsolutePath = null;

    // ✅ persist uploads and keep both DB + absolute paths handy
    [$doc1Path, $doc1AbsolutePath] = $this->storePetDocument($request, 'pet_doc1');
    [$doc2Path, $doc2AbsolutePath] = $this->storePetDocument($request, 'pet_doc2');

    // ✅ fallback to previous uploads if new files not provided
    $doc1Path = $doc1Path ?? $existingDoc1;
    $doc2Path = $doc2Path ?? $existingDoc2;

    // ✅ Gemini summary only when fresh image arrives, else preserve old summary
    $imagePath = $doc1AbsolutePath ?? $doc2AbsolutePath;
    if ($imagePath) {
        $summaryText = $this->describePetImageDynamic($imagePath);
    }
    $summaryText = $summaryText ?? $existingSummary;

    $plainToken = bin2hex(random_bytes(32));

    try {
        $pet = DB::transaction(function () use ($user, $request, $doc1Path, $doc2Path, $summaryText, $plainToken) {
            // ✅ Update user with final details
            $user->fill([
                'pet_name'    => $request->pet_name,
                'pet_gender'  => $request->pet_gender,
                'pet_age'     => $request->pet_age,
                'pet_doc1'    => $doc1Path,
                'pet_doc2'    => $doc2Path,
                'summary'     => $summaryText,
                'breed'       => $request->breed,
            ]);

            $user->api_token_hash = $plainToken;
            $user->save();

            $petAttributes = [
                'name'       => $request->pet_name,
                'breed'      => $request->breed,
                'pet_age'    => $request->pet_age,
                'pet_gender' => $request->pet_gender,
                'pet_doc1'   => $doc1Path,
                'pet_doc2'   => $doc2Path,
            ];

            $existingPet = Pet::where('user_id', $user->id)->first();

            if ($existingPet) {
                $existingPet->fill($petAttributes);
                $existingPet->save();
                $pet = $existingPet;
            } else {
                $pet = Pet::create(array_merge(['user_id' => $user->id], $petAttributes));
            }

            return $pet;
        });
    } catch (\Throwable $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Unable to complete registration at this time',
        ], 500);
    }

    $user->refresh();

    return response()->json([
        'message'    => 'User registered successfully (updated)',
        'user'       => $user,
        'pet'        => $pet,
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
    $doc1AbsolutePath = null;
    $doc2AbsolutePath = null;

    [$doc1Path, $doc1AbsolutePath] = $this->storePetDocument($request, 'pet_doc1');
    [$doc2Path, $doc2AbsolutePath] = $this->storePetDocument($request, 'pet_doc2');

    // ✅ Gemini call agar koi image upload hui hai
    $imagePath = $doc1AbsolutePath ?? $doc2AbsolutePath; // agar dono hain to pehle doc1 lo
    if ($imagePath) {
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

    public function generatePetSummary(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = User::find($data['user_id']);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $docPaths = array_values(array_filter([
            $user->pet_doc1,
            $user->pet_doc2,
        ]));

        if (empty($docPaths)) {
            return response()->json([
                'success' => false,
                'message' => 'No pet documents found for this user.',
            ], 422);
        }

        $summaries = [];
        foreach ($docPaths as $path) {
            $summary = $this->describePetImageDynamic($path);
            if ($summary) {
                $summaries[] = trim($summary);
            }
        }

        if (empty($summaries)) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to generate summary from the uploaded images.',
            ], 502);
        }

        $finalSummary = trim(implode("\n\n", $summaries));
        $user->summary = $finalSummary;
        $user->save();

        return response()->json([
            'success' => true,
            'user_id' => $user->id,
            'summary' => $finalSummary,
            'sources' => $docPaths,
        ]);
    }

/**
 * Store uploaded pet document and return [relativePath, absolutePath]
 */
private function storePetDocument(Request $request, string $field): array
{
    if (!$request->hasFile($field)) {
        return [null, null];
    }

    $uploadPath = public_path('uploads/pet_docs');
    if (!File::exists($uploadPath)) {
        File::makeDirectory($uploadPath, 0777, true, true);
    }

    $file = $request->file($field);
    $docName = time().'_'.uniqid().'_'.$file->getClientOriginalName();
    $file->move($uploadPath, $docName);

    $relativePath = 'backend/uploads/pet_docs/'.$docName;

    return [$relativePath, $uploadPath.'/'.$docName];
}

/**
 * Gemini se image ka description nikalna (dynamic image path ke liye)
 */
private function describePetImageDynamic($imagePath)
{
    if (empty($imagePath)) {
        return null;
    }

    return $this->summarizeImageFile($imagePath);
}

private function summarizeImageFile(string $imagePath): ?string
{
    $resolvedPath = $this->resolveImagePath($imagePath);

    if (!$resolvedPath || !file_exists($resolvedPath)) {
        Log::warning('Gemini summary skipped: file not found', ['path' => $imagePath]);
        return null;
    }

    $mimeType = mime_content_type($resolvedPath) ?: null;
    if (!$mimeType || !str_starts_with($mimeType, 'image/')) {
        Log::info('Gemini summary skipped: unsupported mime type', [
            'path' => $imagePath,
            'mime' => $mimeType,
        ]);
        return null;
    }

    try {
        $imageData = base64_encode(file_get_contents($resolvedPath));
    } catch (\Throwable $e) {
        Log::error('Gemini summary: failed to read image', [
            'path'  => $resolvedPath,
            'error' => $e->getMessage(),
        ]);
        return null;
    }

    return $this->sendImageToGemini($imageData, $mimeType);
}

private function resolveImagePath(string $imagePath): ?string
{
    if (empty($imagePath)) {
        return null;
    }

    if (file_exists($imagePath)) {
        return $imagePath;
    }

    $trimmed = ltrim($imagePath, '/');
    $candidates = [
        public_path($trimmed),
        base_path($trimmed),
    ];

    if (str_starts_with($trimmed, 'backend/')) {
        $relative = substr($trimmed, strlen('backend/'));
        $candidates[] = public_path($relative);
        $candidates[] = base_path($relative);
    }

    foreach ($candidates as $candidate) {
        if ($candidate && file_exists($candidate)) {
            return $candidate;
        }
    }

    return null;
}

private function sendImageToGemini(string $imageData, string $mimeType): ?string
{
    $apiKey = GeminiConfig::apiKey();
    if (empty($apiKey)) {
        Log::error('Gemini summary aborted: API key missing');
        return null;
    }

    $models = array_values(array_unique(array_filter(array_merge(
        [GeminiConfig::chatModel()],
        GeminiConfig::summaryModels()
    ))));

    if (empty($models)) {
        $models = ['gemini-1.5-flash'];
    }

    foreach ($models as $model) {
        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            $model,
            urlencode($apiKey)
        );

        try {
            $response = Http::timeout(40)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'contents' => [[
                        'parts' => [
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $imageData,
                                ],
                            ],
                            [
                                'text' => 'Describe this pet image in detail: breed, appearance, color, mood, and context.'
                            ],
                        ],
                    ]],
                ]);
        } catch (\Throwable $e) {
            Log::error('Gemini summary request failed', [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);
            continue;
        }

        if ($response->successful()) {
            $text = $response->json('candidates.0.content.parts.0.text');
            if ($text) {
                return $text;
            }
        } else {
            Log::warning('Gemini summary failed', [
                'model'  => $model,
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);

            if ($response->status() !== 404) {
                break;
            }
        }
    }

    Log::error('Gemini summary exhausted models', ['models' => $models]);
    return null;
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
        $password = (string) $request->input('password', '');
        $roomTitle = $request->input('room_title');

        if (empty($email) || empty($role)) {
            return response()->json(['message' => 'Email or role missing'], 422);
        }

        $room = null;
        $plainToken = null;

        $adminEmail = strtolower(trim((string) config('admin.email', 'admin@snoutiq.com')));
        if ($adminEmail === '') {
            $adminEmail = 'admin@snoutiq.com';
        }

        $adminPassword = (string) config('admin.password', 'snoutiqvet');
        if ($adminPassword === '') {
            $adminPassword = 'snoutiqvet';
        }

        if ($role === 'vet' && $adminEmail && strtolower(trim((string) $email)) === $adminEmail) {
            if (!hash_equals($adminPassword, (string) $password)) {
                return response()->json([
                    'message' => 'Invalid admin credentials',
                ], 401);
            }

            $request->session()->put([
                'is_admin' => true,
                'admin_email' => $adminEmail,
                'role' => 'admin',
            ]);

            $request->session()->regenerate();

            return response()->json([
                'message' => 'Admin login successful',
                'role' => 'admin',
                'email' => $adminEmail,
                'redirect' => route('admin.dashboard'),
            ], 200);
        }

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

            $response = [
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
                'user_id'    => $user->id,
                'vet_id'     => null,
            ];

            session([
                'user_id'                     => $user->id,
                'role'                        => 'pet',
                'token'                       => $plainToken,
                'token_type'                  => 'Bearer',
                'chat_room'                   => $response['chat_room'],
                'user'                        => $userData,
                'auth_full'                   => $response,
                'vet_id'                      => null,
                'vet_registeration_id'        => null,
                'vet_registerations_temp_id'  => null,
            ]);

            return response()->json($response, 200);

        } elseif ($role === 'vet') {
            $clinicRow = DB::table('vet_registerations_temp')
                ->where('email', $email)
                ->first();

            if ($clinicRow) {
                DB::transaction(function () use (&$plainToken, &$room, $clinicRow, $roomTitle) {
                    $plainToken = bin2hex(random_bytes(32));

                    DB::table('vet_registerations_temp')
                        ->where('id', $clinicRow->id)
                        ->update(['api_token_hash' => $plainToken]);

                    $room = ChatRoom::create([
                        'user_id'         => $clinicRow->id,
                        'chat_room_token' => 'room_' . Str::uuid()->toString(),
                        'name'            => $roomTitle ?? ('New chat - ' . now()->format('d M Y H:i')),
                    ]);
                });

                $clinicId = (int) $clinicRow->id;

                $clinicData = (array) $clinicRow;
                unset($clinicData['password']);
                $clinicData['role'] = 'clinic_admin';
                $clinicData['clinic_id'] = $clinicId;

                $response = [
                    'message'    => 'Login successful',
                    'role'       => 'clinic_admin',
                    'email'      => $clinicRow->email,
                    'token'      => $plainToken,
                    'token_type' => 'Bearer',
                    'chat_room'  => [
                        'id'    => $room->id,
                        'token' => $room->chat_room_token,
                        'name'  => $room->name,
                    ],
                    'user'        => $clinicData,
                    'user_id'     => $clinicId,
                    'clinic_id'   => $clinicId,
                    'vet_id'      => $clinicId,
                    'vet_registeration_id'       => $clinicId,
                    'vet_registerations_temp_id' => $clinicId,
                    'doctor_id'  => null,
                ];

                session([
                    'user_id'                     => $clinicId,
                    'clinic_id'                   => $clinicId,
                    'doctor_id'                   => null,
                    'role'                        => 'clinic_admin',
                    'token'                       => $plainToken,
                    'token_type'                  => 'Bearer',
                    'chat_room'                   => $response['chat_room'],
                    'user'                        => $clinicData,
                    'auth_full'                   => $response,
                    'vet_id'                      => $clinicId,
                    'vet_registeration_id'        => $clinicId,
                    'vet_registerations_temp_id'  => $clinicId,
                ]);

                return response()->json($response, 200);
            }

            $doctorRow = DB::table('doctors')
                ->where('doctor_email', $email)
                ->first();

            if (!$doctorRow) {
                return response()->json(['message' => 'Doctor not found'], 404);
            }

            $clinicId = (int) ($doctorRow->vet_registeration_id ?? 0);
            $clinicForDoctor = $clinicId > 0
                ? DB::table('vet_registerations_temp')->where('id', $clinicId)->first()
                : null;

            DB::transaction(function () use (&$plainToken, &$room, $doctorRow, $roomTitle) {
                $plainToken = bin2hex(random_bytes(32));

                $room = ChatRoom::create([
                    'user_id'         => $doctorRow->id,
                    'chat_room_token' => 'room_' . Str::uuid()->toString(),
                    'name'            => $roomTitle ?? ('New chat - ' . now()->format('d M Y H:i')),
                ]);
            });

            $doctorData = (array) $doctorRow;
            $doctorData['role'] = 'doctor';
            $doctorData['clinic_id'] = $clinicId ?: null;
            $doctorData['vet_registeration_id'] = $clinicId ?: null;
            $doctorData['vet_registerations_temp_id'] = $clinicId ?: null;
            $doctorData['email'] = $doctorRow->doctor_email;
            if ($clinicForDoctor) {
                $doctorData['clinic_profile'] = $clinicForDoctor->clinic_profile ?? ($clinicForDoctor->name ?? null);
            }

            $doctorId = (int) $doctorRow->id;

            $response = [
                'message'    => 'Login successful',
                'role'       => 'doctor',
                'email'      => $doctorRow->doctor_email,
                'token'      => $plainToken,
                'token_type' => 'Bearer',
                'chat_room'  => [
                    'id'    => $room->id,
                    'token' => $room->chat_room_token,
                    'name'  => $room->name,
                ],
                'user'        => $doctorData,
                'user_id'     => $doctorId,
                'doctor_id'   => $doctorId,
                'clinic_id'   => $clinicId ?: null,
                'vet_id'      => $clinicId ?: null,
                'vet_registeration_id'       => $clinicId ?: null,
                'vet_registerations_temp_id' => $clinicId ?: null,
            ];

            session([
                'user_id'                     => $doctorId,
                'doctor_id'                   => $doctorId,
                'clinic_id'                   => $clinicId ?: null,
                'role'                        => 'doctor',
                'token'                       => $plainToken,
                'token_type'                  => 'Bearer',
                'chat_room'                   => $response['chat_room'],
                'user'                        => $doctorData,
                'auth_full'                   => $response,
                'vet_id'                      => $clinicId ?: null,
                'vet_registeration_id'        => $clinicId ?: null,
                'vet_registerations_temp_id'  => $clinicId ?: null,
            ]);

            return response()->json($response, 200);
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
        'role'         => 'required|string|in:pet,vet',
        'room_title'   => 'nullable|string',
    ]);

    try {
        $room = null;
        $plainToken = null;
        $role = $request->role;

        if ($role === 'pet') {
            // 🔹 Pet users table check
            $user = User::where('email', $request->email)
                        ->where('google_token', $request->google_token)
                        ->first();

            if (!$user) {
                // Fallback: locate by email and attach token; if still not found, create a minimal user
                $user = User::where('email', $request->email)->first();
                if ($user) {
                    $user->google_token = $request->google_token;
                    $user->save();
                } else {
                    $name = explode('@', $request->email)[0] ?? 'Pet User';
                    $user = User::create([
                        'name'         => $name,
                        'email'        => $request->email,
                        'password'     => null,
                        'google_token' => $request->google_token,
                    ]);
                }
            }

            // ✅ Laravel login
            Auth::login($user);

            DB::transaction(function () use (&$plainToken, &$room, $user, $request) {
                $plainToken = bin2hex(random_bytes(32));
                $user->api_token_hash = $plainToken;
                $user->save();

                $room = ChatRoom::create([
                    'user_id'         => $user->id,
                    'chat_room_token' => 'room_' . Str::uuid()->toString(),
                    'name'            => $request->room_title ?? ('New chat - ' . now()->format('d M Y H:i')),
                ]);
            });

            $userData = $user->toArray();
            unset($userData['password']);
            $userData['role'] = 'pet';

            $response = [
                'success'    => true,
                'message'    => 'Login success',
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
                'user_id'    => $user->id,
                'vet_id'     => null,
            ];

            // ✅ Save everything in session
            session([
                'user_id'                     => $user->id,
                'role'                        => 'pet',
                'token'                       => $plainToken,
                'token_type'                  => 'Bearer',
                'chat_room'                   => $response['chat_room'],
                'user'                        => $userData,
                'auth_full'                   => $response,
                'vet_id'                      => null,
                'vet_registeration_id'        => null,
                'vet_registerations_temp_id'  => null,
            ]);

            return response()->json($response, 200);

        } elseif ($role === 'vet') {
            // 🔹 Vet table check
            $tempVet = DB::table('vet_registerations_temp')
                ->where('email', $request->email)
                ->where('google_token', $request->google_token)
                ->first();

            if (!$tempVet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid vet credentials',
                ], 401);
            }

            DB::transaction(function () use (&$plainToken, &$room, $tempVet, $request) {
                $plainToken = bin2hex(random_bytes(32));

                DB::table('vet_registerations_temp')
                    ->where('id', $tempVet->id)
                    ->update(['api_token_hash' => $plainToken]);

                $room = ChatRoom::create([
                    'user_id'         => $tempVet->id,
                    'chat_room_token' => 'room_' . Str::uuid()->toString(),
                    'name'            => $request->room_title ?? ('New chat - ' . now()->format('d M Y H:i')),
                ]);
            });

            $vetData = (array) $tempVet;
            unset($vetData['password']);
            $vetData['role'] = 'vet';

            $vetId = (int) $tempVet->id;

            $response = [
                'success'    => true,
                'message'    => 'Login success',
                'role'       => 'vet',
                'email'      => $tempVet->email,
                'token'      => $plainToken,
                'token_type' => 'Bearer',
                'chat_room'  => [
                    'id'    => $room->id,
                    'token' => $room->chat_room_token,
                    'name'  => $room->name,
                ],
                'user'       => $vetData,
                'user_id'    => $vetId,
                'vet_id'     => $vetId,
                'vet_registeration_id'       => $vetId,
                'vet_registerations_temp_id' => $vetId,
            ];

            // ✅ Save everything in session
            session([
                'user_id'                     => $vetId,
                'role'                        => 'vet',
                'token'                       => $plainToken,
                'token_type'                  => 'Bearer',
                'chat_room'                   => $response['chat_room'],
                'user'                        => $vetData,
                'auth_full'                   => $response,
                'vet_id'                      => $vetId,
                'vet_registeration_id'        => $vetId,
                'vet_registerations_temp_id'  => $vetId,
            ]);

            return response()->json($response, 200);
        }

        return response()->json(['message' => 'Invalid role'], 400);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Google login failed',
            'error'   => $e->getMessage(),
        ], 500);
    }
}


// public function googleLogin(Request $request)
// {
//     $request->validate([
//         'email'        => 'required|email',
//         'google_token' => 'required|string',
//         'role'         => 'required|string|in:pet,vet',
//         'room_title'   => 'nullable|string',
//     ]);

//     try {
//         $room = null;
//         $plainToken = null;
//         $role = $request->role;

//         if ($role === 'pet') {
//             // 🔹 Pet users table check
//             $user = User::where('email', $request->email)
//                         ->where('google_token', $request->google_token)
//                         ->first();

//             if (!$user) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Invalid pet credentials',
//                 ], 401);
//             }
//              // ✅ User ko login karao (session me save)
//     Auth::login($user);

//     // ✅ Laravel session me directly save (extra key agar chahiye)
//     session([
//         'user_id' => $user->id,
//         'user_email' => $user->email,
//         'role' => $user->role
//     ]);

//             DB::transaction(function () use (&$plainToken, &$room, $user, $request) {
//                 $plainToken = bin2hex(random_bytes(32));
//                 $user->api_token_hash = $plainToken;
//                 $user->save();

//                 $room = ChatRoom::create([
//                     'user_id'         => $user->id,
//                     'chat_room_token' => 'room_' . Str::uuid()->toString(),
//                     'name'            => $request->room_title ?? ('New chat - ' . now()->format('d M Y H:i')),
//                 ]);
//             });

//             $userData = $user->toArray();
//             unset($userData['password']);
//             $userData['role'] = 'pet';

//             return response()->json([
//                 'success'    => true,
//                 'message'    => 'Login success',
//                 'role'       => 'pet',
//                 'email'      => $user->email,
//                 'token'      => $plainToken,
//                 'token_type' => 'Bearer',
//                 'chat_room'  => [
//                     'id'    => $room->id,
//                     'token' => $room->chat_room_token,
//                     'name'  => $room->name,
//                 ],
//                 'user'       => $userData,
//             ], 200);

//         } elseif ($role === 'vet') {
//             // 🔹 Vet table check
//             $tempVet = DB::table('vet_registerations_temp')
//                 ->where('email', $request->email)
//                 ->where('google_token', $request->google_token)
//                 ->first();

//             if (!$tempVet) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Invalid vet credentials',
//                 ], 401);
//             }

//             DB::transaction(function () use (&$plainToken, &$room, $tempVet, $request) {
//                 $plainToken = bin2hex(random_bytes(32));

//                 DB::table('vet_registerations_temp')
//                     ->where('id', $tempVet->id)
//                     ->update(['api_token_hash' => $plainToken]);

//                 $room = ChatRoom::create([
//                     'user_id'         => $tempVet->id,
//                     'chat_room_token' => 'room_' . Str::uuid()->toString(),
//                     'name'            => $request->room_title ?? ('New chat - ' . now()->format('d M Y H:i')),
//                 ]);
//             });

//             $vetData = (array) $tempVet;
//             unset($vetData['password']);
//             $vetData['role'] = 'vet';

//             return response()->json([
//                 'success'    => true,
//                 'message'    => 'Login success',
//                 'role'       => 'vet',
//                 'email'      => $tempVet->email,
//                 'token'      => $plainToken,
//                 'token_type' => 'Bearer',
//                 'chat_room'  => [
//                     'id'    => $room->id,
//                     'token' => $room->chat_room_token,
//                     'name'  => $room->name,
//                 ],
//                 'user'       => $vetData,
//             ], 200);
//         }

//         return response()->json(['message' => 'Invalid role'], 400);

//     } catch (\Exception $e) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Google login failed',
//             'error'   => $e->getMessage(),
//         ], 500);
//     }
// }

public function googleLogin_bkp(Request $request)
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
