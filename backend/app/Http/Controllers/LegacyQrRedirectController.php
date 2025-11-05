<?php

namespace App\Http\Controllers;

use App\Models\LegacyQrRedirect;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\Request;

class LegacyQrRedirectController extends Controller
{
    public function __invoke(Request $request, string $code)
    {
        $redirect = LegacyQrRedirect::where('code', $code)->first();

        if (! $redirect) {
            abort(404);
        }

        $now = now();
        $attributesToUpdate = [
            'last_scanned_at' => $now,
        ];

        if ($redirect->status !== 'active') {
            $attributesToUpdate['status'] = 'active';
        }

        $redirect->increment('scan_count', 1, $attributesToUpdate);
        $redirect->forceFill(array_merge(
            ['scan_count' => (int) $redirect->scan_count],
            $attributesToUpdate
        ));

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

        return redirect()->to(url('c/'.$publicId));
    }
}
