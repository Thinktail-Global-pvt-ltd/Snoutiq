<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentSubmissionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'clinic_id' => ['required', 'integer', 'exists:vet_registerations_temp,id'],
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'patient_name' => ['required', 'string', 'max:255'],
            'patient_phone' => ['nullable', 'string', 'max:20'],
            'pet_name' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'time_slot' => ['required', 'string', 'max:50'],
            'amount' => ['nullable', 'integer'],
            'currency' => ['nullable', 'string', 'max:10'],
            'razorpay_payment_id' => ['nullable', 'string', 'max:191'],
            'razorpay_order_id' => ['nullable', 'string', 'max:191'],
            'razorpay_signature' => ['nullable', 'string', 'max:255'],
        ]);

        $clinic = VetRegisterationTemp::findOrFail($validated['clinic_id']);
        $doctor = Doctor::findOrFail($validated['doctor_id']);
        $user = User::findOrFail($validated['user_id']);

        $notesPayload = [
            'clinic_name' => $clinic->name,
            'doctor_name' => $doctor->doctor_name ?? $doctor->name ?? null,
            'patient_user_id' => $user->id,
            'patient_email' => $user->email,
            'amount_paise' => $validated['amount'] ?? null,
            'currency' => $validated['currency'] ?? 'INR',
            'razorpay_payment_id' => $validated['razorpay_payment_id'] ?? null,
            'razorpay_order_id' => $validated['razorpay_order_id'] ?? null,
            'razorpay_signature' => $validated['razorpay_signature'] ?? null,
        ];

        $appointment = Appointment::create([
            'vet_registeration_id' => $clinic->id,
            'doctor_id' => $doctor->id,
            'name' => $validated['patient_name'],
            'mobile' => $validated['patient_phone'] ?? ($user->phone ?? null),
            'pet_name' => $validated['pet_name'] ?? null,
            'appointment_date' => $validated['date'],
            'appointment_time' => $validated['time_slot'],
            'status' => 'confirmed',
            'notes' => json_encode($notesPayload),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'appointment' => [
                    'id' => $appointment->id,
                    'clinic' => [
                        'id' => $clinic->id,
                        'name' => $clinic->name,
                    ],
                    'doctor' => [
                        'id' => $doctor->id,
                        'name' => $doctor->doctor_name ?? $doctor->name ?? null,
                    ],
                    'patient' => [
                        'user_id' => $user->id,
                        'name' => $appointment->name,
                        'phone' => $appointment->mobile,
                    ],
                    'date' => $appointment->appointment_date,
                    'time_slot' => $appointment->appointment_time,
                    'status' => $appointment->status,
                    'amount' => $validated['amount'] ?? null,
                    'currency' => $validated['currency'] ?? 'INR',
                ],
            ],
        ], 201);
    }
}
