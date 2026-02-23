<?php

namespace App\Http\Controllers;

use App\Http\Services\FieryService;
use Illuminate\Http\Request;

class FieryController extends Controller
{
    public function index(FieryService $fiery)
    {
        $status = $fiery->getServerStatus();

        return view('fiery.dashboard', compact('status'));
    }

    public function statusJson(FieryService $fiery)
    {
        $status = $fiery->getServerStatus();

        if (!$status) {
            return response()->json([
                'online' => false,
                'stato' => 'offline',
                'avviso' => 'Fiery non raggiungibile',
            ]);
        }

        return response()->json($status);
    }
}
