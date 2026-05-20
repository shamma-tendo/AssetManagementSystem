<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WelcomeController extends Controller
{
    //// WelcomeController.php
    public function index()
    {
        return view('welcome'); 
    }

    public function show()
    {
        return $this->index();
    }

    public function storeContext(Request $request)
    {
        if ($request->has('tenant_type')) {
            session(['tenant_type' => $request->tenant_type]);
        }
        return response()->json(['status' => 'success']);
    }


}
