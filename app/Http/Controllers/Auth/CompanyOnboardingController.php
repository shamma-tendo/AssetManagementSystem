<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CompanyOnboardingController extends Controller
{
    /**
     * Handle Registration of a NEW Company
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'company_name' => 'required|string|max:255',
            'industry' => 'required|string',
            'size' => 'required|string'
        ]);

        try {
            // Generate unique company code (e.g., HOSP-4821)
            $industryPrefix = strtoupper(substr($request->industry, 0, 4));
            $companyCode = $industryPrefix . '-' . strtoupper(Str::random(4));
            
            // Ensure code is unique
            while (Organization::where('code', $companyCode)->exists()) {
                $companyCode = $industryPrefix . '-' . strtoupper(Str::random(4));
            }

            // Create company organization
            $org = Organization::create([
                'name' => $request->company_name,
                'slug' => Str::slug($request->company_name),
                'code' => $companyCode,
                'email' => $request->email,
                'type' => 'company',
                'industry_type' => $request->industry,
                'size' => $request->size,
                'description' => $request->company_name . ' (' . $request->size . ')',
                'status' => 'active',
                'is_active' => true,
            ]);

            // Ensure CEO role exists
            $ceoRole = Role::firstOrCreate(
                ['name' => 'CEO'],
                ['description' => 'CEO/CFO (Super Admin) — Full oversight dashboard']
            );

            // The first person to register becomes CEO (Super Admin) - auto-approved
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'organization_id' => $org->id, // UUID primary key
                'role_id' => $ceoRole->id,
                'status' => 'active',
                'is_approved' => true,
            ]);

            auth()->login($user);

            return redirect()->route('executive.dashboard');
        } catch (\Exception $e) {
            Log::error('Error registering company: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Failed to create company: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle Joining an EXISTING Company
     */
    public function join(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'company_code' => 'required|string',
            'role_request' => 'required|string|in:Asset Manager,Staff'
        ]);

        try {
            // Find organization by code (e.g. HOSP-4821)
            $org = Organization::where('code', $request->company_code)
                ->orWhere('code', strtoupper($request->company_code))
                ->first();

            if (!$org) {
                return back()->withInput()->withErrors(['company_code' => 'The provided company code is invalid. Please verify with your CEO.']);
            }

            // Create pending user account
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'organization_id' => $org->id, // UUID primary key
                'requested_role' => $request->role_request,
                'status' => 'pending', // Account stays pending
                'is_approved' => false,
            ]);
            
            // Notify CEO about new pending user (placeholder for notification system)
            Log::info("New join request: {$user->email} requesting {$request->role_request} role for {$org->name}");

            return view('auth.pending-approval', [
                'message' => "Your request to join {$org->name} as an {$request->role_request} has been sent. Please await approval from your CEO/CFO."
            ]);
        } catch (\Exception $e) {
            Log::error('Error joining company: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Failed to submit join request: ' . $e->getMessage()]);
        }
    }
}
