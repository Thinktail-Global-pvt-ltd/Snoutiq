<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RecordingUploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'call_id'   => 'nullable|string|max:64',
            'doctor_id' => 'nullable|integer',
            'patient_id' => 'nullable|integer',
            'recording' => 'required|file|mimetypes:video/webm,video/mp4,video/quicktime,video/x-matroska,audio/webm',
        ]);

        $file = $data['recording'];
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'webm';
        $filename = sprintf('%s_%s.%s', now()->format('YmdHis'), Str::uuid(), $extension);

        $path = $file->storeAs('call-recordings', $filename, 'public');

        return response()->json([
            'success'   => true,
            'path'      => $path,
            'url'       => Storage::disk('public')->url($path),
            'call_id'   => $data['call_id'] ?? null,
            'doctor_id' => $data['doctor_id'] ?? null,
            'patient_id'=> $data['patient_id'] ?? null,
        ], 201);
    }
}
