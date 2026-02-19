<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Operatore;
use App\Models\Reparto;
use Illuminate\Support\Facades\Hash;

class DashboardAdminController extends Controller
{
    public function index()
    {
        $operatori = Operatore::with('reparti')->orderBy('attivo', 'desc')->orderBy('nome')->get();
        $reparti = Reparto::orderBy('nome')->pluck('nome', 'id');

        $ultimoNumero = Operatore::selectRaw('CAST(RIGHT(codice_operatore, 3) AS UNSIGNED) as numero')
            ->orderByDesc('numero')->value('numero');
        $prossimoNumero = $ultimoNumero ? $ultimoNumero + 1 : 1;
        $prossimoCodice = '__' . str_pad($prossimoNumero, 3, '0', STR_PAD_LEFT);

        return view('admin.dashboard', compact('operatori', 'reparti', 'prossimoCodice', 'prossimoNumero'));
    }

    public function crea()
    {
        $reparti = Reparto::orderBy('nome')->pluck('nome', 'id');
        return view('admin.operatore_form', [
            'operatore' => null,
            'reparti' => $reparti,
        ]);
    }

    public function salva(Request $request)
    {
        $request->validate([
            'nome' => 'required|string',
            'cognome' => 'required|string',
            'ruolo' => 'required|in:operatore,owner,admin',
            'reparto_principale' => 'required|exists:reparti,id',
            'reparto_secondario' => 'nullable|exists:reparti,id',
            'password' => 'nullable|string|min:4',
        ]);

        $ultimoNumero = Operatore::selectRaw('CAST(RIGHT(codice_operatore, 3) AS UNSIGNED) as numero')
            ->orderByDesc('numero')->value('numero');
        $numero = $ultimoNumero ? $ultimoNumero + 1 : 1;

        $nome = ucfirst(strtolower($request->nome));
        $cognome = ucfirst(strtolower($request->cognome));
        $iniziali = strtoupper($nome[0] . $cognome[0]);
        $codice = $iniziali . str_pad($numero, 3, '0', STR_PAD_LEFT);

        $operatore = Operatore::create([
            'nome' => $nome,
            'cognome' => $cognome,
            'codice_operatore' => $codice,
            'ruolo' => $request->ruolo,
            'attivo' => 1,
            'reparto_id' => $request->reparto_principale,
            'password' => $request->password ? Hash::make($request->password) : null,
        ]);

        $reparti = array_filter([$request->reparto_principale, $request->reparto_secondario]);
        $operatore->reparti()->sync($reparti);

        return redirect()->route('admin.dashboard')->with('success', "Operatore $codice creato correttamente.");
    }

    public function modifica($id)
    {
        $operatore = Operatore::with('reparti')->findOrFail($id);
        $reparti = Reparto::orderBy('nome')->pluck('nome', 'id');
        return view('admin.operatore_form', compact('operatore', 'reparti'));
    }

    public function aggiorna(Request $request, $id)
    {
        $operatore = Operatore::findOrFail($id);

        $request->validate([
            'nome' => 'required|string',
            'cognome' => 'required|string',
            'ruolo' => 'required|in:operatore,owner,admin',
            'reparto_principale' => 'required|exists:reparti,id',
            'reparto_secondario' => 'nullable|exists:reparti,id',
            'password' => 'nullable|string|min:4',
        ]);

        $operatore->nome = ucfirst(strtolower($request->nome));
        $operatore->cognome = ucfirst(strtolower($request->cognome));
        $operatore->ruolo = $request->ruolo;
        $operatore->reparto_id = $request->reparto_principale;

        if ($request->filled('password')) {
            $operatore->password = Hash::make($request->password);
        }

        $operatore->save();

        $reparti = array_filter([$request->reparto_principale, $request->reparto_secondario]);
        $operatore->reparti()->sync($reparti);

        return redirect()->route('admin.dashboard')->with('success', "Operatore {$operatore->codice_operatore} aggiornato.");
    }

    public function toggleAttivo($id)
    {
        $operatore = Operatore::findOrFail($id);
        $operatore->attivo = !$operatore->attivo;
        $operatore->save();

        $stato = $operatore->attivo ? 'attivato' : 'disattivato';
        return redirect()->route('admin.dashboard')->with('success', "Operatore {$operatore->codice_operatore} $stato.");
    }
}
