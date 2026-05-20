<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class ExecutiveOverviewController extends Controller
{
    /**
     * Show the executive overview / pulse.
     */
    public function index()
    {
        return redirect()->route('dashboard');
    }
}
