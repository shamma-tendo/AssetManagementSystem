<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TenantLoginController extends Controller
{
    /**
     * Show the tenant type selection page
     */
    public function selectTenantType(): View
    {
        return view('auth.select-tenant-type');
    }

    /**
     * Store the selected tenant type in session
     */
    public function storeTenantType(Request $request): RedirectResponse
    {
        $request->validate([
            'tenant_type' => ['required', 'in:company,household'],
        ]);

        session(['tenant_type' => $request->tenant_type]);

        return redirect()->route('login');
    }

    /**
     * Show the login form with tenant context
     */
    public function showLoginForm(): View
    {
        $tenantType = session('tenant_type', 'company');

        return view('auth.login', [
            'tenant_type' => $tenantType,
        ]);
    }

    /**
     * Handle the login attempt
     */
    public function login(Request $request): RedirectResponse
    {
        $tenantType = session('tenant_type', 'company');

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => __('The provided credentials do not match our records.'),
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Handle logout and clear tenant context
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->forget('tenant_type');

        return redirect()->route('select-tenant-type');
    }
}
