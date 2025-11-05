<?php

namespace App\Http\Controllers;

use App\Models\LegacyQrRedirect;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class VetLandingController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $vet = VetRegisterationTemp::where('slug', $slug)->firstOrFail();

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
            'publicUrl' => url('c/'.$vet->public_id),
            'mapsEmbedKey' => config('services.google_maps.embed_key'),
        ]);
    }

    public function redirectByPublicId(Request $request, string $publicId)
    {
        if ($request->query('via') !== 'legacy-qr') {
            $this->recordLegacyScan($request, $publicId);
        }

        $vet = VetRegisterationTemp::where('public_id', $publicId)->firstOrFail();

        $target = url('/vets/'.$vet->slug);
        $query = $request->getQueryString();

        if ($query) {
            $target .= '?'.$query;
        }

        return redirect()->to($target, 301);
    }

    private function recordLegacyScan(Request $request, string $publicId): void
    {
        $redirect = LegacyQrRedirect::where('public_id', $publicId)->first();

        if (! $redirect) {
            return;
        }

        $bridgeRequest = SymfonyRequest::create(
            '/backend/legacy-qr/'.$redirect->code,
            'GET',
            ['via' => 'bridge'],
            [],
            [],
            [
                'HTTP_HOST' => $request->getHttpHost(),
                'HTTPS' => $request->isSecure() ? 'on' : 'off',
            ]
        );

        try {
            app(LegacyQrRedirectController::class)->__invoke(Request::createFromBase($bridgeRequest), $redirect->code);
        } catch (\Throwable $exception) {
            $redirect->recordScan();
        }
    }
}
