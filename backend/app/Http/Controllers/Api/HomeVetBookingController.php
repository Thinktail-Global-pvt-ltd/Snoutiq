<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomeServiceRequiredByPet;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class HomeVetBookingController extends Controller
{
    public function stepOne(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'pet_type' => ['nullable', 'string', 'max:50'],
            'area' => ['nullable', 'string', 'max:120'],
            'reason_for_visit' => ['nullable', 'string', 'max:255'],
        ]);

        $normalizedPhone = $this->normalizePhone($data['phone']);
        if ($normalizedPhone === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Phone number is invalid.',
            ], 422);
        }

        $result = DB::transaction(function () use ($data, $normalizedPhone) {
            $user = User::query()
                ->where('phone', $normalizedPhone)
                ->orWhere('email', $normalizedPhone)
                ->first();

            if (! $user) {
                $user = new User();
            }

            $user->name = $data['name'];

            if (Schema::hasColumn('users', 'phone')) {
                $user->phone = $normalizedPhone;
            }
            if (Schema::hasColumn('users', 'email') && empty($user->email)) {
                $user->email = $normalizedPhone;
            }
            if (Schema::hasColumn('users', 'role') && empty($user->role)) {
                $user->role = 'pet_parent';
            }
            if (Schema::hasColumn('users', 'city') && ! empty($data['area'])) {
                $user->city = $data['area'];
            }
            if (Schema::hasColumn('users', 'password') && ! $user->exists) {
                $user->password = null;
            }

            $user->save();

            $booking = HomeServiceRequiredByPet::create([
                'user_id' => $user->id,
                'latest_completed_step' => 1,
                'owner_name' => $data['name'],
                'owner_phone' => $normalizedPhone,
                'pet_type' => $data['pet_type'] ?? null,
                'area' => $data['area'] ?? null,
                'reason_for_visit' => $data['reason_for_visit'] ?? null,
                'step1_completed_at' => now(),
            ]);

            return [
                'booking' => $booking,
                'user' => $user,
            ];
        });

        /** @var \App\Models\HomeServiceRequiredByPet $booking */
        $booking = $result['booking'];
        /** @var \App\Models\User $user */
        $user = $result['user'];

        return response()->json([
            'status' => 'success',
            'message' => 'Step 1 saved.',
            'data' => [
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'latest_completed_step' => $booking->latest_completed_step,
            ],
        ], 201);
    }

    public function stepTwo(Request $request)
    {
        $data = $request->validate([
            'booking_id' => ['required', 'integer', 'exists:home_service_required_by_pet,id'],
            'pet_name' => ['required', 'string', 'max:255'],
            'breed' => ['nullable', 'string', 'max:255'],
            'pet_dob' => ['nullable', 'date'],
            'pet_sex' => ['nullable', 'string', 'max:60'],
            'issue_description' => ['nullable', 'string'],
            'symptoms' => ['nullable', 'array'],
            'symptoms.*' => ['string', 'max:120'],
            'vaccination_status' => ['nullable', 'string', 'max:120'],
            'last_deworming' => ['nullable', 'string', 'max:120'],
            'past_illnesses_or_surgeries' => ['nullable', 'string'],
            'current_medications' => ['nullable', 'string'],
            'known_allergies' => ['nullable', 'string'],
            'vet_notes' => ['nullable', 'string'],
        ]);

        $result = DB::transaction(function () use ($data) {
            /** @var \App\Models\HomeServiceRequiredByPet $booking */
            $booking = HomeServiceRequiredByPet::query()
                ->lockForUpdate()
                ->findOrFail($data['booking_id']);

            /** @var \App\Models\User|null $user */
            $user = User::find($booking->user_id);
            if (! $user) {
                abort(422, 'Booking user not found.');
            }

            $pet = null;
            if (! empty($booking->pet_id)) {
                $pet = Pet::find($booking->pet_id);
            }
            if (! $pet) {
                $pet = new Pet();
            }

            $pet->user_id = $user->id;
            $pet->name = $data['pet_name'];
            $pet->breed = $data['breed'] ?? null;

            $petType = $booking->pet_type;
            if ($petType && Schema::hasColumn('pets', 'pet_type')) {
                $pet->pet_type = $petType;
            }
            if ($petType && Schema::hasColumn('pets', 'type')) {
                $pet->type = $petType;
            }

            if (! empty($data['pet_sex'])) {
                if (Schema::hasColumn('pets', 'pet_gender')) {
                    $pet->pet_gender = $data['pet_sex'];
                }
                if (Schema::hasColumn('pets', 'gender')) {
                    $pet->gender = $data['pet_sex'];
                }
            }

            if (! empty($data['pet_dob'])) {
                if (Schema::hasColumn('pets', 'pet_dob')) {
                    $pet->pet_dob = $data['pet_dob'];
                }
                if (Schema::hasColumn('pets', 'dob')) {
                    $pet->dob = $data['pet_dob'];
                }
            }

            if (! empty($data['issue_description']) && Schema::hasColumn('pets', 'reported_symptom')) {
                $pet->reported_symptom = $data['issue_description'];
            }
            if (! empty($data['past_illnesses_or_surgeries']) && Schema::hasColumn('pets', 'medical_history')) {
                $pet->medical_history = $data['past_illnesses_or_surgeries'];
            }
            if (! empty($data['vaccination_status']) && Schema::hasColumn('pets', 'vaccination_log')) {
                $pet->vaccination_log = $data['vaccination_status'];
            }

            $pet->save();

            $booking->fill([
                'pet_id' => $pet->id,
                'concern_description' => $data['issue_description'] ?? null,
                'symptoms' => $data['symptoms'] ?? null,
                'vaccination_status' => $data['vaccination_status'] ?? null,
                'last_deworming' => $data['last_deworming'] ?? null,
                'past_illnesses_or_surgeries' => $data['past_illnesses_or_surgeries'] ?? null,
                'current_medications' => $data['current_medications'] ?? null,
                'known_allergies' => $data['known_allergies'] ?? null,
                'vet_notes' => $data['vet_notes'] ?? null,
            ]);
            $booking->latest_completed_step = max((int) $booking->latest_completed_step, 2);
            $booking->step2_completed_at = $booking->step2_completed_at ?: now();
            $booking->save();

            return [
                'booking' => $booking,
                'pet' => $pet,
            ];
        });

        /** @var \App\Models\HomeServiceRequiredByPet $booking */
        $booking = $result['booking'];
        /** @var \App\Models\Pet $pet */
        $pet = $result['pet'];

        return response()->json([
            'status' => 'success',
            'message' => 'Step 2 saved.',
            'data' => [
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'pet_id' => $pet->id,
                'latest_completed_step' => $booking->latest_completed_step,
            ],
        ]);
    }

    public function stepThree(Request $request)
    {
        $data = $request->validate([
            'booking_id' => ['required', 'integer', 'exists:home_service_required_by_pet,id'],
            'payment_status' => ['nullable', Rule::in(['pending', 'paid', 'failed'])],
            'amount_payable' => ['nullable', 'numeric', 'min:0'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'payment_provider' => ['nullable', 'string', 'max:80'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'booking_reference' => ['nullable', 'string', 'max:60'],
            'confirm_booking' => ['nullable', 'boolean'],
        ]);

        /** @var \App\Models\HomeServiceRequiredByPet $booking */
        $booking = DB::transaction(function () use ($data) {
            /** @var \App\Models\HomeServiceRequiredByPet $booking */
            $booking = HomeServiceRequiredByPet::query()
                ->lockForUpdate()
                ->findOrFail($data['booking_id']);

            if (! empty($data['payment_status'])) {
                $booking->payment_status = $data['payment_status'];
            }
            if (array_key_exists('amount_payable', $data)) {
                $booking->amount_payable = $data['amount_payable'];
            }
            if (array_key_exists('amount_paid', $data)) {
                $booking->amount_paid = $data['amount_paid'];
            }
            if (! empty($data['payment_provider'])) {
                $booking->payment_provider = $data['payment_provider'];
            }
            if (! empty($data['payment_reference'])) {
                $booking->payment_reference = $data['payment_reference'];
            }

            $booking->booking_reference = $data['booking_reference']
                ?? $booking->booking_reference
                ?? $this->generateBookingReference();

            $confirmBooking = array_key_exists('confirm_booking', $data)
                ? (bool) $data['confirm_booking']
                : true;

            if ($confirmBooking) {
                $booking->latest_completed_step = max((int) $booking->latest_completed_step, 3);
                $booking->step3_completed_at = $booking->step3_completed_at ?: now();
                $booking->confirmed_at = now();
            }

            $booking->save();

            return $booking;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Step 3 saved.',
            'data' => [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->booking_reference,
                'latest_completed_step' => $booking->latest_completed_step,
                'payment_status' => $booking->payment_status,
            ],
        ]);
    }

    private function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        $phone = preg_replace('/\s+/', '', $phone);
        $phone = str_replace(['(', ')', '-'], '', $phone);

        if ($phone === '') {
            return '';
        }

        if (Str::startsWith($phone, '+')) {
            $digits = preg_replace('/\D+/', '', substr($phone, 1));
            return $digits ? '+' . $digits : '';
        }

        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    private function generateBookingReference(): string
    {
        return 'SNQ-HOME-' . strtoupper(Str::random(8));
    }
}
