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
        $fasiVisibili = OrdineFase::whereHas('faseCatalogo.reparto', function ($query) use ($reparto) {
            $query->where('nome', $reparto);
        })
        ->where('stato', '!=', '2') // Solo fasi non completate
        ->with(['ordine'=> function($query){
            $query->select('id', 'commessa', 'cliente_nome','priorita','cod_art','descrizione','qta_richiesta','um','data_registrazione','data_prevista_consegna');
        }, 'faseCatalogo'])
        ->get()
        ->sortbydesc(fn($f) => $f->ordine->priorita);
        return view('operatore.dashboard', compact('fasiVisibili'));
    }

}
