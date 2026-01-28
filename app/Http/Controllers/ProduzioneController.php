<?php

namespace App\Http\Controllers;

use App\Models\OrdineFase;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ProduzioneController extends Controller
{
    public function index()
    {
        $operatore= $request->attributes->get('operatore');
        $fasiVisibili = OrdineFase::with(['ordine', 'faseCatalogo', 'operatori'])->get();
        return view('produzione.index', compact('fasiVisibili', 'operatore'));
    }

public function datiDashboardOperatore(Request $request)
{
    $operatore = $request->attributes->get('operatore');

    $fasi = $operatore->fasi()
        ->with(['ordine', 'faseCatalogo', 'operatori'])
        ->get()
        ->map(function ($fase) {
            return [
                'id' => $fase->id,
                'stato' => $fase->stato,
                'qta_prod' => $fase->qta_prod,
                'note' => $fase->note,
                'timeout' => $fase->timeout,
                'operatori' => $fase->operatori->map(fn($op) => [
                    'nome' => $op->nome,
                    'data_inizio' => $op->pivot->data_inizio,
                    'data_fine' => $op->pivot->data_fine
                ])
            ];
        });

    return response()->json([
        'success' => true,
        'fasi' => $fasi
    ]);
}

    public function avviaFase(Request $request)
    {
        $fase = OrdineFase::with('operatori')->find($request->fase_id);
        if (!$fase) {
            return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
        }

        $operatore=$request->attributes->get('operatore');
        $operatoreId = $operatore->id;

        // Aggiunge l'operatore se non presente
        if (!$fase->operatori->contains($operatoreId)) {
            $fase->operatori()->attach($operatoreId, ['data_inizio' => now()]);
        }

        $fase->stato = 1; // fase avviata
        $fase->save();
        $fase->load('operatori'); // ricarica gli operatori dopo l'eventuale attach
        $operatori = $fase->operatori->map(function($op){
            return [
                'nome' => $op->nome,
                'data_inizio' => $op->pivot->data_inizio ? Carbon::parse($op->pivot->data_inizio)->format('d/m/Y H:i:s') : '-'
            ];
        });

        return response()->json([
            'success' => true,
            'nuovo_stato' => $this->statoLabel($fase->stato),
            'operatori' => $operatori
        ]);
    }



    public function terminaFase(Request $request)
{
    $fase = OrdineFase::with('operatori')->find($request->fase_id);
    if (!$fase) {
        return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
    }

    $operatore = $request->attributes->get('operatore');
    $operatoreId = $operatore->id;

    // Aggiorna la pivot con la data di fine dell'operatore
    $fase->operatori()->updateExistingPivot($operatoreId, ['data_fine' => now()]);

    // Imposta data_fine nella tabella principale solo se non già valorizzata
    if (!$fase->data_fine) {
        $fase->data_fine = now();
    }

    $fase->stato = 2; // fase terminata
    $fase->save();

    // Ricarica gli operatori con pivot aggiornata
    $fase->load('operatori');

    $operatori = $fase->operatori->map(function($op){
        return [
            'nome' => $op->nome,
            'data_inizio' => $op->pivot->data_inizio ? Carbon::parse($op->pivot->data_inizio)->format('d/m/Y H:i:s') : '-',
            'data_fine' => $op->pivot->data_fine ? Carbon::parse($op->pivot->data_fine)->format('d/m/Y H:i:s') : '-'
        ];
    });

    return response()->json([
        'success' => true,
        'nuovo_stato' => $this->statoLabel($fase->stato),
        'operatori' => $operatori,
        'data_fine' => $fase->data_fine->format('d/m/Y H:i:s')
    ]);
}



    public function pausaFase(Request $request)
    {
        $fase = OrdineFase::find($request->fase_id);
        if (!$fase) {
            return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
        }

        $motivo = $request->input('motivo');
        if (!$motivo) {
            return response()->json(['success' => false, 'messaggio' => 'Motivo della pausa mancante']);
        }

        $fase->stato = $motivo;
        $fase->timeout = now();
        $fase->save();

        return response()->json([
            'success' => true,
            'nuovo_stato' => $fase->stato,
            'timeout' => Carbon::parse($fase->timeout)->format('d/m/Y H:i:s')
        ]);
    }

    public function aggiornaCampo(Request $request)
    {
        $request->validate([
            'fase_id' => 'required|exists:ordine_fasi,id',
            'campo' => 'required|string|in:qta_prod,note',
            'valore' => 'nullable'
        ]);

        $fase = OrdineFase::find($request->fase_id);
        if (!$fase) {
            return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
        }

        $fase->{$request->campo} = $request->valore;
        $fase->save();

        return response()->json(['success' => true]);
    }

    private function statoLabel($stato)
    {
        switch ($stato) {
            case 0: return '0';
            case 1: return '1';
            case 2: return '2';
            default: return $stato;
        }
    }
}