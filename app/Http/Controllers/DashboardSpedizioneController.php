<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdineFase;
use App\Models\Ordine;
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

        // Fasi spedizione con stato 1 (pronto) - consegnabili solo se tutte le altre fasi della commessa sono terminate
        $fasiSpedizione = OrdineFase::where('stato', 1)
            ->whereHas('faseCatalogo', function ($q) use ($repartoSpedizione) {
                $q->where('reparto_id', $repartoSpedizione->id);
            })
            ->with(['ordine', 'faseCatalogo', 'operatori'])
            ->get();

        // Filtra: mostra solo se tutte le altre fasi (non spedizione) della stessa commessa sono terminate (3)
        $fasiDaSpedire = $fasiSpedizione->filter(function ($fase) use ($repartoSpedizione) {
            $altreFasi = OrdineFase::where('ordine_id', $fase->ordine_id)
                ->where('id', '!=', $fase->id)
                ->whereHas('faseCatalogo', function ($q) use ($repartoSpedizione) {
                    $q->where('reparto_id', '!=', $repartoSpedizione->id);
                })
                ->get();

            return $altreFasi->every(fn($f) => $f->stato == 3);
        })->sortBy(function ($fase) {
            return $fase->ordine->data_prevista_consegna ?? '9999-12-31';
        });

        // Calcola percentuale completamento per ogni fase da spedire
        foreach ($fasiDaSpedire as $fase) {
            $totaleFasi = OrdineFase::where('ordine_id', $fase->ordine_id)->count();
            $fasiTerminate = OrdineFase::where('ordine_id', $fase->ordine_id)->where('stato', 3)->count();
            $fase->percentuale = $totaleFasi > 0 ? round(($fasiTerminate / $totaleFasi) * 100) : 0;
        }

        // Fasi spedite oggi (stato 3)
        $fasiSpediteOggi = OrdineFase::where('stato', 3)
            ->whereDate('data_fine', Carbon::today())
            ->whereHas('faseCatalogo', function ($q) use ($repartoSpedizione) {
                $q->where('reparto_id', $repartoSpedizione->id);
            })
            ->with(['ordine', 'faseCatalogo', 'operatori'])
            ->get()
            ->sortByDesc('data_fine');

        // Fasi in attesa (le fasi BRT non ancora pronte perché altre fasi non terminate)
        $fasiInAttesa = OrdineFase::whereIn('stato', [0])
            ->whereHas('faseCatalogo', function ($q) use ($repartoSpedizione) {
                $q->where('reparto_id', $repartoSpedizione->id);
            })
            ->with(['ordine', 'faseCatalogo'])
            ->get();

        foreach ($fasiInAttesa as $fase) {
            $totaleFasi = OrdineFase::where('ordine_id', $fase->ordine_id)
                ->where('id', '!=', $fase->id)
                ->count();
            $fasiTerminate = OrdineFase::where('ordine_id', $fase->ordine_id)
                ->where('id', '!=', $fase->id)
                ->where('stato', 3)
                ->count();
            $fase->percentuale = $totaleFasi > 0 ? round(($fasiTerminate / $totaleFasi) * 100) : 0;

            // Fasi non terminate (per il bottone Forza Consegna)
            $fase->fasiNonTerminate = OrdineFase::where('ordine_id', $fase->ordine_id)
                ->where('id', '!=', $fase->id)
                ->whereHas('faseCatalogo', function ($q) use ($repartoSpedizione) {
                    $q->where('reparto_id', '!=', $repartoSpedizione->id);
                })
                ->where('stato', '!=', 3)
                ->with('faseCatalogo')
                ->get();
        }

        $fasiInAttesa = $fasiInAttesa->sortByDesc('percentuale');

        return view('spedizione.dashboard', compact('fasiDaSpedire', 'fasiSpediteOggi', 'fasiInAttesa', 'operatore'));
    }

    public function invioAutomatico(Request $request)
    {
        try {
            $fase = OrdineFase::with(['operatori', 'ordine'])->find($request->fase_id);
            if (!$fase) {
                return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
            }

            if ($fase->stato == 3) {
                return response()->json(['success' => false, 'messaggio' => 'Già consegnato']);
            }

            $forza = $request->boolean('forza', false);

            // Verifica che tutte le altre fasi della commessa siano terminate
            $repartoSpedizione = Reparto::where('nome', 'spedizione')->first();

            // Cerca fasi non terminate in TUTTI gli ordini della stessa commessa
            $commessa = $fase->ordine->commessa ?? null;
            $altreFasiNonTerminate = OrdineFase::where('id', '!=', $fase->id)
                ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                ->where(function ($q) use ($repartoSpedizione) {
                    if ($repartoSpedizione) {
                        $q->where(function ($q2) use ($repartoSpedizione) {
                            $q2->whereDoesntHave('faseCatalogo')
                               ->orWhereHas('faseCatalogo', fn($q3) => $q3->where('reparto_id', '!=', $repartoSpedizione->id));
                        });
                    }
                })
                ->where('stato', '!=', 3)
                ->get();

            if ($altreFasiNonTerminate->count() > 0 && !$forza) {
                return response()->json(['success' => false, 'messaggio' => 'Non tutte le fasi sono terminate']);
            }

            // Se forza: termina automaticamente tutte le fasi precedenti
            if ($forza && $altreFasiNonTerminate->count() > 0) {
                $adesso = now()->format('Y-m-d H:i:s');
                foreach ($altreFasiNonTerminate as $faseAperta) {
                    $faseAperta->stato = 3;
                    if (!$faseAperta->data_fine) {
                        $faseAperta->data_fine = $adesso;
                    }
                    if (!$faseAperta->data_inizio) {
                        $faseAperta->data_inizio = $adesso;
                    }
                    $faseAperta->save();
                }
            }

            $operatoreId = session('operatore_id');

            if ($operatoreId && !$fase->operatori->contains($operatoreId)) {
                $fase->operatori()->attach($operatoreId, ['data_inizio' => now(), 'data_fine' => now()]);
            }

            $fase->stato = 3;
            $fase->data_inizio = now()->format('Y-m-d H:i:s');
            $fase->data_fine = now()->format('Y-m-d H:i:s');
            $fase->save();

            return response()->json([
                'success' => true,
                'messaggio' => 'Consegnato - commessa ' . ($fase->ordine->commessa ?? '-')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'messaggio' => 'Errore server: ' . $e->getMessage()
            ], 500);
        }
    }
}
