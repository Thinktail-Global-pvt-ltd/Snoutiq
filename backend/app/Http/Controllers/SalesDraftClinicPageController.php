<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SalesDraftClinicPageController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('backend.clinics.draft-create', [
            'apiEndpoint' => url('/api/clinics/drafts'),
            'defaultExpiryDays' => 60,
        ]);
    }
}
