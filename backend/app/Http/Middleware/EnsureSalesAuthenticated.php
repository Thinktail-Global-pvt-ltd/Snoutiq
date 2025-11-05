<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSalesAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('sales_authenticated')) {
            return redirect()->route('sales.login');
        }

        return $next($request);
    }
}

