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
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();
        if (!$operatore) abort(403, 'Accesso negato');

        // Reparto spedizione
        $repartoSpedizione = Reparto::where('nome', 'spedizione')->first();
        if (!$repartoSpedizione) abort(404, 'Reparto spedizione non trovato');

        // Fasi spedizione con stato 0 o 1 (candidati per "Da consegnare" o "In attesa")
        $fasiSpedizione = OrdineFase::whereIn('stato', [0, 1])
            ->whereHas('faseCatalogo', function ($q) use ($repartoSpedizione) {
                $q->where('reparto_id', $repartoSpedizione->id);
            })
            ->with(['ordine', 'faseCatalogo', 'operatori'])
            ->get();

        // "Da consegnare" = tutte le fasi non-BRT dell'intera commessa (tutti gli ordini) sono a stato >= 3
        $fasiDaSpedire = $fasiSpedizione->filter(function ($fase) use ($repartoSpedizione) {
            $commessa = $fase->ordine->commessa ?? null;
            if (!$commessa) return false;

            $fasiNonBrtCommessa = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                ->whereHas('faseCatalogo', function ($q) use ($repartoSpedizione) {
                    $q->where('reparto_id', '!=', $repartoSpedizione->id);
                })
                ->get();

            return $fasiNonBrtCommessa->isNotEmpty() && $fasiNonBrtCommessa->every(fn($f) => $f->stato >= 3);
        })->sortBy(function ($fase) {
            return $fase->ordine->data_prevista_consegna ?? '9999-12-31';
        });

        // Auto-promuovi fasi stato 0→1 che sono pronte per la spedizione
        foreach ($fasiDaSpedire as $fase) {
            if ($fase->stato == 0) {
                $fase->stato = 1;
                $fase->save();
            }
        }

        // Calcola percentuale completamento per commessa
        foreach ($fasiDaSpedire as $fase) {
            $commessa = $fase->ordine->commessa ?? null;
            $totaleFasi = $commessa ? OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                ->whereHas('faseCatalogo', fn($q) => $q->where('reparto_id', '!=', $repartoSpedizione->id))
                ->count() : 0;
            $fasiTerminate = $commessa ? OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                ->whereHas('faseCatalogo', fn($q) => $q->where('reparto_id', '!=', $repartoSpedizione->id))
                ->where('stato', '>=', 3)
                ->count() : 0;
            $fase->percentuale = $totaleFasi > 0 ? round(($fasiTerminate / $totaleFasi) * 100) : 0;
        }

        // Fasi spedite oggi (stato 4 = consegnato)
        $fasiSpediteOggi = OrdineFase::where('stato', 4)
            ->whereDate('data_fine', Carbon::today())
            ->whereHas('faseCatalogo', function ($q) use ($repartoSpedizione) {
                $q->where('reparto_id', $repartoSpedizione->id);
            })
            ->with(['ordine', 'faseCatalogo', 'operatori'])
            ->get()
            ->sortByDesc('data_fine');

        // Fasi in attesa: fasi spedizione stato 0 le cui altre fasi NON sono tutte terminate
        $fasiDaSpedireIds = $fasiDaSpedire->pluck('id')->toArray();
        $fasiInAttesa = $fasiSpedizione->filter(fn($f) => !in_array($f->id, $fasiDaSpedireIds));

        foreach ($fasiInAttesa as $fase) {
            $commessa = $fase->ordine->commessa ?? null;

            // Percentuale calcolata su tutte le fasi non-BRT dell'intera commessa
            $totaleFasi = $commessa ? OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                ->whereHas('faseCatalogo', fn($q) => $q->where('reparto_id', '!=', $repartoSpedizione->id))
                ->count() : 0;
            $fasiTerminate = $commessa ? OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                ->whereHas('faseCatalogo', fn($q) => $q->where('reparto_id', '!=', $repartoSpedizione->id))
                ->where('stato', '>=', 3)
                ->count() : 0;
            $fase->percentuale = $totaleFasi > 0 ? round(($fasiTerminate / $totaleFasi) * 100) : 0;

            // Fasi non terminate dell'intera commessa (per il bottone Forza Consegna)
            $fase->fasiNonTerminate = $commessa ? OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                ->whereHas('faseCatalogo', fn($q) => $q->where('reparto_id', '!=', $repartoSpedizione->id))
                ->where('stato', '<', 3)
                ->with('faseCatalogo')
                ->get() : collect();
        }

        $fasiInAttesa = $fasiInAttesa->sortByDesc('percentuale');

        // Fasi esterne (reparto "esterno") non terminate
        $repartoEsterno = Reparto::where('nome', 'esterno')->first();
        $fasiEsterne = collect();
        if ($repartoEsterno) {
            $fasiEsterne = OrdineFase::where('stato', '<', 3)
                ->whereHas('faseCatalogo', fn($q) => $q->where('reparto_id', $repartoEsterno->id))
                ->with(['ordine', 'faseCatalogo', 'operatori'])
                ->get()
                ->sortBy(fn($f) => $f->ordine->data_prevista_consegna ?? '9999-12-31');
        }

        return view('spedizione.dashboard', compact('fasiDaSpedire', 'fasiSpediteOggi', 'fasiInAttesa', 'fasiEsterne', 'operatore'));
    }

    public function esterne(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();
        if (!$operatore) abort(403, 'Accesso negato');

        $repartoEsterno = Reparto::where('nome', 'esterno')->first();
        $fasiEsterne = collect();
        if ($repartoEsterno) {
            $fasiEsterne = OrdineFase::where('stato', '<', 3)
                ->whereHas('faseCatalogo', fn($q) => $q->where('reparto_id', $repartoEsterno->id))
                ->with(['ordine', 'faseCatalogo', 'operatori'])
                ->get()
                ->sortBy(fn($f) => $f->ordine->data_prevista_consegna ?? '9999-12-31');
        }

        return view('spedizione.esterne', compact('fasiEsterne', 'operatore'));
    }

    public function invioAutomatico(Request $request)
    {
        try {
            $fase = OrdineFase::with(['operatori', 'ordine'])->find($request->fase_id);
            if (!$fase) {
                return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
            }

            if ($fase->stato >= 3) {
                return response()->json(['success' => false, 'messaggio' => 'Già consegnato']);
            }

            $forza = $request->boolean('forza', false);
            $tipoConsegna = $request->input('tipo_consegna', 'totale'); // 'totale' o 'parziale'

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
                ->where('stato', '<', 3)
                ->get();

            if ($altreFasiNonTerminate->count() > 0 && !$forza) {
                return response()->json(['success' => false, 'messaggio' => 'Non tutte le fasi sono terminate']);
            }

            $adesso = now()->format('Y-m-d H:i:s');
            $operatoreId = $request->attributes->get('operatore_id') ?? session('operatore_id');

            // Tutte le fasi della commessa vanno a stato=4 (consegnato)
            $tutteLeFasiCommessa = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                ->where('stato', '<', 4)
                ->get();

            foreach ($tutteLeFasiCommessa as $f) {
                $f->stato = 4;
                if (!$f->data_fine) {
                    $f->data_fine = $adesso;
                }
                if (!$f->data_inizio) {
                    $f->data_inizio = $adesso;
                }
                $f->save();
            }

            // Salva tipo consegna sulla fase spedizione
            $fase->tipo_consegna = $tipoConsegna;
            $fase->save();

            // Attach operatore spedizione alle fasi BRT
            $fasiSpedizioneCommessa = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                ->where('stato', 4)
                ->where(function ($q) use ($repartoSpedizione) {
                    if ($repartoSpedizione) {
                        $q->whereHas('faseCatalogo', fn($q2) => $q2->where('reparto_id', $repartoSpedizione->id));
                    }
                })
                ->with('operatori')
                ->get();

            foreach ($fasiSpedizioneCommessa as $faseSped) {
                if ($operatoreId && !$faseSped->operatori->contains($operatoreId)) {
                    $faseSped->operatori()->attach($operatoreId, ['data_inizio' => now(), 'data_fine' => now()]);
                }
            }

            return response()->json([
                'success' => true,
                'messaggio' => ($tipoConsegna === 'parziale' ? 'Consegna parziale' : 'Consegnato') . ' - commessa ' . $commessa
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'messaggio' => 'Errore server: ' . $e->getMessage()
            ], 500);
        }
    }

    public function recuperaConsegna(Request $request)
    {
        try {
            $fase = OrdineFase::with('ordine')->find($request->fase_id);
            if (!$fase) {
                return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
            }

            $commessa = $fase->ordine->commessa ?? null;
            if (!$commessa) {
                return response()->json(['success' => false, 'messaggio' => 'Commessa non trovata']);
            }

            $repartoSpedizione = Reparto::where('nome', 'spedizione')->first();

            // Ripristina fasi spedizione: stato 1 (pronto), rimuovi data_fine e tipo_consegna
            $fasiSped = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                ->whereHas('faseCatalogo', fn($q) => $q->where('reparto_id', $repartoSpedizione->id))
                ->where('stato', 4)
                ->get();

            foreach ($fasiSped as $f) {
                $f->stato = 1;
                $f->data_fine = null;
                $f->data_inizio = null;
                $f->tipo_consegna = null;
                $f->save();
            }

            // Ripristina fasi non-spedizione: stato 3 (terminato) - erano già terminate prima della consegna
            $fasiAltro = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                ->where(function ($q) use ($repartoSpedizione) {
                    $q->whereDoesntHave('faseCatalogo')
                       ->orWhereHas('faseCatalogo', fn($q2) => $q2->where('reparto_id', '!=', $repartoSpedizione->id));
                })
                ->where('stato', 4)
                ->get();

            foreach ($fasiAltro as $f) {
                $f->stato = 3;
                $f->save();
            }

            return response()->json([
                'success' => true,
                'messaggio' => 'Consegna annullata - commessa ' . $commessa
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'messaggio' => 'Errore server: ' . $e->getMessage()
            ], 500);
        }
    }
}
