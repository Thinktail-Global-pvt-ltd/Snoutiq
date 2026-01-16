<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatRoom;
use App\Models\Doctor;
use App\Models\Receptionist;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RoleLoginController extends Controller
{
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

    private function normalizeRole(?string $role): string
    {
        $role = strtolower(trim((string) $role));
        $role = str_replace([' ', '-'], '_', $role);
        if ($role === 'clinicadmin') {
            return 'clinic_admin';
        }
        if ($role === 'reseptionist') {
            return 'receptionist';
        }
        return $role;
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'role'     => ['required', 'string'],
                'password' => ['required', 'string'],
                'email'    => ['nullable', 'string'],
                'login'    => ['nullable', 'string'],
            ]);

            $email = $request->input('email') ?? $request->input('login');
            $role = $this->normalizeRole($request->input('role'));
            $password = (string) $request->input('password', '');
            $roomTitle = $request->input('room_title');

            if (empty($email) || empty($role)) {
                return response()->json(['message' => 'Email/login or role missing'], 422);
            }

            $room = null;
            $plainToken = null;
            $tokenExpiresAt = now()->addDays(30);

            if (in_array($role, ['clinic_admin', 'admin'], true)) {
                $adminEmail = strtolower(trim((string) config('admin.email', 'admin@snoutiq.com')));
                if ($adminEmail === '') {
                    $adminEmail = 'admin@snoutiq.com';
                }

                $adminPassword = (string) config('admin.password', 'snoutiqvet');
                if ($adminPassword === '') {
                    $adminPassword = 'snoutiqvet';
                }

                if ($adminEmail && strtolower(trim((string) $email)) === $adminEmail) {
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
            }

            if ($role === 'pet') {
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
            }

            if ($role === 'clinic_admin') {
                $clinicRow = DB::table('vet_registerations_temp')
                    ->where('email', $email)
                    ->first();

                if (!$clinicRow) {
                    return response()->json(['message' => 'Clinic not found'], 404);
                }

                if (!$this->passwordMatches(data_get($clinicRow, 'password'), $password)) {
                    return response()->json(['message' => 'Invalid credentials'], 401);
                }

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

            if ($role === 'doctor') {
                $doctorRow = DB::table('doctors')
                    ->where('doctor_email', $email)
                    ->first();

                if (!$doctorRow) {
                    return response()->json(['message' => 'Doctor not found'], 404);
                }

                $doctorPasswordOk = $this->passwordMatches(
                    data_get($doctorRow, 'password') ?? data_get($doctorRow, 'doctor_password'),
                    $password
                );

                if (!$doctorPasswordOk) {
                    return response()->json(['message' => 'Invalid credentials'], 401);
                }

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

            if ($role === 'receptionist') {
                $receptionistRow = Receptionist::where('email', $email)->first();

                if (!$receptionistRow) {
                    return response()->json(['message' => 'Receptionist not found'], 404);
                }

                $receptionistPasswordOk = $this->passwordMatches(
                    data_get($receptionistRow, 'password') ?? data_get($receptionistRow, 'receptionist_password'),
                    $password
                );

                if (!$receptionistPasswordOk) {
                    return response()->json(['message' => 'Invalid credentials'], 401);
                }

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

            return response()->json(['message' => 'Invalid role'], 400);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed. Please try again later.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
