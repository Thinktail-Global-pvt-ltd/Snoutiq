<?php

namespace App\Http\Controllers;

use App\Models\LegacyQrRedirect;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LegacyQrRedirectController extends Controller
{
    public function __invoke(Request $request, string $code)
    {
        $redirect = LegacyQrRedirect::where('code', $code)->first();

        if (! $redirect) {
            $redirect = LegacyQrRedirect::findByPublicId($code);
        }

        if (! $redirect) {
            abort(404);
        }

        return $this->handleRedirect($request, $redirect);
    }

    public function handleRedirect(Request $request, LegacyQrRedirect $redirect): RedirectResponse
    {
        $redirect->recordScan();

        $publicId = $redirect->public_id;

        if (! $publicId && $redirect->clinic_id) {
            $publicId = VetRegisterationTemp::where('id', $redirect->clinic_id)->value('public_id');
        }

        if (! $publicId && $redirect->legacy_url) {
            // try to extract a public_id from legacy url if it contains /c/{id}
            if (preg_match('~/c/([0-9A-Z]+)~', $redirect->legacy_url, $matches)) {
                $publicId = $matches[1];
            }
        }

        if (! $publicId) {
            abort(404);
        }

        $query = collect($request->query())
            ->except('via')
            ->toArray();

        $query['via'] = 'legacy-qr';
        // include pixel tracking identifier so the clinic page can fire a beacon
        if (! isset($query['qr_i'])) {
            $query['qr_i'] = $publicId;
        }

        // If a custom target URL is configured, append our tracking query and exit.
        if (! empty($redirect->target_url)) {
            $glue = str_contains($redirect->target_url, '?') ? '&' : '?';
            $url = $redirect->target_url.$glue.http_build_query($query);
            return redirect()->away($url);
        }

        $targetUrl = route('clinics.shortlink', array_merge(
            ['publicId' => $publicId],
            $query
        ));

        return redirect()->to($targetUrl);
    }
}
