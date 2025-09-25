<?php

namespace App\Http\Controllers;

use App\Models\VetRegisterationTemp;
use Illuminate\Support\Str;

class VetLandingController extends Controller
{
    public function show(string $slug)
    {
        $vet = VetRegisterationTemp::where('slug', $slug)->firstOrFail();

        // doctors linked by FK
        $doctors = $vet->doctors()->orderBy('doctor_name')->get();
      //  dd($doctors);

        // helpers for view
        $phoneDigits = $vet->mobile ? preg_replace('/\D+/', '', $vet->mobile) : null;
        $clinicFee   = $vet->chat_price ?? 499; // adjust if you store clinic fee elsewhere
        $videoFee    = $vet->chat_price ?? 399;

        $mapQuery = ($vet->lat && $vet->lng)
            ? ($vet->lat.','.$vet->lng)
            : ($vet->formatted_address ?: ($vet->address ?: $vet->city));

        return view('vet.landing', compact('vet','doctors','phoneDigits','clinicFee','videoFee','mapQuery'));
    }
}
