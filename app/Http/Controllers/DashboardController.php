<?php

namespace App\Http\Controllers;

use App\Models\Ordine;
use App\Models\Operatore;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // Mostra la dashboard dell'operatore
    public function operator(Request $request)
    {
        // Prendi l'operatore loggato tramite middleware
        $operatore = $request->attributes->get('operatore'); // se usi il token middleware
        if (!$operatore) {
            abort(403, 'Accesso negato');
        }

        // Gestione più reparti separati da virgola
        $reparti = array_map('trim', explode(',', $operatore->reparto));

        // Prendi solo gli ordini che hanno fasi nel reparto dell'operatore
        $ordini = Ordine::whereHas('fasi', function ($query) use ($reparti) {
                $query->whereIn('reparto', $reparti);
            })
            ->with(['fasi' => function ($query) use ($reparti) {
                $query->whereIn('reparto', $reparti);
            }])
            ->get();

        return view('dashboard.operator', compact('ordini', 'operatore'));
    }
}