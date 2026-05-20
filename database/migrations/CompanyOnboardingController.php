<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class CompanyOnboardingController extends Controller
{
    /**
     * Register a new company and assign user as CEO/CFO
     */
    public function registerCompany(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'industry' => 'required',
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        // Generate Unique Company Code (e.g., TECH-ABCD)
        $prefix = strtoupper(substr($request->industry, 0, 4));
        $code = $prefix . '-' . strtoupper(Str::random(4));

        $org = Organization::create([
            'name' => $request->company_name,
            'industry_type' => $request->industry,
            'company_code' => $code,
            'type' => 'company'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'organization_id' => $org->id,
            'role' => 'CEO', // Auto-assign Super Admin
            'status' => 'active'
        ]);

        auth()->login($user);
        return redirect()->route('executive.dashboard')->with('success', "Company created! Code: $code");
    }

    /**
     * Join an existing company via code
     */
    public function joinCompany(Request $request)
    {
        $org = Organization::where('company_code', $request->company_code)->firstOrFail();

        User::create([
            'organization_id' => $org->id,
            'status' => 'pending',
            'requested_role' => $request->role // Asset Manager or Staff
        ]);
    }
}