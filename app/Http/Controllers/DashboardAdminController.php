<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Operatore;
use App\Models\Reparto;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

    public function statistiche()
    {
        $sogliaRecenti = Carbon::now()->subDays(7);

        $operatori = Operatore::where('attivo', 1)
            ->where('ruolo', 'operatore')
            ->with('reparti')
            ->get()
            ->map(function ($op) use ($sogliaRecenti) {
                // Reparti come stringa
                $op->stat_reparti = $op->reparti->pluck('nome')->join(', ');

                // Fasi completate (stato=3) e in corso (stato=2) dalla pivot fase_operatore
                $fasi = DB::table('fase_operatore')
                    ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
                    ->where('fase_operatore.operatore_id', $op->id)
                    ->select(
                        'ordine_fasi.stato',
                        'ordine_fasi.qta_prod',
                        'fase_operatore.data_inizio',
                        'fase_operatore.data_fine'
                    )
                    ->get();

                $op->stat_fasi_completate = $fasi->where('stato', 3)->count();
                $op->stat_fasi_in_corso = $fasi->where('stato', 2)->count();

                // Tempo totale lavorato (somma differenze data_inizio - data_fine dalla pivot)
                $secTotale = 0;
                $fasiConTempo = 0;
                foreach ($fasi as $f) {
                    if ($f->data_inizio && $f->data_fine) {
                        $sec = Carbon::parse($f->data_fine)->diffInSeconds(Carbon::parse($f->data_inizio));
                        $secTotale += $sec;
                        $fasiConTempo++;
                    }
                }
                $op->stat_sec_totale = $secTotale;
                $op->stat_sec_medio = $fasiConTempo > 0 ? round($secTotale / $fasiConTempo) : 0;

                // Quantita prodotta totale
                $op->stat_qta_prod = $fasi->where('stato', 3)->sum('qta_prod');

                // Ultimi 7 giorni
                $recenti = $fasi->filter(function ($f) use ($sogliaRecenti) {
                    return $f->stato == 3 && $f->data_fine && Carbon::parse($f->data_fine)->gte($sogliaRecenti);
                });
                $op->stat_fasi_recenti = $recenti->count();
                $secRecenti = 0;
                foreach ($recenti as $f) {
                    if ($f->data_inizio && $f->data_fine) {
                        $secRecenti += Carbon::parse($f->data_fine)->diffInSeconds(Carbon::parse($f->data_inizio));
                    }
                }
                $op->stat_sec_recenti = $secRecenti;

                return $op;
            })
            ->sortByDesc('stat_fasi_completate');

        return view('admin.statistiche_operatori', compact('operatori'));
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
