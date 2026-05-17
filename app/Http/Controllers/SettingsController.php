<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $stats = [
            'totalUsers' => User::count(),
            'adminUsers' => User::where('role', 'admin')->count(),
            'technicianUsers' => User::where('role', 'technician')->count(),
        ];

        return view('settings', compact('stats'));
    }
}
