<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Asset;
use App\Models\WorkOrder;
use App\Models\User;

class LoginController extends Controller
{
    public function showLanding()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('landing', [
            'stats' => [
                'totalAssets' => Asset::count(),
                'workOrders'  => WorkOrder::count(),
                'users'       => User::count(),
            ],
        ]);
    }

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login', [
            'stats' => [
                'totalAssets' => Asset::count(),
                'workOrders'  => WorkOrder::count(),
                'users'       => User::count(),
            ],
        ]);
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:8|confirmed',
            'role'       => 'required|in:viewer,technician,auditor,manager',
        ]);

        $base     = strtolower(substr($request->first_name, 0, 1) . $request->last_name);
        $base     = preg_replace('/[^a-z0-9]/', '', $base);
        $username = $base;
        $i        = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base . $i++;
        }

        User::create([
            'first_name'        => $request->first_name,
            'last_name'         => $request->last_name,
            'email'             => $request->email,
            'username'          => $username,
            'password'          => $request->password,
            'role'              => $request->role,
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        return redirect()->route('login')
            ->with('success', 'Account created! You can now sign in.');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            auth()->user()->updateLastLogin();
            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'These credentials do not match our records.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('landing');
    }
}
