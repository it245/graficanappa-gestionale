<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdineFase;
use App\Models\Operatore;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\OrdiniImport;

class DashboardOwnerController extends Controller
{
    // Mostra tutte le fasi e ordini
    public function index()
    {
        // Recupera tutte le fasi, compresi ordini terminati
        $fasi = OrdineFase::with('ordine', 'faseCatalogo', 'operatore')->get();

        return view('owner.dashboard', compact('fasi'));
    }

    // Aggiorna campi qta_prod e note
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

    // Aggiunge nuovo operatore
    public function aggiungiOperatore(Request $request)
    {
        $request->validate([
            'nome' => 'required|string',
            'cognome' => 'nullable|string',
            'codice_operatore' => 'required|string|unique:operatori,codice_operatore',
            'ruolo' => 'required|in:operatore,owner',
            'reparto' => 'required|string',
        ]);

        Operatore::create([
            'nome' => $request->nome,
            'cognome' => $request->cognome,
            'codice_operatore' => $request->codice_operatore,
            'ruolo' => $request->ruolo,
            'reparto' => $request->reparto,
            'attivo' => 1,
            'password' => Hash::make('password123'), // password iniziale
        ]);

        return redirect()->back()->with('success', 'Operatore aggiunto correttamente.');
    }

    // Import ordini da file Excel
    public function importOrdini(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            Excel::import(new OrdiniImport, $request->file('file'));
            return redirect()->back()->with('success', 'Ordini importati correttamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Errore importazione: '.$e->getMessage());
        }
    }
}