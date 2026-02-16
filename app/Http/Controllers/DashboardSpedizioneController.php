<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdineFase;
use App\Models\Reparto;
use Carbon\Carbon;

class DashboardSpedizioneController extends Controller
{
    public function index(Request $request)
    {
        $operatore = auth()->guard('operatore')->user();
        if (!$operatore) abort(403, 'Accesso negato');

        // Reparto spedizione
        $repartoSpedizione = Reparto::where('nome', 'spedizione')->first();
        if (!$repartoSpedizione) abort(404, 'Reparto spedizione non trovato');

        // Fasi da spedire (solo stato 0 = da avviare)
        $fasiDaSpedire = OrdineFase::where('stato', 0)
            ->whereHas('faseCatalogo', function ($q) use ($repartoSpedizione) {
                $q->where('reparto_id', $repartoSpedizione->id);
            })
            ->with(['ordine', 'faseCatalogo', 'operatori'])
            ->get()
            ->sortBy(function ($fase) {
                // Ordina per data consegna (urgenti prima)
                return $fase->ordine->data_prevista_consegna ?? '9999-12-31';
            });

        // Fasi spedite oggi
        $fasiSpediteOggi = OrdineFase::where('stato', 2)
            ->whereDate('data_fine', Carbon::today())
            ->whereHas('faseCatalogo', function ($q) use ($repartoSpedizione) {
                $q->where('reparto_id', $repartoSpedizione->id);
            })
            ->with(['ordine', 'faseCatalogo', 'operatori'])
            ->get()
            ->sortByDesc('data_fine');

        return view('spedizione.dashboard', compact('fasiDaSpedire', 'fasiSpediteOggi', 'operatore'));
    }

    /**
     * Invio automatico: avvia + termina la fase in un solo click
     */
    public function invioAutomatico(Request $request)
    {
        $fase = OrdineFase::with('operatori')->find($request->fase_id);
        if (!$fase) {
            return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
        }

        if ($fase->stato == 2) {
            return response()->json(['success' => false, 'messaggio' => 'Già consegnato']);
        }

        $operatoreId = session('operatore_id');

        // Attach operatore e passa direttamente da 0 a 2
        if (!$fase->operatori->contains($operatoreId)) {
            $fase->operatori()->attach($operatoreId, ['data_inizio' => now(), 'data_fine' => now()]);
        }

        $fase->stato = 2;
        $fase->data_inizio = now();
        $fase->data_fine = now();
        $fase->save();

        return response()->json([
            'success' => true,
            'messaggio' => 'Consegnato - commessa ' . ($fase->ordine->commessa ?? '-')
        ]);
    }
}
