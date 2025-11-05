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

        if (! empty($redirect->target_url)) {
            return redirect()->away($redirect->target_url);
        }

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

        $targetUrl = route('clinics.shortlink', array_merge(
            ['publicId' => $publicId],
            $query
        ));

        return redirect()->to($targetUrl);
    }
}
