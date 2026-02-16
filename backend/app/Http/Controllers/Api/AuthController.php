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
use App\Models\Chat;
use App\Models\Pet;
use App\Models\UserPet;
use App\Models\Doctor;
use App\Models\Receptionist;
use App\Models\Transaction;
use App\Models\VetRegisterationTemp;
use App\Models\CallSession;


use Illuminate\Support\Facades\Http;
use App\Support\GeminiConfig;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

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
        if (! $this->hasTokenColumns('users')) {
            Log::warning('api_token_* columns missing on users; skipping bearer lookup');
            return null;
        }
        return User::where('api_token_hash', $hash)
            ->where('api_token_expires_at', '>', now())
            ->first();
    }

    private function hasTokenColumns(string $table): bool
    {
        return Schema::hasColumn($table, 'api_token_hash')
            && Schema::hasColumn($table, 'api_token_expires_at');
    }

    private function assignTokenToModel($model, string $hash, $expiresAt): void
    {
        $table = $model->getTable();
        if (!$this->hasTokenColumns($table)) {
            Log::warning("Skipping api_token_* set for {$table}: columns missing");
            return;
        }

        $model->api_token_hash = $hash;
        $model->api_token_expires_at = $expiresAt;
        $model->save();
    }

    private function persistTokenToTable(string $table, int $id, string $hash, $expiresAt): void
    {
        if (!$this->hasTokenColumns($table)) {
            Log::warning("Skipping api_token_* update for {$table}: columns missing");
            return;
        }

        DB::table($table)
            ->where('id', $id)
            ->update([
                'api_token_hash' => $hash,
                'api_token_expires_at' => $expiresAt,
            ]);
    }

    private function passwordMatches(?string $storedPassword, string $providedPassword): bool
    {
        if ($storedPassword === null || $storedPassword === '') {
            return false;
        }

        if (Str::startsWith($storedPassword, '$2y$') || Str::startsWith($storedPassword, '$argon2')) {
            return Hash::check($providedPassword, $storedPassword);
        }

        return hash_equals((string) $storedPassword, $providedPassword);
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

    private function extractGeo(Request $request): array
    {
        $latKeys = ['latitude', 'lat', 'lattitude', 'laititude'];
        $lngKeys = ['longitude', 'lng', 'lang', 'lon', 'long', 'longtitude'];

        $fetch = static function (array $keys) use ($request) {
            foreach ($keys as $key) {
                $value = $request->input($key);
                if ($value === null || $value === '') {
                    continue;
                }
                if (is_string($value)) {
                    $value = trim($value);
                    if ($value === '') {
                        continue;
                    }
                }
                return is_numeric($value) ? (float) $value : $value;
            }
            return null;
        };

        return [$fetch($latKeys), $fetch($lngKeys)];
    }

    private function loadRelatedPets(?User $user): array
    {
        if (! $user) {
            return [collect(), collect()];
        }

        $pets = Schema::hasTable('pets')
            ? Pet::where('user_id', $user->id)->orderByDesc('id')->get()
            : collect();

        $userPets = Schema::hasTable('user_pets')
            ? UserPet::where('user_id', $user->id)->orderByDesc('id')->get()
            : collect();

        return [$pets, $userPets];
    }

    private function latestCallSessionForUser(?User $user): ?CallSession
    {
        if (!$user || !Schema::hasTable('call_sessions')) {
            return null;
        }

        $query = CallSession::where('patient_id', $user->id);

        $orderColumns = [];
        foreach (['ended_at', 'started_at', 'created_at'] as $column) {
            if (Schema::hasColumn('call_sessions', $column)) {
                $orderColumns[] = "call_sessions.{$column}";
            }
        }

        if ($orderColumns) {
            $query->orderByDesc(DB::raw('COALESCE(' . implode(', ', $orderColumns) . ')'));
        }

        $query->orderByDesc('id');

        return $query->first();
    }

    private function formatCallSessionForResponse(?CallSession $session): ?array
    {
        if (!$session) {
            return null;
        }

        return [
            'id' => $session->id,
            'call_identifier' => $session->resolveIdentifier(),
            'channel_name' => $session->channel_name,
            'doctor_id' => $session->doctor_id,
            'patient_id' => $session->patient_id,
            'status' => $session->status,
            'payment_status' => $session->payment_status,
            'doctor_join_url' => $session->resolvedDoctorJoinUrl(),
            'patient_payment_url' => $session->resolvedPatientPaymentUrl(),
            'accepted_at' => optional($session->accepted_at)->toIso8601String(),
            'started_at' => optional($session->started_at)->toIso8601String(),
            'ended_at' => optional($session->ended_at)->toIso8601String(),
            'created_at' => optional($session->created_at)->toIso8601String(),
            'updated_at' => optional($session->updated_at)->toIso8601String(),
        ];
    }

    private function resolvePetIdForOverview(Request $request, Collection $pets): ?int
    {
        $requested = $request->input('pet_id') ?? $request->input('petId');
        if (is_numeric($requested)) {
            $requestedId = (int) $requested;
            if ($pets->contains('id', $requestedId)) {
                return $requestedId;
            }
        }

        $firstPet = $pets->first();
        return $firstPet?->id;
    }

    private function fetchPetOverview(Request $request, ?int $petId): ?array
    {
        if (! $petId) {
            return null;
        }

        try {
            $controller = app(PetOverviewController::class);
            $response = $controller->show($request, $petId);
        } catch (\Throwable $e) {
            Log::warning('Pet overview lookup failed', [
                'pet_id' => $petId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        return $response->getData(true);
    }

    private function snapshotPhoneOtp(?User $user, string $otp, Carbon $expiresAt): void
    {
        if (!$user) {
            return;
        }

        $user->forceFill([
            'last_otp' => $otp,
            'last_otp_expires_at' => $expiresAt,
            'last_otp_verified_at' => null,
        ])->save();
    }

    private function markPhoneVerified(?User $user, string $phone, ?Otp $otpEntry = null): void
    {
        if (!$user) {
            return;
        }

        $now = Carbon::now();

        $updates = [
            'phone_verified_at' => $now,
            'last_otp_verified_at' => $now,
        ];

        // Only override phone if it is empty; avoid collisions on unique constraint
        if (empty($user->phone)) {
            $updates['phone'] = $phone;
        }

        if ($otpEntry) {
            $updates['last_otp'] = $otpEntry->otp;
            $updates['last_otp_expires_at'] = $otpEntry->expires_at;
        }

        $user->forceFill($updates)->save();
    }

    private function ensureLastVetSlug(?User $user): ?string
    {
        if (! $user || ! $user->last_vet_id) {
            return $user?->last_vet_slug;
        }

        if (! Schema::hasTable('vet_registerations_temp')) {
            return $user->last_vet_slug;
        }

        $slug = VetRegisterationTemp::query()
            ->where('id', $user->last_vet_id)
            ->value('slug');

        if ($slug && $slug !== $user->last_vet_slug) {
            $user->last_vet_slug = $slug;
            $user->save();
        }

        return $slug ?? $user->last_vet_slug;
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
                'type' => ['nullable', Rule::in(['whatsapp'])],
                'value' => 'required|string',
            ]);

            $type = 'whatsapp';
            $rawValue = trim((string) $request->input('value'));
            $otp = (string) random_int(1000, 9999);
            $token = (string) Str::uuid();
            $expiresAt = Carbon::now()->addMinutes(10);
            $normalizedPhone = null;
            $otpUser = null;

            $normalizedPhone = $this->normalizePhone($rawValue);

            if (! $normalizedPhone) {
                return response()->json([
                    'message' => 'Invalid phone number for WhatsApp verification',
                ], 422);
            }

            if (! $this->whatsApp->isConfigured()) {
                if (!config('app.debug')) {
                    return response()->json([
                        'message' => 'WhatsApp channel is temporarily unavailable',
                        'code'    => 'WHATSAPP_UNCONFIGURED',
                    ], 503);
                }

                Log::warning('otp.whatsapp.unconfigured', ['phone' => $normalizedPhone]);
            }

            if ($this->shouldCheckUniqueness($request)) {
                $exists = User::query()
                    ->where('phone', $normalizedPhone)
                    ->orWhere('phone', $rawValue)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'message' => 'Phone is already registered with us',
                    ], 401);
                }
            }

            try {
                $this->whatsApp->sendOtpTemplate($normalizedPhone, $otp);
            } catch (\Throwable $e) {
                Log::error('otp.whatsapp.failed', [
                    'phone' => $normalizedPhone,
                    'error' => $e->getMessage(),
                ]);

                // In non-debug environments, return a clear failure instead of a 500
                if (!config('app.debug')) {
                    return response()->json([
                        'message' => 'Unable to send OTP at this time. Please try again shortly.',
                        'code'    => 'WHATSAPP_SEND_FAILED',
                    ], 503);
                }
            }

            $otpUser = User::where('phone', $normalizedPhone)
                ->orWhere('phone', $rawValue)
                ->first();

            Otp::create([
                'token'       => $token,
                'type'        => $type,
                'value'       => $normalizedPhone,
                'otp'         => $otp,
                'expires_at'  => $expiresAt,
                'is_verified' => 0,
            ]);

            $this->snapshotPhoneOtp($otpUser, $otp, $expiresAt);

            return response()->json([
                'message' => 'OTP sent successfully',
                'channel' => $type,
                'otp'     => config('app.debug') ? $otp : 'hidden',
                'token'   => $token,
            ], 200);
        } catch (\Throwable $e) {
            report($e);

            Log::error('otp.send.failed', [
                'phone'   => $request->input('value'),
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'message' => config('app.debug')
                    ? ('OTP send failed: ' . $e->getMessage())
                    : 'Unable to send OTP at this time. Please try again shortly.',
                'code' => 'OTP_SEND_FAILED',
            ], 503);
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
            'otp'   => 'required|string',
            'phone' => 'required|string',
            'pet_id' => 'nullable|integer',
            'petId' => 'nullable|integer',
            'lat'   => 'nullable|numeric',
            'lang'  => 'nullable|numeric',
        ]);

        $normalizedPhone = $this->normalizePhone($request->phone);
        if (! $normalizedPhone) {
            return response()->json(['error' => 'Invalid phone number'], 422);
        }

        $otpEntry = Otp::query()
            ->where('token', $request->token)
            ->where('type', 'whatsapp')
            ->where('value', $normalizedPhone)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpEntry) {
            return response()->json(['error' => 'Invalid or expired OTP'], 401);
        }

        [$latitude, $longitude] = $this->extractGeo($request);

        if ($otpEntry->is_verified) {
            $existingUser = User::where('phone', $normalizedPhone)
                ->orWhere('email', $normalizedPhone)
                ->first();

            if ($existingUser && ($latitude !== null || $longitude !== null)) {
                $geoUpdates = [];
                if ($latitude !== null) {
                    $geoUpdates['latitude'] = $latitude;
                }
                if ($longitude !== null) {
                    $geoUpdates['longitude'] = $longitude;
                }
                if ($geoUpdates) {
                    $existingUser->forceFill($geoUpdates)->save();
                }
            }

            $lastVetSlug = $this->ensureLastVetSlug($existingUser);
            [$pets, $userPets] = $this->loadRelatedPets($existingUser);
            $latestChat = $existingUser ? Chat::where('user_id', $existingUser->id)->latest()->first() : null;
            $latestCallSession = $this->latestCallSessionForUser($existingUser);
            $overviewPetId = $this->resolvePetIdForOverview($request, $pets);
            $petOverview = $this->fetchPetOverview($request, $overviewPetId);

            return response()->json([
                'message' => 'OTP already verified',
                'user_id' => $existingUser?->id,
                'user'    => $existingUser,
                'last_vet_slug' => $lastVetSlug,
                'slug' => $lastVetSlug,
                'pets'    => $pets,
                'user_pets' => $userPets,
                'latest_chat' => $latestChat,
                'latest_call_session' => $this->formatCallSessionForResponse($latestCallSession),
                'pet_overview' => $petOverview,
            ], 200);
        }

        $otpUser = User::where('phone', $normalizedPhone)
            ->orWhere('email', $normalizedPhone)
            ->orWhere('phone', $request->phone)
            ->first();

        $otpEntry->update(['is_verified' => 1]);

        $user = $otpUser;

        if (! $user) {
            $user = User::create([
                'name'              => $request->fullName ?? $normalizedPhone,
                'email'             => $normalizedPhone,
                'phone'             => $normalizedPhone,
                'password'          => null,
                'google_token'      => $request->google_token,
                'latitude'          => $latitude ?? $request->latitude,
                'longitude'         => $longitude ?? $request->longitude,
                'phone_verified_at' => now(),
            ]);
        } else {
            $geoUpdates = [];
            if ($latitude !== null) {
                $geoUpdates['latitude'] = $latitude;
            }
            if ($longitude !== null) {
                $geoUpdates['longitude'] = $longitude;
            }

            $user->forceFill(array_merge([
                'phone_verified_at' => $user->phone_verified_at ?? now(),
                'phone'             => $user->phone ?: $normalizedPhone,
                'email'             => $user->email ?: $normalizedPhone,
            ], $geoUpdates))->save();
        }

        $this->markPhoneVerified($user, $normalizedPhone, $otpEntry);
        $lastVetSlug = $this->ensureLastVetSlug($user);
        [$pets, $userPets] = $this->loadRelatedPets($user);
        $latestChat = Chat::where('user_id', $user->id)->latest()->first();
        $latestCallSession = $this->latestCallSessionForUser($user);
        $overviewPetId = $this->resolvePetIdForOverview($request, $pets);
        $petOverview = $this->fetchPetOverview($request, $overviewPetId);

        return response()->json([
            'message' => 'OTP verified successfully',
            'user_id' => $user->id,
            'user'    => $user,
            'last_vet_slug' => $lastVetSlug,
            'slug' => $lastVetSlug,
            'pets'    => $pets,
            'user_pets' => $userPets,
            'latest_chat' => $latestChat,
            'latest_call_session' => $this->formatCallSessionForResponse($latestCallSession),
            'pet_overview' => $petOverview,
        ]);
    }

    // --------------------------- REGISTER -----------------------------------

public function createInitialRegistration(Request $request)
{
    try {
        [$latitude, $longitude] = $this->extractGeo($request);
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
         'latitude'    => $latitude ?? $request->latitude,
        'longitude'   => $longitude ?? $request->longitude,
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

    public function createInitialRegistrationMobile(Request $request)
    {
        try {
            [$latitude, $longitude] = $this->extractGeo($request);
            $request->validate([
                'mobileNumber' => ['required', 'string'],
            ]);

            $mobile = (string) $request->mobileNumber;

            $mobileExists = DB::table('users')
                ->where('phone', $mobile)
                ->orWhere('email', $mobile)
                ->exists();

            if ($mobileExists) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'enter unique mobile number'
                ], 422);
            }

            $user = User::create([
                'name'         => $request->fullName,
                'email'        => $mobile,
                'phone'        => $mobile,
                'password'     => null,
                'google_token' => $request->google_token,
                'latitude'     => $latitude ?? $request->latitude,
                'longitude'    => $longitude ?? $request->longitude,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Initial registration (mobile) created',
                'user_id' => $user->id,
                'user'    => $user,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong while creating initial registration via mobile',
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

    if ($user->phone && ! $user->phone_verified_at) {
        $normalizedPhone = $this->normalizePhone($user->phone);
        $hasVerifiedOtp = $normalizedPhone && Otp::query()
            ->where('type', 'whatsapp')
            ->where('value', $normalizedPhone)
            ->where('is_verified', 1)
            ->where('expires_at', '>', now())
            ->exists();

        if (! $hasVerifiedOtp) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Please verify your mobile number via OTP before completing registration',
            ], 422);
        }

        $user->phone_verified_at = now();
        $user->save();
    }

    $existingDoc1 = $user->pet_doc1;
    $existingDoc2 = $user->pet_doc2;
    $existingSummary = $user->summary;
    $ownerName = $request->input('pet_owner_name') ?? $request->input('fullName') ?? $user->name;
    $userBlobColumnsReady = $this->userPetDoc2BlobColumnsReady();
    $petBlobColumnsReady = $this->petPetDoc2BlobColumnsReady();
    $hasNewDoc2Upload = $request->hasFile('pet_doc2');
    [$doc2Blob, $doc2Mime] = $this->extractPetDocumentBlob($request, 'pet_doc2');

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
    $tokenHash  = hash('sha256', $plainToken);
    $tokenExpiresAt = now()->addDays(30);
    [$latitude, $longitude] = $this->extractGeo($request);
    $petType = $request->filled('pet_type') ? $request->input('pet_type') : null;
    $petDob = $request->filled('pet_dob') ? $request->input('pet_dob') : null;
    $weightRaw = $request->filled('pet_weight') ? $request->input('pet_weight') : $request->input('weight');
    $petWeight = null;
    if ($weightRaw !== null && $weightRaw !== '' && is_numeric($weightRaw)) {
        $petWeight = (float) $weightRaw;
    }

    try {
        $pet = DB::transaction(function () use ($user, $request, $doc1Path, $doc2Path, $summaryText, $tokenHash, $tokenExpiresAt, $latitude, $longitude, $petType, $petDob, $petWeight, $ownerName, $userBlobColumnsReady, $petBlobColumnsReady, $hasNewDoc2Upload, $doc2Blob, $doc2Mime) {
            // ✅ Update user with final details
            $user->fill([
                'name'        => $ownerName,
                'pet_name'    => $request->pet_name,
                'pet_gender'  => $request->pet_gender,
                'pet_age'     => $request->pet_age,
                'pet_doc1'    => $doc1Path,
                'pet_doc2'    => $doc2Path,
                'summary'     => $summaryText,
                'breed'       => $request->breed,
                'latitude'    => $latitude ?? $request->latitude ?? $user->latitude,
                'longitude'   => $longitude ?? $request->longitude ?? $user->longitude,
            ]);

            if ($userBlobColumnsReady && $hasNewDoc2Upload && $doc2Blob !== null) {
                $user->pet_doc2_blob = $doc2Blob;
                $user->pet_doc2_mime = $doc2Mime;
            }

            // Persist core fields even if api_token_* columns are missing
            $user->save();

            $this->assignTokenToModel($user, $tokenHash, $tokenExpiresAt);

            // If pets table is absent, skip pet upsert gracefully
            if (!Schema::hasTable('pets')) {
                return null;
            }

            $petAttributes = [
                'name'       => $request->pet_name,
                'breed'      => $request->breed,
                'pet_age'    => $request->pet_age,
                'pet_gender' => $request->pet_gender,
                'pet_doc1'   => $doc1Path,
                'pet_doc2'   => $doc2Path,
            ];
            if ($petType !== null) {
                $petAttributes['pet_type'] = $petType;
            }
            if ($petDob !== null) {
                $petAttributes['pet_dob'] = $petDob;
            }
            if ($petWeight !== null) {
                $petAttributes['weight'] = $petWeight;
            }
            if ($petBlobColumnsReady && $hasNewDoc2Upload && $doc2Blob !== null) {
                $petAttributes['pet_doc2_blob'] = $doc2Blob;
                $petAttributes['pet_doc2_mime'] = $doc2Mime;
            }

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
        Log::error('register.failed', [
            'user_id' => $request->user_id,
            'error' => $e->getMessage(),
            'trace' => app()->environment('production') ? null : $e->getTraceAsString(),
        ]);
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
        'pet_doc2_blob_url' => $this->userPetDoc2BlobUrl($user),
        'token'      => $plainToken,
        'token_type' => 'Bearer',
    ], 200);
}

    public function userPetDoc2Blob(User $user)
    {
        if (! $this->userPetDoc2BlobColumnsReady()) {
            return response()->json([
                'success' => false,
                'message' => 'pet_doc2 blob columns are missing. Please run migrations.',
            ], 500);
        }

        $blob = $user->getRawOriginal('pet_doc2_blob');
        if ($blob === null || $blob === '') {
            return response()->json([
                'success' => false,
                'message' => 'pet_doc2 blob not found.',
            ], 404);
        }

        $mime = $user->pet_doc2_mime ?: 'application/octet-stream';

        return response($blob, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="user-' . $user->id . '-pet-doc2"',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }


    public function register_latest_backup(Request $request)
    {
        // check mobile (phone/email columns) instead of email
        $mobileExists = DB::table('users')
            ->where('phone', $request->mobileNumber)
            ->orWhere('email', $request->mobileNumber)
            ->exists();

        if ($mobileExists) {
            return response()->json([
                'status'  => 'error',
                'message' => 'enter unique mobile number'
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
        'email'       => $request->mobileNumber,
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
    $tokenExpiresAt = now()->addDays(30);
    $this->assignTokenToModel($user, hash('sha256', $plainToken), $tokenExpiresAt);

    return response()->json([
        'message'    => 'User registered successfully',
        'user'       => $user,
        'token'      => $plainToken,
        'token_type' => 'Bearer',
    ], 201);
}

    public function registerViaMobile(Request $request)
    {
        $request->validate([
            'mobileNumber' => ['required', 'string'],
        ]);

        $mobileNumber = (string) $request->mobileNumber;
        $normalizedPhone = $this->normalizePhone($mobileNumber);
        if (! $normalizedPhone) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid mobile number format',
            ], 422);
        }

        $mobileExists = DB::table('users')
            ->where('phone', $mobileNumber)
            ->orWhere('email', $mobileNumber)
            ->exists();

        if ($mobileExists) {
            return response()->json([
                'status'  => 'error',
                'message' => 'enter unique mobile number'
            ], 422);
        }

        $verifiedOtp = Otp::query()
            ->where('type', 'whatsapp')
            ->where('value', $normalizedPhone)
            ->where('is_verified', 1)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $verifiedOtp) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Please verify OTP for this mobile number before registering',
            ], 422);
        }

        $doc1Path = null;
        $doc2Path = null;
        $summaryText = null;
        $doc1AbsolutePath = null;
        $doc2AbsolutePath = null;

        [$doc1Path, $doc1AbsolutePath] = $this->storePetDocument($request, 'pet_doc1');
        [$doc2Path, $doc2AbsolutePath] = $this->storePetDocument($request, 'pet_doc2');

        $imagePath = $doc1AbsolutePath ?? $doc2AbsolutePath;
        if ($imagePath) {
            $summaryText = $this->describePetImageDynamic($imagePath);
        }

        $user = User::create([
            'name'        => $request->fullName,
            'email'       => $mobileNumber,
            'phone'       => $mobileNumber,
            'password'    => $request->password, // ⚠ plain text (unsafe in prod)
            'phone_verified_at' => now(),
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

        $plainToken = bin2hex(random_bytes(32));
        $this->assignTokenToModel($user, hash('sha256', $plainToken), now()->addDays(30));

        return response()->json([
            'message'    => 'User registered successfully (mobile)',
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

private function extractPetDocumentBlob(Request $request, string $field): array
{
    if (! $request->hasFile($field)) {
        return [null, null];
    }

    $file = $request->file($field);
    if (! $file || ! $file->isValid()) {
        return [null, null];
    }

    return [
        $file->get(),
        $file->getMimeType() ?: ($file->getClientMimeType() ?: 'application/octet-stream'),
    ];
}

private function userPetDoc2BlobColumnsReady(): bool
{
    return Schema::hasTable('users')
        && Schema::hasColumn('users', 'pet_doc2_blob')
        && Schema::hasColumn('users', 'pet_doc2_mime');
}

private function petPetDoc2BlobColumnsReady(): bool
{
    return Schema::hasTable('pets')
        && Schema::hasColumn('pets', 'pet_doc2_blob')
        && Schema::hasColumn('pets', 'pet_doc2_mime');
}

private function userPetDoc2BlobUrl(?User $user): ?string
{
    if (! $user || ! $this->userPetDoc2BlobColumnsReady()) {
        return null;
    }

    $blob = $user->getRawOriginal('pet_doc2_blob');
    if ($blob === null || $blob === '') {
        return null;
    }

    return route('api.users.pet-doc2-blob', ['user' => $user->id]);
}

/**
 * Gemini se image ka description nikalna (dynamic image path ke liye)
 */
private function describePetImageDynamic($imagePath)
{
    // Gemini integration disabled (API key suspended); skip summary generation
    return null;
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

            // Hard-stop on auth/rate/permission errors; skip further models and return null
            if (in_array($response->status(), [401, 403, 429], true)) {
                return null;
            }

            // For other errors (5xx etc), don't keep cycling through models
            if ($response->status() !== 404) {
                return null;
            }
        }
    }

    // If we reach here, all attempts were skipped or failed softly; avoid creating a summary
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
  //  dd($request->all());
    try {
        $request->validate([
            'role'     => ['required', 'string'],
            'password' => ['required', 'string'],
            'email'    => ['nullable', 'string'],
            'login'    => ['nullable', 'string'],
        ]);

        $email = $request->input('email') ?? $request->input('login');
        $role  = $request->input('role'); // 👈 role pick karo
        $password = (string) $request->input('password', '');
        $roomTitle = $request->input('room_title');

        if (empty($email) || empty($role)) {
            return response()->json(['message' => 'Email/login or role missing'], 422);
        }

        $room = null;
        $plainToken = null;
        $tokenExpiresAt = now()->addDays(30);

        $adminEmail = strtolower(trim((string) config('admin.email', 'admin@snoutiq.com')));
        if ($adminEmail === '') {
            $adminEmail = 'admin@snoutiq.com';
        }

        $adminPassword = (string) config('admin.password', 'snoutiqvet');
        if ($adminPassword === '') {
            $adminPassword = 'snoutiqvet';
        }

        if ($role === 'vet' && $adminEmail && strtolower(trim((string) $email)) === $adminEmail) {
            if (!$this->passwordMatches($adminPassword, $password)) {
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

            if (!$this->passwordMatches($user->password, $password)) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            DB::transaction(function () use (&$plainToken, &$room, $user, $roomTitle, $tokenExpiresAt) {
                $plainToken = bin2hex(random_bytes(32));
                $this->assignTokenToModel($user, hash('sha256', $plainToken), $tokenExpiresAt);

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

            $doctorRow = DB::table('doctors')
                ->where('doctor_email', $email)
                ->first();

            $receptionistRow = Receptionist::where('email', $email)->first();
            $receptionistRow = Receptionist::where('email', $email)->first();

            $clinicPasswordOk = $clinicRow && $this->passwordMatches(data_get($clinicRow, 'password'), $password);
            $doctorPasswordOk = $doctorRow && $this->passwordMatches(
                data_get($doctorRow, 'password') ?? data_get($doctorRow, 'doctor_password'),
                $password
            );
            $receptionistPasswordOk = $receptionistRow && $this->passwordMatches(
                data_get($receptionistRow, 'password') ?? data_get($receptionistRow, 'receptionist_password'),
                $password
            );

            if ($clinicPasswordOk) {
                DB::transaction(function () use (&$plainToken, &$room, $clinicRow, $roomTitle, $tokenExpiresAt) {
                    $plainToken = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $plainToken);

                    $this->persistTokenToTable('vet_registerations_temp', (int) $clinicRow->id, $tokenHash, $tokenExpiresAt);

                    $room = ChatRoom::create([
                        'user_id'         => $clinicRow->id,
                        'chat_room_token' => 'room_' . Str::uuid()->toString(),
                        'name'            => $roomTitle ?? ('New chat - ' . now()->format('d M Y H:i')),
                    ]);
                });

                $clinicId = (int) $clinicRow->id;

                $doctors = Doctor::where('vet_registeration_id', $clinicId)
                    ->get()
                    ->map(function (Doctor $doctor) {
                        return [
                            'id'                   => $doctor->id,
                            'name'                 => $doctor->doctor_name,
                            'email'                => $doctor->doctor_email,
                            'mobile'               => $doctor->doctor_mobile,
                            'license'              => $doctor->doctor_license,
                            'image'                => $doctor->doctor_image,
                            'toggle_availability'  => $doctor->toggle_availability,
                            'consultation_price'   => $doctor->doctors_price,
                        ];
                    })
                    ->values()
                    ->toArray();

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
                    'doctors'    => $doctors,
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
                    'doctors'                     => $doctors,
                ]);

                return response()->json($response, 200);
            }

            if ($doctorPasswordOk) {
                $clinicId = (int) ($doctorRow->vet_registeration_id ?? 0);
                $clinicForDoctor = $clinicId > 0
                    ? DB::table('vet_registerations_temp')->where('id', $clinicId)->first()
                    : null;

                $doctors = Doctor::where('vet_registeration_id', $clinicId)
                    ->get()
                    ->map(function (Doctor $doctor) {
                        return [
                            'id'                   => $doctor->id,
                            'name'                 => $doctor->doctor_name,
                            'email'                => $doctor->doctor_email,
                            'mobile'               => $doctor->doctor_mobile,
                            'license'              => $doctor->doctor_license,
                            'image'                => $doctor->doctor_image,
                            'toggle_availability'  => $doctor->toggle_availability,
                            'consultation_price'   => $doctor->doctors_price,
                        ];
                    })
                    ->values()
                    ->toArray();

                DB::transaction(function () use (&$plainToken, &$room, $doctorRow, $roomTitle, $tokenExpiresAt) {
                    $plainToken = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $plainToken);

                    $this->persistTokenToTable('doctors', (int) $doctorRow->id, $tokenHash, $tokenExpiresAt);

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
                    'doctors'     => $doctors,
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
                    'doctors'                     => $doctors,
                ]);

                return response()->json($response, 200);
            }

            if ($receptionistPasswordOk) {
                $clinicId = (int) ($receptionistRow->vet_registeration_id ?? 0);
                $clinicRecord = $clinicId > 0
                    ? DB::table('vet_registerations_temp')->where('id', $clinicId)->first()
                    : null;

                $doctors = Doctor::where('vet_registeration_id', $clinicId)
                    ->get()
                    ->map(function (Doctor $doctor) {
                        return [
                            'id'                   => $doctor->id,
                            'name'                 => $doctor->doctor_name,
                            'email'                => $doctor->doctor_email,
                            'mobile'               => $doctor->doctor_mobile,
                            'license'              => $doctor->doctor_license,
                            'image'                => $doctor->doctor_image,
                            'toggle_availability'  => $doctor->toggle_availability,
                            'consultation_price'   => $doctor->doctors_price,
                        ];
                    })
                    ->values()
                    ->toArray();

                DB::transaction(function () use (&$plainToken, &$room, $receptionistRow, $roomTitle, $tokenExpiresAt) {
                    $plainToken = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $plainToken);

                    $this->persistTokenToTable('receptionists', (int) $receptionistRow->id, $tokenHash, $tokenExpiresAt);

                    $room = ChatRoom::create([
                        'user_id'         => $receptionistRow->id,
                        'chat_room_token' => 'room_' . Str::uuid()->toString(),
                        'name'            => $roomTitle ?? ('New chat - ' . now()->format('d M Y H:i')),
                    ]);
                });

                $receptionistData = $receptionistRow->toArray();
                $receptionistData['role'] = 'receptionist';
                $receptionistData['clinic_id'] = $clinicId ?: null;
                $receptionistData['vet_registeration_id'] = $clinicId ?: null;
                $receptionistData['vet_registerations_temp_id'] = $clinicId ?: null;
                $receptionistData['email'] = $receptionistRow->email;
                if ($clinicRecord) {
                    $receptionistData['clinic_profile'] = $clinicRecord->clinic_profile ?? ($clinicRecord->name ?? null);
                }

                $receptionistId = (int) $receptionistRow->id;

                $response = [
                    'message'    => 'Login successful',
                    'role'       => 'receptionist',
                    'email'      => $receptionistRow->email,
                    'token'      => $plainToken,
                    'token_type' => 'Bearer',
                    'chat_room'  => [
                        'id'    => $room->id,
                        'token' => $room->chat_room_token,
                        'name'  => $room->name,
                    ],
                    'user'        => $receptionistData,
                    'user_id'     => $receptionistId,
                    'receptionist_id' => $receptionistId,
                    'clinic_id'   => $clinicId ?: null,
                    'vet_id'      => $clinicId ?: null,
                    'vet_registeration_id'       => $clinicId ?: null,
                    'vet_registerations_temp_id' => $clinicId ?: null,
                    'doctors'     => $doctors,
                ];

                session([
                    'user_id'                     => $receptionistId,
                    'receptionist_id'             => $receptionistId,
                    'clinic_id'                   => $clinicId ?: null,
                    'role'                        => 'receptionist',
                    'token'                       => $plainToken,
                    'token_type'                  => 'Bearer',
                    'chat_room'                   => $response['chat_room'],
                    'user'                        => $receptionistData,
                    'auth_full'                   => $response,
                    'vet_id'                      => $clinicId ?: null,
                    'vet_registeration_id'        => $clinicId ?: null,
                    'vet_registerations_temp_id'  => $clinicId ?: null,
                    'doctors'                     => $doctors,
                ]);

                return response()->json($response, 200);
            }

            if ($clinicRow || $doctorRow || $receptionistRow) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            return response()->json(['message' => 'Doctor or receptionist not found'], 404);
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
    $tokenExpiresAt = now()->addDays(30);

    if ($role === 'pet') {
        // 🔹 Search in users table
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        DB::transaction(function () use (&$plainToken, &$room, $user, $roomTitle, $tokenExpiresAt) {
            $plainToken = bin2hex(random_bytes(32));
            $this->assignTokenToModel($user, hash('sha256', $plainToken), $tokenExpiresAt);

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
        $tokenExpiresAt = now()->addDays(30);

        if ($role === 'pet') {
            // 🔹 Pet users table check
            $user = User::where('email', $request->email)
                        ->where('google_token', $request->google_token)
                        ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Google credentials',
                ], 401);
            }

            // ✅ Laravel login
            Auth::login($user);

            DB::transaction(function () use (&$plainToken, &$room, $user, $request, $tokenExpiresAt) {
                $plainToken = bin2hex(random_bytes(32));
                $this->assignTokenToModel($user, hash('sha256', $plainToken), $tokenExpiresAt);

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
                    'message' => 'Invalid Google credentials',
                ], 401);
            }

            DB::transaction(function () use (&$plainToken, &$room, $tempVet, $request, $tokenExpiresAt) {
                $plainToken = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $plainToken);

                $this->persistTokenToTable('vet_registerations_temp', (int) $tempVet->id, $tokenHash, $tokenExpiresAt);

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
        $tokenExpiresAt = now()->addDays(30);

        DB::transaction(function () use (&$plainToken, &$room, $user, $request, $tokenExpiresAt) {
            // ✅ API token regenerate
            $plainToken = bin2hex(random_bytes(32));
            $this->assignTokenToModel($user, hash('sha256', $plainToken), $tokenExpiresAt);

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

        if ($this->hasTokenColumns($user->getTable())) {
            $user->api_token_hash = null; // invalidate
            $user->api_token_expires_at = null;
            $user->save();
        } else {
            Log::warning("Skipping logout token clear for {$user->getTable()}: columns missing");
        }

        return response()->json(['message' => 'Logged out']);
    }



    public function clinicPayments(Request $request, $clinicId = null)
    {
        $clinicId = $clinicId ?? $request->input('clinic_id');
        $vetId    = $request->input('vet_id') ?: $clinicId; // allow vet_id or fallback to clinic_id
        $status   = $request->input('status'); // optional: filter a specific status

        if ((!$clinicId && !$vetId) || (isset($clinicId) && (!is_numeric($clinicId) || (int) $clinicId <= 0))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing clinic_id/vet_id',
            ], 422);
        }

        $clinicId = $clinicId ? (int) $clinicId : null;
        $vetId    = $vetId ? (int) $vetId : null;

        $successfulStatuses = [
            'completed',
            'captured',
            'paid',
            'success',
            'successful',
            'settled',
        ];

        $baseQuery = Transaction::query()
            ->when($clinicId || $vetId, function ($q) use ($clinicId, $vetId) {
                $q->where(function ($sub) use ($clinicId, $vetId) {
                    if ($clinicId) {
                        $sub->orWhere('clinic_id', $clinicId);
                    }
                    if ($vetId) {
                        $sub->orWhereHas('doctor', function ($qq) use ($vetId) {
                            $qq->where('vet_registeration_id', $vetId);
                        });
                    }
                });
            });

        $transactionsQuery = clone $baseQuery;
        if ($status) {
            $transactionsQuery->where('status', $status);
        }

        // Payments count (respecting status filter if provided)
        $paymentsCount = (clone $transactionsQuery)->count();

        // Collected only for successful statuses (or the provided status if caller wants)
        $collectedQuery = clone $transactionsQuery;
        if (!$status) {
            $collectedQuery->whereIn('status', $successfulStatuses);
        }

        $totalPaise = (int) $collectedQuery->sum('amount_paise');
        $totalRupees = round($totalPaise / 100, 2, PHP_ROUND_HALF_UP);

        return response()->json([
            'success'         => true,
            'clinic_id'       => $clinicId,
            'vet_id'          => $vetId,
            'status_filter'   => $status ?: 'all',
            'payments'        => $paymentsCount,
            'total_paise'     => $totalPaise,
            'total_rupees'    => $totalRupees,
            'currency'        => 'INR',
            'transactions'    => $transactionsQuery->orderByDesc('created_at')->limit(300)->get(),
        ]);
    }

}
