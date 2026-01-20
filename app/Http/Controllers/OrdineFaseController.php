<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OrdineFaseController extends Controller
{
    public function avvia(Request $request)
    {
       $request->validate([
    'fase_id' => 'required|exists:ordine_fasi,id',
    'operatore_id' => 'required|exists:operatori,id',
]);
    $fase = OrdineFase::findOrFail($request->fase_id);
    $operatore = Operatore::findOrFail($request->operatore_id);
    
    if ($fase->stato !== 0) {
    return back()->withErrors('La fase non è disponibile per l’avvio');
}
     $fase->avvia($operatore);
     
         return back()->with('success', 'Fase avviata correttamente');
     }

     public function termina(Request $request){
         $request->validate([
    'fase_id' => 'required|exists:ordine_fasi,id',
         ]);

         $fase = OrdineFase::findOrFail($request->fase_id);

        if ($fase->stato !== 1) {
    return back()->withErrors('La fase non è attualmente in lavorazione');
}
    
        $fase->termina();
            
        return back()->with('success', 'Fase terminata correttamente');

     }

     public function aggiungiProduzione(Request $request){
       
     $request->validate([
    'fase_id' => 'required|exists:ordine_fasi,id',
    'quantita' => 'required|numeric|min:1',
]);
    $fase = OrdineFase::findOrFail($request->fase_id);

    $totaleProdotto = $fase->ordine->fasi()->sum('qta_prod');
$quantitaRichiesta = $fase->ordine->qta_richiesta;

if ($totaleProdotto - $fase->qta_prod + $request->quantita > $quantitaRichiesta) {
    return back()->withErrors('Non puoi produrre più della quantità totale dell’ordine');
}

     }
}
