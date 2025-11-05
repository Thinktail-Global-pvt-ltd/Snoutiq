<?php

namespace App\Http\Controllers;

use App\Models\LegacyQrRedirect;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\Request;

class VetLandingController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $vet = VetRegisterationTemp::where('slug', $slug)->firstOrFail();

        // Fallback counting: if no qr_counted flag, try to increment based on
        // explicit qr_i, or by matching target_url/clinic public_id.
        try {
            if ((string) $request->query('qr_counted') !== '1') {
                $qrI = trim((string) $request->query('qr_i', ''));
                if ($qrI !== '') {
                    LegacyQrRedirect::recordScanForIdentifier($qrI);
                } else {
                    $url = rtrim($request->url(), '/');
                    $redirect = LegacyQrRedirect::where('target_url', $url)
                        ->orWhere('target_url', $request->url())
                        ->first();
                    if ($redirect) {
                        $redirect->recordScan();
                    } else {
                        // As a last resort, bump using clinic public_id so QR scans still register.
                        if (! empty($vet->public_id)) {
                            LegacyQrRedirect::recordScanForIdentifier($vet->public_id);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('QR fallback count failed on landing', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);
        }

        $isDraft = $vet->status !== 'active';
        $hasClaimToken = $request->query('claim_token');
        $canClaim = $isDraft && $vet->claim_token && $hasClaimToken && hash_equals($vet->claim_token, (string) $hasClaimToken);

        // Doctors are only surfaced for active clinics
        $doctors = $isDraft ? collect() : $vet->doctors()->orderBy('doctor_name')->get();

        // helpers for view
        $phoneDigits = $vet->mobile ? preg_replace('/\D+/', '', $vet->mobile) : null;
        $clinicFee   = $isDraft ? null : ($vet->chat_price ?? 499); // adjust if you store clinic fee elsewhere
        $videoFee    = $isDraft ? null : ($vet->chat_price ?? 399);

        $mapQuery = ($vet->lat && $vet->lng)
            ? ($vet->lat.','.$vet->lng)
            : ($vet->formatted_address ?: ($vet->address ?: $vet->city));

        return view('vet.landing', [
            'vet' => $vet,
            'doctors' => $doctors,
            'phoneDigits' => $phoneDigits,
            'clinicFee' => $clinicFee,
            'videoFee' => $videoFee,
            'mapQuery' => $mapQuery,
            'isDraft' => $isDraft,
            'canClaim' => $canClaim,
            'publicUrl' => LegacyQrRedirect::scanUrlForPublicId($vet->public_id),
            'mapsEmbedKey' => config('services.google_maps.embed_key'),
        ]);
    }

    public function redirectByPublicId(Request $request, string $publicId)
    {
        // Always attempt to record a scan when arriving via the shortlink,
        // except when already coming from the legacy-qr controller (to avoid double counts).
        if ($request->query('via') !== 'legacy-qr') {
            LegacyQrRedirect::recordScanForIdentifier($publicId);
        }

        $vet = VetRegisterationTemp::where('public_id', $publicId)->firstOrFail();

        // Preserve incoming query and add tracking information.
        $params = $request->query();
        $params['qr_i'] = $params['qr_i'] ?? $publicId;
        // Mark as counted only if this shortlink route already incremented.
        if ($request->query('via') !== 'legacy-qr') {
            $params['qr_counted'] = '1';
        }

        $target = url('/vets/'.$vet->slug).($params ? ('?'.http_build_query($params)) : '');

        return redirect()->to($target, 301);
    }
}
