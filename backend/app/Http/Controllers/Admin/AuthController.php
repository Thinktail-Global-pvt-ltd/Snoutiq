<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if ($request->session()->get('is_admin') === true) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $inputEmail = strtolower(trim($validated['email']));
        $configuredEmail = strtolower(trim((string) config('admin.email', 'admin@snoutiq.com')));

        if ($configuredEmail === '') {
            $configuredEmail = 'admin@snoutiq.com';
        }

        if ($inputEmail !== $configuredEmail) {
            return back()
                ->withErrors(['email' => 'You are not authorized to access the admin panel.'])
                ->withInput($request->except('password'));
        }

        $configuredPassword = (string) config('admin.password', 'snoutiqvet');

        if ($configuredPassword === '') {
            $configuredPassword = 'snoutiqvet';
        }

        if (!hash_equals($configuredPassword, (string) $validated['password'])) {
            return back()
                ->withErrors(['password' => 'Invalid password provided.'])
                ->withInput($request->except('password'));
        }

        $request->session()->put('is_admin', true);
        $request->session()->put('admin_email', $configuredEmail);
        $request->session()->put('role', 'admin');
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['is_admin', 'admin_email', 'role']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
