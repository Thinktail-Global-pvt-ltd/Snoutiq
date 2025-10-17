<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GeoController extends Controller
{
    /**
     * GET /api/geo/pincodes
     *
     * Query params:
     *  - city=Gurugram (default)
     *  - active=1|0 (default: 1)
     *  - pincode=122001 (optional exact filter)
     *  - search=DLF (optional fuzzy match on pincode/label)
     *  - limit=500 (default)
     *
     * Response:
     *  {
     *    city: "Gurugram",
     *    count: <int>,
     *    pincodes: [{ code, name, lat, lon }]
     *  }
     */
    public function pincodes(Request $r)
    {
        $city   = (string) $r->query('city', 'Gurugram');
        $limit  = max(1, (int) $r->query('limit', 500));
        $pin    = trim((string) $r->query('pincode', ''));
        $search = trim((string) $r->query('search', ''));

        // active can be "1","0","true","false"
        $activeParam = $r->query('active', '1');
        $active = null;
        if ($activeParam !== null) {
            $active = in_array(strtolower((string)$activeParam), ['1','true'], true) ? 1
                    : (in_array(strtolower((string)$activeParam), ['0','false'], true) ? 0 : null);
        }

        $q = DB::table('geo_pincodes')->where('city', $city);
        if (!is_null($active)) {
            $q->where('active', $active);
        }
        if ($pin !== '') {
            $q->where('pincode', $pin);
        }
        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('pincode', 'like', "%{$search}%")
                  ->orWhere('label', 'like', "%{$search}%");
            });
        }

        $rows = $q->orderBy('pincode')
                  ->limit($limit)
                  ->get([
                      'pincode as code',
                      'label as name',
                      'lat',
                      'lon',
                  ]);

        return response()->json([
            'city'     => $city,
            'count'    => $rows->count(),
            'pincodes' => $rows,
        ]);
    }

    public function strips()
{
    $rows = \App\Models\GeoStrip::query()
        ->where('active', 1)
        ->orderBy('id')
        ->get(['id','name','min_lon','max_lon']);
    return response()->json(['strips' => $rows]);
}

}
