<?php

namespace App\Http\Controllers;

use App\Models\OrdineFase;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class ProduzioneController extends Controller
{
    public function index()
    {
        $fasiVisibili = OrdineFase::with(['ordine', 'faseCatalogo', 'operatori'])->get();
        return view('produzione.index', compact('fasiVisibili'));
    }

    public function avviaFase(Request $request)
    {
        $fase = OrdineFase::with('operatori')->find($request->fase_id);
        if (!$fase) {
            return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
        }

        $operatoreId = session('operatore_id');

        // Aggiunge l'operatore se non presente
        if (!$fase->operatori->contains($operatoreId)) {
            $fase->operatori()->attach($operatoreId, ['data_inizio' => now(),'data_fine'=>null]);
        }

        $fase->stato = 1; // fase avviata
        $fase->save();

        $fase->load('operatori');

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

    $fase->stato = 2; // fase terminata
    $fase->save();

    $operatoreId = session('operatore_id');

    // Aggiorna la data_fine nella pivot per l'operatore corrente
    if ($fase->operatori->contains($operatoreId)) {
        $fase->operatori()->updateExistingPivot($operatoreId, [
            'data_fine' => now()
        ]);
    }

    return response()->json([
        'success' => true,
        'nuovo_stato' => $this->statoLabel($fase->stato),
        'operatori' => [] // nessuno rimane
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
    $validator = Validator::make($request->all(), [
        'fase_id' => 'required|exists:ordine_fasi,id',
        'campo'   => 'required|string|in:qta_prod,note',
        'valore'  => 'nullable'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors()
        ], 422);
    }

    $fase = OrdineFase::find($request->fase_id);

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