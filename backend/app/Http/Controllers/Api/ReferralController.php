<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\DownloadReferralMail;
use App\Models\User;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class ReferralController extends Controller
{
    public function sendDownloadLink(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:filter', 'max:255'],
            'phone' => ['nullable', 'string', 'max:25'],
            'vet_slug' => ['nullable', 'string', 'max:255'],
        ]);

        $email = strtolower($validated['email']);
        $user = User::firstOrNew(['email' => $email]);
        $hasRoleColumn = $this->usersTableHasRoleColumn();
        $hasVetSlugColumn = $this->usersTableHasLastVetSlugColumn();
        $hasVetIdColumn = $this->usersTableHasLastVetIdColumn();

        if (! $user->exists) {
            $user->name = $validated['name'];
            if ($hasRoleColumn && ! $user->role) {
                $user->role = 'pet_owner';
            }
        } else {
            if (! $user->name) {
                $user->name = $validated['name'];
            }
            if ($hasRoleColumn && ! $user->role) {
                $user->role = 'pet_owner';
            }
        }

        if (! empty($validated['phone'])) {
            $user->phone = $validated['phone'];
        }

        $user->referral_code = $this->generateUniqueReferralCode($user->id);

        $vetName = null;
        $vetSlug = $validated['vet_slug'] ?? null;
        $clinic = null;
        if ($vetSlug) {
            $clinic = VetRegisterationTemp::where('slug', $vetSlug)->first();
            if ($clinic) {
                $vetName = $clinic->name;
                if ($hasVetSlugColumn) {
                    $user->last_vet_slug = $clinic->slug;
                }
                if ($hasVetIdColumn) {
                    $user->last_vet_id = $clinic->id;
                }
            }
        }

        $user->save();

        try {
            Mail::to($user->email)->send(new DownloadReferralMail($user, $user->referral_code, $vetName, $vetSlug));
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Unable to send referral email right now. Please try again later.',
            ], 500);
        }

        return response()->json([
            'message' => 'Referral code sent successfully.',
            'referral_code' => $user->referral_code,
        ], 201);
    }

    public function showByCode(Request $request, string $code)
    {
        $lookup = trim($code);

        if ($lookup === '') {
            return response()->json([
                'message' => 'Referral code is required.',
            ], 422);
        }

        $user = User::where('referral_code', $lookup)->first();

        if (! $user) {
            return response()->json([
                'message' => 'Referral code not found.',
            ], 404);
        }

        $clinicData = null;
        $doctors = [];

        $clinicQuery = null;
        if (! empty($user->last_vet_id)) {
            $clinicQuery = VetRegisterationTemp::query()->where('id', $user->last_vet_id);
        } elseif (! empty($user->last_vet_slug)) {
            $clinicQuery = VetRegisterationTemp::query()->where('slug', $user->last_vet_slug);
        }

        if ($clinicQuery) {
            $clinic = $clinicQuery->with(['doctors' => function ($query) {
                $query->orderBy('doctor_name');
            }])->first();

            if ($clinic) {
                $clinicData = [
                    'id' => $clinic->id,
                    'name' => $clinic->name,
                    'slug' => $clinic->slug,
                    'city' => $clinic->city,
                    'address' => $clinic->formatted_address ?? $clinic->address,
                    'phone' => $clinic->mobile,
                ];

                $doctors = $clinic->doctors->map(function ($doctor) {
                    return [
                        'id' => $doctor->id,
                        'name' => $doctor->doctor_name,
                        'email' => $doctor->doctor_email,
                        'phone' => $doctor->doctor_mobile,
                        'license' => $doctor->doctor_license,
                        'image' => $doctor->doctor_image,
                        'price' => $doctor->doctors_price,
                    ];
                })->values()->all();
            }
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'referral_code' => $user->referral_code,
                'last_vet_slug' => $user->last_vet_slug,
                'last_vet_id' => $user->last_vet_id,
            ],
            'clinic' => $clinicData,
            'doctors' => $doctors,
        ]);
    }

    protected function generateUniqueReferralCode(?int $ignoreUserId = null): string
    {
        $attempts = 0;
        $maxAttempts = 5;

        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $exists = User::where('referral_code', $code)
                ->when($ignoreUserId, fn ($q) => $q->where('id', '!=', $ignoreUserId))
                ->exists();

            if (! $exists) {
                return $code;
            }
            $attempts++;
        } while ($attempts < $maxAttempts);

        throw new \RuntimeException('Unable to generate a unique referral code at the moment.');
    }

    protected function usersTableHasRoleColumn(): bool
    {
        static $hasRole = null;
        if ($hasRole === null) {
            $hasRole = Schema::hasColumn('users', 'role');
        }

        return $hasRole;
    }

    protected function usersTableHasLastVetSlugColumn(): bool
    {
        static $hasColumn = null;
        if ($hasColumn === null) {
            $hasColumn = Schema::hasColumn('users', 'last_vet_slug');
        }

        return $hasColumn;
    }

    protected function usersTableHasLastVetIdColumn(): bool
    {
        static $hasColumn = null;
        if ($hasColumn === null) {
            $hasColumn = Schema::hasColumn('users', 'last_vet_id');
        }

        return $hasColumn;
    }
}
