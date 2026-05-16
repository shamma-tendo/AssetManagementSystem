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
     * User chooses between managing company assets or household assets
     */
    public function selectTenantType(): View
    {
        return view('auth.select-tenant-type');
    }

    /**
     * Store the selected tenant type in session
     * Routes to industry selection if company, or directly to login if household
     */
    public function storeTenantType(Request $request): RedirectResponse
    {
        $request->validate([
            'tenant_type' => ['required', 'in:company,household'],
        ]);

        session(['tenant_type' => $request->tenant_type]);

        // If household, go straight to login
        if ($request->tenant_type === 'household') {
            return redirect()->route('login');
        }

        // If company, show industry type selection
        return redirect()->route('select-industry-type');
    }

    /**
     * Show the industry type selection page
     * Only for companies - select specific industry like hospital, school, retail, etc.
     */
    public function selectIndustryType(): View
    {
        $tenantType = session('tenant_type');
        
        // Only companies should see this
        if ($tenantType !== 'company') {
            return redirect()->route('select-tenant-type');
        }

        $industries = [
            'generic' => [
                'name' => 'General Company',
                'description' => 'Standard corporate asset management',
                'icon' => '📦',
                'features' => ['Asset Requests', 'Team Distribution', 'Executive Dashboard']
            ],
            'hospital' => [
                'name' => 'Hospital / Medical Facility',
                'description' => 'Specialized for medical equipment and facilities',
                'icon' => '🏥',
                'features' => ['Equipment Tracking', 'Maintenance Schedules', 'Compliance Reports']
            ],
            'school' => [
                'name' => 'School / Educational Institution',
                'description' => 'For classrooms, labs, and educational resources',
                'icon' => '🎓',
                'features' => ['Classroom Resources', 'Equipment Checkout', 'Usage Reports']
            ],
            'retail' => [
                'name' => 'Retail Store',
                'description' => 'POS systems, store equipment, and inventory',
                'icon' => '🏪',
                'features' => ['Store Equipment', 'POS Tracking', 'Multi-Location']
            ],
            'manufacturing' => [
                'name' => 'Manufacturing Facility',
                'description' => 'Machinery, tools, and production line tracking',
                'icon' => '🏭',
                'features' => ['Machinery Management', 'Tool Tracking', 'Maintenance Planning']
            ],
            'corporate' => [
                'name' => 'Corporate Office',
                'description' => 'IT assets, furniture, and office resources',
                'icon' => '🏢',
                'features' => ['IT Asset Management', 'Furniture Tracking', 'Resource Booking']
            ],
        ];

        return view('auth.select-industry-type', ['industries' => $industries]);
    }

    /**
     * Store the selected industry type in session
     */
    public function storeIndustryType(Request $request): RedirectResponse
    {
        $request->validate([
            'industry_type' => ['required', 'in:generic,hospital,school,retail,manufacturing,corporate'],
        ]);

        session(['industry_type' => $request->industry_type]);

        return redirect()->route('login');
    }

    /**
     * Show the login form with tenant context
     */
    public function showLoginForm(): View
    {
        $tenantType = session('tenant_type', 'company');
        $industryType = session('industry_type', 'generic');

        return view('auth.login', [
            'tenant_type' => $tenantType,
            'industry_type' => $industryType,
        ]);
    }

    /**
     * Handle the login attempt
     */
    public function login(Request $request): RedirectResponse
    {
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
        $request->session()->forget(['tenant_type', 'industry_type']);

        return redirect()->route('select-tenant-type');
    }
}
