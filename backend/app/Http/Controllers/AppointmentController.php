<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VetRegisterationTemp;
use App\Models\Appointment;

class AppointmentController extends Controller
{
    public function store(Request $request)
    {
        try {
          //  dd($request->all());
            $data = $request->validate([
                'clinic_slug'       => 'required|string',
                'doctor_id'         => 'nullable|integer',
                'name'              => 'required|string|max:255',
                'mobile'            => 'required|string|max:20',
                'pet_name'          => 'nullable|string|max:255',
                'appointment_date'  => 'required|date',
                'appointment_time'  => 'required',
                'notes'             => 'nullable|string',
            ]);

            $clinic = VetRegisterationTemp::where('slug', $data['clinic_slug'])->first();
            if (!$clinic) {
                if (config('app.debug') || (string)$request->query('debug') === '1') {
                    dd('Clinic not found', ['slug' => $data['clinic_slug']]);
                }
                return back()->withInput()->with('error', 'Clinic not found for slug: '.$data['clinic_slug']);
            }

            // Prefer provided doctor_id; else derive from session/auth for logged-in doctor
            $resolvedDoctorId = $data['doctor_id']
                ?? (session('user_id') ? (int) session('user_id') : null)
                ?? (optional(auth()->user())->id ?? null);

            $appointment = Appointment::create([
                'vet_registeration_id' => $clinic->id,
                'doctor_id'            => $resolvedDoctorId,
                'name'                 => $data['name'],
                'mobile'               => $data['mobile'],
                'pet_name'             => $data['pet_name'] ?? null,
                'appointment_date'     => $data['appointment_date'],
                'appointment_time'     => $data['appointment_time'],
                'notes'                => $data['notes'] ?? null,
                'status'               => 'pending',
            ]);

            return redirect()->back()->with('success', 'Appointment booked! Reference #'.$appointment->id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if (config('app.debug') || (string)$request->query('debug') === '1') {
                dd('Validation failed', $e->errors());
            }
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            if (config('app.debug') || (string)$request->query('debug') === '1') {
                dd($e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }
            return back()->withInput()->with('error', 'Unable to save appointment right now');
        }
    }
}
