<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{

    public function operator()
    {
      return view('dashboard.operator');
    }

    public function superadmin()
    {
      return view('dashboard.admin');
    }
    
}
