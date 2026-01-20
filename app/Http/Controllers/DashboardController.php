<?php

namespace App\Http\Controllers;

use App\Models\Ordine;
use App\Models\Operatore;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function operator()
    {
        // Per ora simuliamo l'operatore (senza Auth)
        $operatore = Operatore::first();

        // Prendiamo solo gli ordini che hanno fasi del reparto dell'operatore
        $ordini = Ordine::whereHas('fasi', function ($query) use ($operatore) {
            $query->where('reparto', $operatore->reparto);
        })
        ->with(['fasi' => function ($query) use ($operatore) {
            $query->where('reparto', $operatore->reparto);
        }])
        ->get();

        return view('dashboard.operator', compact('ordini'));
    }
}
