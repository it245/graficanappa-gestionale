<?php

namespace App\Http\Controllers;

use App\Models\OrdineFase;
use Illuminate\Http\Request;

class ProduzioneController extends Controller
{
    public function index()
    {
        // Non necessario se usi solo AJAX per aggiornare le fasi
        $fasi = OrdineFase::with('ordine', 'faseCatalogo')->get();
        return view('produzione.index', compact('fasi'));
    }

    public function avviaFase(Request $request)
    {
        $fase = OrdineFase::find($request->fase_id);
        if (!$fase) {
            return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
        }

        $fase->stato = 1; // 1 = avviata
        $fase->operatore_id = session('operatore_id'); // Assumi che l'ID operatore sia in sessione
        $fase->save();

        return response()->json([
            'success' => true,
            'nuovo_stato' => $this->statoLabel($fase->stato),
            'operatore' => session('operatore_nome')
        ]);
    }

   public function pausaFase(Request $request)
{
    $fase = OrdineFase::find($request->fase_id);
    if (!$fase) {
        return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
    }

    // Richiedi motivo all'operatore
    $motivo = $request->input('motivo'); 
    if (!$motivo) {
        return response()->json(['success' => false, 'messaggio' => 'Motivo della pausa mancante']);
    }
    
    date_default_timezone_set('Europe/Rome'); // Imposta il fuso orario a Roma

    // Aggiorna stato e timeout
    $fase->stato = $motivo;          // testo della pausa
    $fase->timeout = date('Y-m-d H:i:s');           // data e ora corrente
    $fase->save();
    $timeout_italiano = date('d/m/Y H:i:s', strtotime($fase->timeout));
   
    return response()->json([
        'success' => true,
        'nuovo_stato' => $fase->stato,
        'timeout' => $timeout_italiano // formato leggibile
    ]);
}

    public function terminaFase(Request $request)
    {
        $fase = OrdineFase::find($request->fase_id);
        if (!$fase) {
            return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
        }

        $fase->stato = 2; // 2 = terminata
        $fase->save();

        return response()->json([
            'success' => true,
            'nuovo_stato' => $this->statoLabel($fase->stato)
        ]);
    }

    // Helper per visualizzare label leggibile
    private function statoLabel($stato)
    {
        switch ($stato) {
            case 0: return '0';
            case 1: return '1';
            case 2: return '2';
            default: return $stato; // se è un testo (pausa), lo ritorna così com’è
        }
    }

 public function aggiornaCampo(Request $request)
{
    $request->validate([
        'fase_id' => 'required|exists:ordine_fasi,id',
        'campo' => 'required|string|in:qta_prod,note', // solo campi spostati in ordine_fasi
        'valore' => 'nullable'
    ]);

    // Recupera la fase
    $fase = OrdineFase::find($request->fase_id);
    if (!$fase) {
        return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
    }

    // Aggiorna direttamente la fase
    $campo = $request->campo;
    $valore = $request->valore;
    $fase->{$campo} = $valore;
    $fase->save();

    return response()->json(['success' => true]);
}
}