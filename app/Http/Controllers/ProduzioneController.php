<?php

namespace App\Http\Controllers;

use App\Models\OrdineFase;
use App\Models\PausaOperatore;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Services\FaseStatoService;

class ProduzioneController extends Controller
{
    public function index()
    {
        $fasiVisibili = OrdineFase::with(['ordine', 'faseCatalogo', 'operatori'])->get();
        return view('produzione.index', compact('fasiVisibili'));
    }

    public function avviaFase(Request $request)
    {
        try {
            $fase = OrdineFase::with('operatori')->find($request->fase_id);
            if (!$fase) {
                return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
            }

            $operatoreId = session('operatore_id');

            // Aggiunge l'operatore se non presente
            if ($operatoreId && !$fase->operatori->contains($operatoreId)) {
                $fase->operatori()->attach($operatoreId, ['data_inizio' => now(),'data_fine'=>null]);
            }

            // Se viene inviato il nome del terzista, salvalo nelle note
            if ($request->filled('terzista')) {
                $fase->note = 'Inviato a: ' . $request->input('terzista');
            }

            $fase->stato = 2; // fase avviata
            if (!$fase->data_inizio) {
                $fase->data_inizio = now()->format('Y-m-d H:i:s');
            }
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
        } catch (\Exception $e) {
            \Log::error('avviaFase errore: ' . $e->getMessage(), ['fase_id' => $request->fase_id, 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'messaggio' => $e->getMessage()], 500);
        }
    }

   public function terminaFase(Request $request)
{
    $validator = Validator::make($request->all(), [
        'fase_id'       => 'required',
        'qta_prodotta'  => 'required|integer|min:0',
        'scarti'        => 'nullable|integer|min:0',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'messaggio' => 'Inserire la quantita prodotta.',
            'errors' => $validator->errors()
        ], 422);
    }

    $fase = OrdineFase::with('operatori')->find($request->fase_id);
    if (!$fase) {
        return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
    }

    $operatoreId = session('operatore_id');

    // Auto-chiusura pausa aperta (se l'operatore termina senza aver ripreso)
    $pausaAperta = PausaOperatore::where('operatore_id', $operatoreId)
        ->where('ordine_id', $fase->ordine_id)
        ->where('fase', $fase->fase)
        ->whereNull('fine')
        ->latest('data_ora')
        ->first();

    if ($pausaAperta) {
        $pausaAperta->fine = now();
        $pausaAperta->save();

        $durataSecondi = Carbon::parse($pausaAperta->data_ora)->diffInSeconds(Carbon::parse($pausaAperta->fine));

        if ($fase->operatori->contains($operatoreId)) {
            $currentSecondi = $fase->operatori->find($operatoreId)->pivot->secondi_pausa ?? 0;
            $fase->operatori()->updateExistingPivot($operatoreId, [
                'secondi_pausa' => $currentSecondi + $durataSecondi
            ]);
        }
    }

    $fase->qta_prod = $request->qta_prodotta;
    $fase->scarti = $request->scarti ?? 0;
    $fase->stato = 3; // fase terminata
    $fase->data_fine = now()->format('Y-m-d H:i:s');
    $fase->timeout = null;
    $fase->save();

    // Aggiorna la data_fine nella pivot per l'operatore corrente
    if ($fase->operatori->contains($operatoreId)) {
        $fase->operatori()->updateExistingPivot($operatoreId, [
            'data_fine' => now()
        ]);
    }

    // Ricalcola stati fasi successive della stessa commessa
    FaseStatoService::ricalcolaStati($fase->ordine_id);

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

        $operatoreId = session('operatore_id');

        // Crea record pausa aperta
        PausaOperatore::create([
            'operatore_id' => $operatoreId,
            'ordine_id'    => $fase->ordine_id,
            'fase'         => $fase->fase,
            'motivo'       => $motivo,
            'data_ora'     => now(),
        ]);

        $fase->stato = $motivo;
        $fase->timeout = now();
        $fase->save();

        return response()->json([
            'success' => true,
            'nuovo_stato' => $fase->stato,
            'timeout' => Carbon::parse($fase->timeout)->format('d/m/Y H:i:s')
        ]);
    }

    public function riprendiFase(Request $request)
    {
        $fase = OrdineFase::with('operatori')->find($request->fase_id);
        if (!$fase) {
            return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
        }

        $operatoreId = session('operatore_id');

        // Trova pausa aperta per questo operatore/ordine/fase
        $pausa = PausaOperatore::where('operatore_id', $operatoreId)
            ->where('ordine_id', $fase->ordine_id)
            ->where('fase', $fase->fase)
            ->whereNull('fine')
            ->latest('data_ora')
            ->first();

        if ($pausa) {
            $pausa->fine = now();
            $pausa->save();

            // Calcola durata pausa in secondi e accumula nel pivot
            $durataSecondi = Carbon::parse($pausa->data_ora)->diffInSeconds(Carbon::parse($pausa->fine));

            if ($fase->operatori->contains($operatoreId)) {
                $currentSecondi = $fase->operatori->find($operatoreId)->pivot->secondi_pausa ?? 0;
                $fase->operatori()->updateExistingPivot($operatoreId, [
                    'secondi_pausa' => $currentSecondi + $durataSecondi
                ]);
            }
        }

        $fase->stato = 2; // Avviato
        $fase->timeout = null;
        $fase->save();

        return response()->json([
            'success' => true,
            'nuovo_stato' => $this->statoLabel($fase->stato),
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
            case 0: return 'Caricato';
            case 1: return 'Pronto';
            case 2: return 'Avviato';
            case 3: return 'Terminato';
            case 4: return 'Consegnato';
            default: return $stato;
        }
    }
}