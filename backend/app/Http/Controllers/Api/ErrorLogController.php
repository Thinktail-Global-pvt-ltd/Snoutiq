<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ErrorLogController extends Controller
{
    /**
     * Store a new error log entry.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'page_name' => ['required', 'string', 'max:255'],
            'logs' => ['required'],
        ]);

        if (empty($data['doctor_id']) && empty($data['user_id'])) {
            throw ValidationException::withMessages([
                'doctor_id' => 'Either doctor_id or user_id is required.',
                'user_id' => 'Either doctor_id or user_id is required.',
            ]);
        }

        $logPayload = $data['logs'];
        if (is_array($logPayload) || is_object($logPayload)) {
            $encoded = json_encode($logPayload, JSON_PRETTY_PRINT);
            $logPayload = $encoded !== false ? $encoded : print_r($logPayload, true);
        } else {
            $logPayload = (string) $logPayload;
        }

        $log = ErrorLog::create([
            'doctor_id' => $data['doctor_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'page_name' => $data['page_name'],
            'logs' => $logPayload,
        ]);

        return response()->json([
            'success' => true,
            'data' => $log,
        ], 201);
    }
}
