<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VetRegisterationTemp; // assuming Vet model exists
use Illuminate\Support\Str;

class VetLandingController extends Controller
{
  public function show(string $slug)
    {
        // 1) Try proper slug match
        $vet = VetRegisterationTemp::where('slug', $slug)->first();

        // 2) Fallback: match by slugified name (for old rows without slug)
        if (!$vet) {
            $candidate = Str::of($slug)->replace('-', ' ')->__toString();
            $vet = VetRegisterationTemp::whereRaw('LOWER(name) = ?', [strtolower($candidate)])
                    ->orWhere('name', 'LIKE', $candidate.'%')
                    ->first();

            // If found but slug empty, persist it for next time
            if ($vet && empty($vet->slug)) {
                $vet->slug = Str::slug($vet->name);
                // ensure unique
                $base = $vet->slug; $i = 1;
                while (VetRegisterationTemp::where('slug', $vet->slug)->where('id', '!=', $vet->id)->exists()) {
                    $vet->slug = $base.'-'.$i++;
                }
                $vet->save();
            }
        }

        abort_if(!$vet, 404);

        // Derived fields for the view
        $phone = $vet->mobile;
        $phoneDigits = $phone ? preg_replace('/\D+/', '', $phone) : null;

        $clinicFee = $vet->chat_price ?? 499;  // using chat_price as clinic consult for now
        $videoFee  = $vet->chat_price ?? 399;

        $mapQuery = ($vet->lat && $vet->lng)
            ? ($vet->lat.','.$vet->lng)
            : ($vet->formatted_address ?: ($vet->address ?: $vet->city));
           // dd($vet);

        return view('vet.landing', [
            'vet'         => $vet,
            'phoneDigits' => $phoneDigits,
            'clinicFee'   => $clinicFee,
            'videoFee'    => $videoFee,
            'mapQuery'    => $mapQuery,
        ]);
    }

}
