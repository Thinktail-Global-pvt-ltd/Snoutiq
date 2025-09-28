<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $patientId = auth()->id() ?? 101;

        // Nearby doctors (replace with your real query)
        $nearbyDoctors = collect();
        try {
            if (class_exists(\App\Models\Doctor::class)) {
                $nearbyDoctors = \App\Models\Doctor::select('id','name')
                    ->where('is_online', 1)
                    ->take(20)
                    ->get();
            }
        } catch (\Throwable $e) {}

        if ($nearbyDoctors->isEmpty()) {
            $nearbyDoctors = collect([
                (object)['id' => 501, 'name' => 'Dr. Demo One'],
                (object)['id' => 502, 'name' => 'Dr. Demo Two'],
            ]);
        }

        // Socket URL from config/env
        $socketUrl = config('app.socket_server_url') ?? env('SOCKET_SERVER_URL', 'http://localhost:3000');

        // Pre-map doctors for JS (cleaner than mapping inside Blade)
        $nearbyDoctorsForJs = $nearbyDoctors->map(fn($d) => ['id'=>$d->id, 'name'=>$d->name])->values();

        return view('chat', [
            'patientId'           => $patientId,
            'nearbyDoctors'       => $nearbyDoctors,
            'nearbyDoctorsForJs'  => $nearbyDoctorsForJs,
            'socketUrl'           => $socketUrl,
        ]);
    }
}

