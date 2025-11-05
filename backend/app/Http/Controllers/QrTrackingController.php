<?php

namespace App\Http\Controllers;

use App\Models\LegacyQrRedirect;
use Illuminate\Http\Request;

class QrTrackingController extends Controller
{
    /**
     * 1x1 GIF beacon that increments scan count for the given identifier.
     * Query: /qr/beacon.gif?i={publicIdOrCode}
     */
    public function beacon(Request $request)
    {
        $identifier = (string) $request->query('i', '');
        if ($identifier !== '') {
            LegacyQrRedirect::recordScanForIdentifier($identifier);
        }

        // Transparent 1x1 GIF
        $gif = base64_decode(
            'R0lGODlhAQABAPAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=='
        );

        return response($gif, 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0')
            ->header('X-QR-Tracked', $identifier !== '' ? '1' : '0');
    }
}

