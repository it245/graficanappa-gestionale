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

            $repartoSpedizione = Reparto::where('nome', 'spedizione')->first();
            $commessa = $fase->ordine->commessa ?? null;

            // Tutte le fasi non-spedizione non terminate della stessa commessa
            $altreFasiNonTerminate = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
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

            $adesso = now()->format('Y-m-d H:i:s');
            $operatoreId = session('operatore_id');

            // Se forza: termina tutte le fasi non-spedizione aperte
            if ($forza && $altreFasiNonTerminate->count() > 0) {
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

            // Chiudi TUTTE le fasi spedizione della stessa commessa (non solo quella cliccata)
            $fasiSpedizioneCommessa = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                ->where('stato', '!=', 3)
                ->where(function ($q) use ($repartoSpedizione) {
                    if ($repartoSpedizione) {
                        $q->whereHas('faseCatalogo', fn($q2) => $q2->where('reparto_id', $repartoSpedizione->id));
                    }
                })
                ->get();

            foreach ($fasiSpedizioneCommessa as $faseSped) {
                if ($operatoreId && !$faseSped->operatori->contains($operatoreId)) {
                    $faseSped->operatori()->attach($operatoreId, ['data_inizio' => now(), 'data_fine' => now()]);
                }
                $faseSped->stato = 3;
                $faseSped->data_inizio = $adesso;
                $faseSped->data_fine = $adesso;
                $faseSped->save();
            }

            return response()->json([
                'success' => true,
                'messaggio' => 'Consegnato - commessa ' . $commessa
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'messaggio' => 'Errore server: ' . $e->getMessage()
            ], 500);
        }
    }
}
