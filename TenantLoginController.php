<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class TenantLoginController extends Controller
{
    /**
     * Store the selected tenant type and proceed to industry selection.
     */
    public function storeType(Request $request)
    {
        $validated = $request->validate([
            'tenant_type' => 'required|string|in:company,individual',
        ]);

        // Security: Clear any existing onboarding session data to start fresh
        Session::forget(['onboarding_tenant_type', 'onboarding_industry']);

        // Store the selection for the registration process
        Session::put('onboarding_tenant_type', $validated['tenant_type']);

        if ($validated['tenant_type'] === 'individual') {
            return redirect()->route('register')
                ->with('status', 'Welcome! Please create your personal account.');
        }

        return redirect()->route('select-industry-type')
            ->with('status', 'Tenant type selected. Now, please select your industry.');
    }

    /**
     * Store the selected industry and proceed to registration.
     */
    public function storeIndustry(Request $request)
    {
        // Security check: Ensure they didn't skip the first step
        if (!Session::has('onboarding_tenant_type')) {
            return redirect()->route('select-tenant-type')
                ->withErrors(['error' => 'Please select a tenant type first.']);
        }

        $validated = $request->validate([
            'industry_type' => 'required|string|max:255',
        ]);

        Session::put('onboarding_industry', $validated['industry_type']);

        // Finally, redirect to the standard registration page
        // The RegisterController can then check the session for these values.
        return redirect()->route('register')
            ->with('status', 'Almost there! Please create your account.');
    }
}