<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Support\Str;

class HouseholdRegistrationController extends Controller
{
    /**
     * Show the household registration form
     */
    public function showRegistrationForm(): View
    {
        $tenantType = session('tenant_type', 'household');
        
        // Only household users should see this
        if ($tenantType !== 'household') {
            return redirect()->route('select-tenant-type');
        }
        
        return view('auth.household-register');
    }

    /**
     * Handle household user registration
     */
    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        // Create household organization
        $organization = Organization::create([
            'name' => $request->name . "'s Household",
            'slug' => Str::slug($request->name . 's-household'),
            'type' => 'household',
            'industry_type' => 'household',
            'size' => '1-5',
            'status' => 'active',
            'email' => $request->email,
        ]);

        // Create the user (no role needed for household)
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'organization_id' => $organization->id,
            'role_id' => null, // Household users don't have roles
            'status' => 'active',
            'is_approved' => true, // Auto-approved for household
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('household.dashboard');
    }

    /**
     * Show the household login/registration choice page
     */
    public function showAuthChoice(): View
    {
        $tenantType = session('tenant_type', 'household');
        
        // Only household users should see this
        if ($tenantType !== 'household') {
            return redirect()->route('select-tenant-type');
        }
        
        return view('auth.household-auth-choice');
    }
}
