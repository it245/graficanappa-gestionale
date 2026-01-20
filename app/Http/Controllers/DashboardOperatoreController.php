<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdineFase;

class DashboardOperatoreController extends Controller
{
    public function index(Request $request)
    {
        // Prendi reparto dall'operatore loggato
        $reparto = session('operatore_reparto');

        // Prendi tutte le fasi degli ordini relative al reparto
        $fasiVisibili = OrdineFase::whereHas('faseCatalogo', function($q) use ($reparto) {
            $q->whereHas('reparto', function($r) use ($reparto) {
                $r->where('nome', $reparto);
            });
        })->with('ordine')->get();

        return view('operatore.dashboard', compact('fasiVisibili'));
    }
}