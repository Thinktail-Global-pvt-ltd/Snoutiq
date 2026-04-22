<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatServiceBooking;
use App\Models\Doctor;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatServiceBookingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'pet_id' => ['nullable', 'integer'],

            'clinic_name' => ['required', 'string', 'max:255'],
            'clinic_mobile' => ['nullable', 'string', 'max:32'],
            'clinic_email' => ['nullable', 'email', 'max:191'],
            'clinic_city' => ['nullable', 'string', 'max:120'],
            'clinic_pincode' => ['nullable', 'string', 'max:20'],
            'clinic_address' => ['nullable', 'string', 'max:500'],
            'clinic_lat' => ['nullable', 'numeric'],
            'clinic_lng' => ['nullable', 'numeric'],
            'clinic_place_id' => ['nullable', 'string', 'max:191'],

            'doctor_name' => ['required', 'string', 'max:255'],
            'doctor_mobile' => ['nullable', 'string', 'max:32'],
            'doctor_email' => ['nullable', 'email', 'max:191'],
            'doctor_license' => ['nullable', 'string', 'max:120'],
            'doctor_price' => ['nullable', 'numeric', 'min:0'],

            'chat_room_token' => ['nullable', 'string', 'max:120'],
            'context_token' => ['nullable', 'string', 'max:120'],
            'service_type' => ['nullable', 'string', 'max:50'],
            'appointment_date' => ['nullable', 'date'],
            'appointment_time' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $result = DB::transaction(function () use ($payload) {
            $clinicMobile = trim((string) ($payload['clinic_mobile'] ?? ''));
            if ($clinicMobile === '') {
                $clinicMobile = '0000000000';
            }

            $clinicEmail = $payload['clinic_email']
                ?? sprintf('chat-clinic-%s@snoutiq.local', substr(md5((string) ($payload['clinic_name'] ?? now()->timestamp)), 0, 12));

            $clinic = VetRegisterationTemp::create([
                'name' => $payload['clinic_name'],
                'mobile' => $clinicMobile,
                'email' => $clinicEmail,
                'city' => $payload['clinic_city'] ?? null,
                'pincode' => $payload['clinic_pincode'] ?? null,
                'address' => $payload['clinic_address'] ?? null,
                'formatted_address' => $payload['clinic_address'] ?? null,
                'lat' => $payload['clinic_lat'] ?? null,
                'lng' => $payload['clinic_lng'] ?? null,
                'place_id' => $payload['clinic_place_id'] ?? null,
                'business_status' => 'OPERATIONAL',
                'open_now' => null,
                'status' => 'draft',
            ]);

            $doctorMobile = trim((string) ($payload['doctor_mobile'] ?? ''));
            if ($doctorMobile === '') {
                $doctorMobile = '0000000000';
            }

            $doctor = Doctor::create([
                'vet_registeration_id' => $clinic->id,
                'doctor_name' => $payload['doctor_name'],
                'doctor_mobile' => $doctorMobile,
                'doctor_email' => $payload['doctor_email'] ?? null,
                'doctor_license' => $payload['doctor_license'] ?? null,
                'toggle_availability' => 1,
                'doctor_status' => 'active',
                'doctors_price' => $payload['doctor_price'] ?? null,
            ]);

            $booking = ChatServiceBooking::create([
                'user_id' => $payload['user_id'] ?? null,
                'pet_id' => $payload['pet_id'] ?? null,
                'vet_registeration_id' => $clinic->id,
                'doctor_id' => $doctor->id,
                'chat_room_token' => $payload['chat_room_token'] ?? null,
                'context_token' => $payload['context_token'] ?? null,
                'service_type' => $payload['service_type'] ?? 'in_clinic',
                'appointment_date' => $payload['appointment_date'] ?? null,
                'appointment_time' => $payload['appointment_time'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'source_payload' => $payload,
            ]);

            return [
                'clinic' => $clinic,
                'doctor' => $doctor,
                'booking' => $booking,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Clinic, doctor, and chat service booking created successfully.',
            'data' => [
                'clinic_id' => $result['clinic']->id,
                'doctor_id' => $result['doctor']->id,
                'chat_service_booking_id' => $result['booking']->id,
                'chat_room_token' => $result['booking']->chat_room_token,
                'context_token' => $result['booking']->context_token,
            ],
        ], 201);
    }
}
