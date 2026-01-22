<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdineFase;

class DashboardOperatoreController extends Controller
{
    public function index(Request $request)
    {
        // Prendo l'operatore loggato tramite guard
        $operatore = auth()->guard('operatore')->user();

        if (!$operatore) {
            abort(403, 'Accesso negato');
        }

        // Separiamo i reparti dell'operatore (virgola come separatore)
        $reparti = array_map('trim', explode(',', $operatore->reparto));

        // Recuperiamo le fasi visibili per l'operatore
        $fasiVisibili = OrdineFase::whereIn('reparto', $reparti) // filtro per reparti
            ->where('stato', '!=', 2) // solo fasi non completate
            ->with([
                'ordine' => function ($query) {
                    $query->select(
                        'id',
                        'commessa',
                        'cliente_nome',
                        'priorita',
                        'cod_art',
                        'descrizione',
                        'qta_richiesta',
                        'um',
                        'data_registrazione',
                        'data_prevista_consegna',
                        'quantita',
                        'cod_carta',
                        'qta_fase',
                        'carta',
                        'qta_carta',
                        'UM_carta'
                    );
                },
                'faseCatalogo'
            ])
            ->get()
            ->sortBy(fn($f) => strtotime($f->ordine->data_prevista_consegna) - strtotime($f->ordine->data_registrazione));

        // Passiamo tutto alla view
        return view('operatore.dashboard', compact('fasiVisibili', 'operatore'));
    }
}