<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdminAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        $session = $request->session();

        $isAdmin = $session->get('is_admin') === true;

        $configuredEmail = strtolower(trim((string) config('admin.email', 'admin@snoutiq.com')));
        if ($configuredEmail === '') {
            $configuredEmail = 'admin@snoutiq.com';
        }

        $emailMatches = strtolower((string) $session->get('admin_email')) === $configuredEmail;
        $roleMatches = $session->get('role') === 'admin';

        if (!($isAdmin && $emailMatches && $roleMatches)) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
