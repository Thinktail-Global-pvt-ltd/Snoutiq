<?php

namespace App\Http\Controllers;

use App\Models\VetRegisterationTemp;
use Illuminate\Http\Response;

class ClinicMediaController extends Controller
{
    public function image(VetRegisterationTemp $clinic): Response
    {
        if (empty($clinic->clinic_image)) {
            abort(404);
        }

        return response($clinic->clinic_image, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'inline; filename="clinic-'.$clinic->id.'.jpg"',
        ]);
    }

    public function video(VetRegisterationTemp $clinic): Response
    {
        if (empty($clinic->clinic_video)) {
            abort(404);
        }

        return response($clinic->clinic_video, 200, [
            'Content-Type' => 'video/mp4',
            'Content-Disposition' => 'inline; filename="clinic-'.$clinic->id.'.mp4"',
        ]);
    }
}
