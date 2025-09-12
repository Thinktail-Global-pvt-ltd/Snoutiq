<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\AgoraTokenService;

class AgoraController extends Controller
{
    protected $agora;

    public function __construct(AgoraTokenService $agora)
    {
        $this->agora = $agora;
    }

    
}
