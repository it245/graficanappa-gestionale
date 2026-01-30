<?php

namespace App\Http\Controllers;

use App\Http\Services\PrinectService;

class PrinectController extends Controller
{
    public function index(PrinectService $service)
{
    $devices = $service->getDevices();
    return view('mes.prinect_dashboard', compact('devices'));
}
}
