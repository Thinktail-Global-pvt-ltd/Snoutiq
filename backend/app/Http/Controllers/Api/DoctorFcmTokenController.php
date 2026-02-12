<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorFcmToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class DoctorFcmTokenController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'fcm_token' => ['required', 'string', 'max:500'],
        ]);

        $token = trim(trim($validated['fcm_token']), "\"'");
        if ($token === '' || strlen($token) < 80 || preg_match('/\\s/', $token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid FCM token',
            ], 422);
        }

        try {
            DoctorFcmToken::where('token', $token)
                ->where('doctor_id', '!=', $validated['doctor_id'])
                ->delete();

            $record = DoctorFcmToken::updateOrCreate(
                ['doctor_id' => $validated['doctor_id']],
                ['token' => $token]
            );

            return response()->json([
                'success' => true,
                'id' => $record->id,
                'doctor_id' => $record->doctor_id,
            ]);
        } catch (Throwable $e) {
            Log::error('doctor.fcm_token.save_failed', [
                'doctor_id' => $validated['doctor_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save FCM token',
            ], 500);
        }
    }
}
