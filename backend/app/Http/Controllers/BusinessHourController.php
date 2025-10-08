<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VetRegisterationTemp;
use App\Models\BusinessHour;
use App\Models\Doctor;

class BusinessHourController extends Controller
{
    public function save(Request $request)
    {
        // On-demand hard dump for debugging from the browser:
        // POST to /clinic/hours/save?dd=1 (or add dd=1 to the form action) to see the exact payload
        if ((string)$request->query('dd') === '1') {
            dd([
                'url'               => $request->fullUrl(),
                'method'            => $request->method(),
                'request_all'       => $request->all(),
                'headers'           => $request->headers->all(),
                'cookies'           => $request->cookies->all(),
                'has_csrf_token'    => $request->has('_token'),
                'session_user_id'   => session('user_id'),
                'auth_user_id'      => optional(auth()->user())->id,
            ]);
        }
        try {
            $data = $request->validate([
                'vet_id'             => 'nullable|integer',
                'clinic_slug'        => 'nullable|string',
                'open_time'          => 'required|array',
                'open_time.*'        => 'nullable|date_format:H:i',
                'close_time'         => 'required|array',
                'close_time.*'       => 'nullable|date_format:H:i',
                'closed'             => 'nullable|array',
                'closed.*'           => 'nullable|boolean',
                'user_id'            => 'nullable|integer',
            ]);

            // Resolve clinic: prefer explicit vet_id, then slug, then request/SESSION user -> doctor/clinic
            $clinic = null;
            $requestUserId = $data['vet_id'] ? null : ($data['user_id'] ?? null);
            if (!empty($data['vet_id'])) {
                $clinic = VetRegisterationTemp::find($data['vet_id']);
            }
            if (!$clinic && !empty($data['clinic_slug'])) {
                $clinic = VetRegisterationTemp::where('slug', $data['clinic_slug'])->first();
            }
            // From request user_id (like Services flow)
            if (!$clinic && $requestUserId) {
                $doctor = Doctor::where('user_id', $requestUserId)->orWhere('id', $requestUserId)->first();
                if ($doctor && $doctor->vet_registeration_id) {
                    $clinic = VetRegisterationTemp::find($doctor->vet_registeration_id);
                }
                if (!$clinic) {
                    $clinic = VetRegisterationTemp::where('employee_id', (string)$requestUserId)->first();
                }
            }
            if (!$clinic) {
                // Try session user_id -> Doctor primary key mapping
                $sessionUserId = session('user_id');
                if ($sessionUserId) {
                    $doctor = Doctor::where('user_id', $sessionUserId)->orWhere('id', $sessionUserId)->first();
                    if ($doctor && $doctor->vet_registeration_id) {
                        $clinic = VetRegisterationTemp::find($doctor->vet_registeration_id);
                    }
                    // Fallback: clinic where employee_id equals session user id
                    if (!$clinic) {
                        $clinic = VetRegisterationTemp::where('employee_id', (string)$sessionUserId)->first();
                    }
                }
            }
            if (!$clinic) {
                // Try Laravel auth user -> match by email/phone/employee_id
                $user = auth()->user();
                if ($user) {
                    // Doctor by email/phone
                    $doctor = Doctor::where('doctor_email', $user->email)
                                    ->orWhere('doctor_mobile', $user->phone ?? null)
                                    ->first();
                    if ($doctor && $doctor->vet_registeration_id) {
                        $clinic = VetRegisterationTemp::find($doctor->vet_registeration_id);
                    }
                    // Clinic by email/phone/employee_id
                    if (!$clinic) {
                        $clinic = VetRegisterationTemp::where('email', $user->email)
                                    ->orWhere('mobile', $user->phone ?? null)
                                    ->orWhere('employee_id', (string)$user->id)
                                    ->first();
                    }
                }
            }

            if (!$clinic) {
                $context = $this->dbgContext($request, $data, $clinic);
                if ($request->wantsJson() || (string)$request->query('debug') === '1') {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Unable to resolve clinic',
                        'context' => $context,
                    ], 422);
                }
                return back()
                    ->withInput()
                    ->with('error', 'Unable to resolve clinic. Provide vet_id or login as a doctor.')
                    ->with('error_context', $context);
            }

            \DB::transaction(function () use ($data, $clinic) {
                for ($day = 1; $day <= 7; $day++) {
                    $isClosed = isset($data['closed'][$day]) && (int)$data['closed'][$day] === 1;
                    $open = $data['open_time'][$day] ?? null; if ($open === '') $open = null;
                    $close= $data['close_time'][$day] ?? null; if ($close === '') $close = null;
                    BusinessHour::updateOrCreate(
                        [
                            'vet_registeration_id' => $clinic->id,
                            'day_of_week'          => $day,
                        ],
                        [
                            'open_time' => $isClosed ? null : $open,
                            'close_time'=> $isClosed ? null : $close,
                            'closed'    => $isClosed,
                        ]
                    );
                }
            });

            if ($request->wantsJson()) {
                return response()->json(['status'=>'success','message'=>'Business hours saved','clinic_id'=>$clinic->id]);
            }
            return back()->with('success', 'Business hours saved')
                         ->with('debug_context', [
                             'clinic_id' => $clinic->id,
                             'user_id'   => $data['user_id'] ?? session('user_id') ?? optional(auth()->user())->id,
                             'vet_id'    => $data['vet_id'] ?? null,
                         ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson() || (string)$request->query('debug') === '1') {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $e->errors(),
                ], 422);
            }
            return back()->withErrors($e->errors())->withInput()
                        ->with('error', 'Validation failed')
                        ->with('error_context', ['errors' => $e->errors()]);
        } catch (\Throwable $e) {
            $context = $this->dbgContext($request, $request->all(), isset($clinic)?$clinic:null);
            \Log::error('clinic.hours.save.failed', array_merge($context, [
                'exception' => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ]));
            if ($request->wantsJson() || (string)$request->query('debug') === '1') {
                return response()->json([
                    'status'    => 'error',
                    'message'   => 'Save failed',
                    'exception' => $e->getMessage(),
                    'context'   => $context,
                ], 500);
            }
            return back()->withInput()
                         ->with('error', 'Could not save business hours.')
                         ->with('error_context', array_merge($context, [
                             'exception' => $e->getMessage(),
                             'file'      => $e->getFile(),
                             'line'      => $e->getLine(),
                         ]));
        }
    }

    private function dbgContext(Request $request, $data, $clinic)
    {
        return [
            'vet_id'            => $data['vet_id'] ?? null,
            'clinic_slug'       => $data['clinic_slug'] ?? null,
            'resolved_clinic_id'=> optional($clinic)->id,
            'request_user_id'   => $data['user_id'] ?? null,
            'session_user_id'   => session('user_id'),
            'auth_user_id'      => optional(auth()->user())->id,
            'has_open_time'     => isset($data['open_time']) ? array_keys((array)$data['open_time']) : [],
            'has_close_time'    => isset($data['close_time'])? array_keys((array)$data['close_time']) : [],
            'has_closed'        => isset($data['closed']) ? array_keys((array)$data['closed']) : [],
        ];
    }
}
