<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdineFase;
use App\Models\Ordine;
use App\Models\Reparto;
use App\Models\DdtSpedizione;
use App\Http\Services\BrtService;
use App\Models\NotaSpedizione;
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

        // Fasi DDT vendita: ordini con ddt_vendita_id, fase spedizione stato < 4, tipo_consegna IS NULL
        $fasiDDT = OrdineFase::whereHas('ordine', fn($q) => $q->whereNotNull('ddt_vendita_id'))
            ->where('stato', '<', 4)
            ->where(fn($q) => $q->whereNull('tipo_consegna'))
            ->whereHas('faseCatalogo', fn($q) => $q->where('reparto_id', $repartoSpedizione->id))
            ->with(['ordine.fasi.faseCatalogo', 'faseCatalogo'])
            ->get();

        // Fasi parziali: tipo_consegna='parziale' e stato < 4
        $fasiParziali = OrdineFase::where('tipo_consegna', 'parziale')
            ->where('stato', '<', 4)
            ->whereHas('faseCatalogo', fn($q) => $q->where('reparto_id', $repartoSpedizione->id))
            ->with(['ordine.fasi.faseCatalogo', 'faseCatalogo', 'operatori'])
            ->get();

        // IDs da escludere dalle sezioni normali (DDT + parziali)
        $idsDDTeParziali = $fasiDDT->pluck('id')->merge($fasiParziali->pluck('id'))->toArray();

        // Fasi spedizione con stato 0 o 1 (candidati per "Da consegnare" o "In attesa")
        $fasiSpedizione = OrdineFase::whereIn('stato', [0, 1])
            ->whereHas('faseCatalogo', function ($q) use ($repartoSpedizione) {
                $q->where('reparto_id', $repartoSpedizione->id);
            })
            ->whereNotIn('id', $idsDDTeParziali)
            ->with(['ordine.fasi.faseCatalogo', 'faseCatalogo', 'operatori'])
            ->get();

        // Pre-fetch: tutte le commesse coinvolte e le loro fasi non-spedizione (evita N+1)
        $commesseSpedizione = $fasiSpedizione->map(fn($f) => $f->ordine->commessa)->filter()->unique()->values();
        $tutteFasiPerCommessa = collect();
        if ($commesseSpedizione->isNotEmpty()) {
            $tutteFasiPerCommessa = OrdineFase::whereHas('ordine', fn($q) => $q->whereIn('commessa', $commesseSpedizione))
                ->whereHas('faseCatalogo', fn($q) => $q->where('reparto_id', '!=', $repartoSpedizione->id))
                ->with('faseCatalogo')
                ->get()
                ->groupBy(fn($f) => $f->ordine->commessa);
        }

        // "Da consegnare" = tutte le fasi non-BRT dell'intera commessa sono a stato >= 3
        $fasiDaSpedire = $fasiSpedizione->filter(function ($fase) use ($tutteFasiPerCommessa) {
            $commessa = $fase->ordine->commessa ?? null;
            if (!$commessa) return false;
            $fasiNonBrt = $tutteFasiPerCommessa->get($commessa, collect());
            // stato 5 (esterno) NON conta come terminata
            return $fasiNonBrt->isNotEmpty() && $fasiNonBrt->every(fn($f) => is_numeric($f->stato) && (int)$f->stato >= 3 && (int)$f->stato != 5);
        })->sortBy(fn($fase) => $fase->ordine->data_prevista_consegna ?? '9999-12-31');

        // Auto-promuovi fasi stato 0→1 che sono pronte per la spedizione
        foreach ($fasiDaSpedire as $fase) {
            if ($fase->stato == 0) {
                $fase->stato = 1;
                $fase->save();
            }
        }

        // Calcola percentuale completamento per tutte le fasi spedizione
        foreach ($fasiSpedizione as $fase) {
            $commessa = $fase->ordine->commessa ?? null;
            $fasiNonBrt = $tutteFasiPerCommessa->get($commessa, collect());
            $totaleFasi = $fasiNonBrt->count();
            // stato 5 (esterno) NON conta come terminata
            $fasiTerminate = $fasiNonBrt->filter(fn($f) => is_numeric($f->stato) && (int)$f->stato >= 3 && (int)$f->stato != 5)->count();
            $fase->percentuale = $totaleFasi > 0 ? round(($fasiTerminate / $totaleFasi) * 100) : 0;
        }

        // Fasi spedite oggi (stato 4 = consegnato)
        $fasiSpediteOggi = OrdineFase::where('stato', 4)
            ->whereDate('data_fine', Carbon::today())
            ->whereHas('faseCatalogo', function ($q) use ($repartoSpedizione) {
                $q->where('reparto_id', $repartoSpedizione->id);
            })
            ->with(['ordine.fasi.faseCatalogo', 'faseCatalogo', 'operatori'])
            ->get()
            ->sortByDesc('data_fine');

        // Fasi in attesa: fasi spedizione le cui altre fasi NON sono tutte terminate
        $fasiDaSpedireIds = $fasiDaSpedire->pluck('id')->toArray();
        $fasiInAttesa = $fasiSpedizione->filter(fn($f) => !in_array($f->id, $fasiDaSpedireIds));

        // Fasi non terminate per il bottone Forza Consegna (usa dati pre-caricati)
        foreach ($fasiInAttesa as $fase) {
            $commessa = $fase->ordine->commessa ?? null;
            $fasiNonBrt = $tutteFasiPerCommessa->get($commessa, collect());
            $fase->fasiNonTerminate = $fasiNonBrt->where('stato', '<', 3)->values();
        }

        $fasiInAttesa = $fasiInAttesa->sortByDesc('percentuale');

        // Fasi esterne (reparto "esterno" + flag esterno) non terminate
        $repartoEsterno = Reparto::where('nome', 'esterno')->first();
        $fasiEsterne = OrdineFase::where(fn($q) => $q->where('stato', '<', 3)->orWhere('stato', 5))
            ->where(function ($q) use ($repartoEsterno) {
                if ($repartoEsterno) {
                    $q->whereHas('faseCatalogo', fn($q2) => $q2->where('reparto_id', $repartoEsterno->id));
                }
                $q->orWhere('esterno', true);
            })
            ->with(['ordine.fasi.faseCatalogo', 'faseCatalogo', 'operatori'])
            ->get()
            ->sortBy(fn($f) => $f->ordine->data_prevista_consegna ?? '9999-12-31');

        // Storico consegne (ultimi 30 giorni, escluso oggi)
        $storicoConsegne = OrdineFase::where('stato', 4)
            ->whereDate('data_fine', '<', Carbon::today())
            ->whereDate('data_fine', '>=', Carbon::today()->subDays(30))
            ->whereHas('faseCatalogo', function ($q) use ($repartoSpedizione) {
                $q->where('reparto_id', $repartoSpedizione->id);
            })
            ->with(['ordine.fasi.faseCatalogo', 'faseCatalogo', 'operatori'])
            ->orderByDesc('data_fine')
            ->get();

        // Spedizioni BRT: dalla tabella ddt_spedizioni (supporta più DDT per commessa)
        $spedizioniBRT = DdtSpedizione::with('ordine:id,descrizione')
            ->where('vettore', 'LIKE', '%BRT%')
            ->orderByDesc('onda_id_doc')
            ->get()
            ->groupBy('numero_ddt');

        return view('spedizione.dashboard', compact('fasiDaSpedire', 'fasiSpediteOggi', 'fasiInAttesa', 'fasiEsterne', 'fasiDDT', 'fasiParziali', 'spedizioniBRT', 'storicoConsegne', 'operatore'));
    }

    public function esterne(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();
        if (!$operatore) abort(403, 'Accesso negato');

        $repartoEsterno = Reparto::where('nome', 'esterno')->first();
        $fasiEsterne = OrdineFase::where(fn($q) => $q->where('stato', '<', 3)->orWhere('stato', 5))
            ->where(function ($q) use ($repartoEsterno) {
                if ($repartoEsterno) {
                    $q->whereHas('faseCatalogo', fn($q2) => $q2->where('reparto_id', $repartoEsterno->id));
                }
                $q->orWhere('esterno', true);
            })
            ->with(['ordine.fasi.faseCatalogo', 'faseCatalogo', 'operatori'])
            ->get()
            ->sortBy(fn($f) => $f->ordine->data_prevista_consegna ?? '9999-12-31');

        return view('spedizione.esterne', compact('fasiEsterne', 'operatore'));
    }

    public function invioAutomatico(Request $request)
    {
        try {
            $fase = OrdineFase::with(['operatori', 'ordine'])->find($request->fase_id);
            if (!$fase) {
                return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
            }

            if ((int) $fase->stato >= 3 && (int) $fase->stato !== 5) {
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

            if ($tipoConsegna === 'parziale') {
                // Consegna parziale: aggiorna solo la fase spedizione, NON promuovere a stato=4
                $fase->tipo_consegna = 'parziale';
                $fase->segnacollo_brt = $request->input('segnacollo_brt') ?: null;
                $fase->data_fine = $adesso;
                if (!$fase->data_inizio) {
                    $fase->data_inizio = $adesso;
                }
                // Stato resta < 4 (promuovi a 2 se era 0/1)
                if ($fase->stato < 2) {
                    $fase->stato = 2;
                }
                $fase->save();

                // Attach operatore
                if ($operatoreId && !$fase->operatori->contains($operatoreId)) {
                    $fase->operatori()->attach($operatoreId, ['data_inizio' => now(), 'data_fine' => now()]);
                }
            } else {
                // Consegna totale: tutte le fasi della commessa vanno a stato=4 (consegnato)
                $tutteLeFasiCommessa = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                    ->whereRaw("stato REGEXP '^[0-9]+$' AND (stato < 4 OR stato = 5)")
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

                // Salva tipo consegna e segnacollo BRT sulla fase spedizione
                $fase->tipo_consegna = 'totale';
                $fase->segnacollo_brt = $request->input('segnacollo_brt') ?: null;
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

    public function tracking(Request $request)
    {
        $segnacollo = $request->input('segnacollo');
        if (!$segnacollo) {
            return response()->json(['error' => true, 'message' => 'Segnacollo mancante']);
        }

        $brt = new BrtService();
        $data = $brt->getTracking($segnacollo);

        return response()->json($data);
    }

    public function trackingTest(Request $request)
    {
        $segnacollo = $request->query('segnacollo');
        $data = null;

        if ($segnacollo) {
            $brt = new BrtService();
            $data = $brt->getTracking(trim($segnacollo));
        }

        return view('spedizione.tracking-test', compact('segnacollo', 'data'));
    }

    public function trackingJson(Request $request, $segnacollo)
    {
        $brt = new BrtService();
        $data = $brt->getTracking(trim($segnacollo));

        return response()->json($data, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Tracking via SOAP: cerca per numero DDT (riferimento mittente alfabetico)
     */
    public function trackingByDDT(Request $request)
    {
        $numeroDDT = $request->input('numero_ddt');
        if (!$numeroDDT) {
            return response()->json(['error' => true, 'message' => 'Numero DDT mancante']);
        }

        if (!class_exists('SoapClient')) {
            return response()->json([
                'error' => true,
                'message' => 'Estensione PHP SOAP non installata sul server',
            ]);
        }

        $brt = new BrtService();
        $ddtSenzaZeri = ltrim($numeroDDT, '0');

        $data = $brt->getTrackingByDDT($numeroDDT);

        if (!$data) {
            return response()->json([
                'error' => true,
                'message' => "DDT {$ddtSenzaZeri}: tracking non ancora disponibile. Le spedizioni recenti vengono indicizzate da BRT entro 24-48 ore dal ritiro.",
            ]);
        }

        // Salva nella cache ddt_spedizioni
        $this->cacheBrtTracking($numeroDDT, $data);

        return response()->json($data);
    }

    /**
     * Salva lo stato BRT nella tabella ddt_spedizioni come cache.
     */
    protected function cacheBrtTracking(string $numeroDDT, array $data): void
    {
        try {
            $stato = $data['stato'] ?? null;
            $bolla = $data['bolla'] ?? [];
            $dataConsegna = $bolla['data_consegna'] ?? null;
            $ora = $bolla['ora_consegna'] ?? null;
            if ($dataConsegna && $ora) $dataConsegna .= ' ' . $ora;
            $destinatario = collect([$bolla['destinatario_ragione_sociale'] ?? null, $bolla['destinatario_localita'] ?? null])
                ->filter()->implode(' - ');
            $colli = $bolla['colli'] ?? null;

            DdtSpedizione::where('numero_ddt', $numeroDDT)
                ->update([
                    'brt_stato' => $stato,
                    'brt_data_consegna' => $dataConsegna ?: null,
                    'brt_destinatario' => $destinatario ?: null,
                    'brt_colli' => $colli,
                    'brt_cache_at' => now(),
                ]);
        } catch (\Exception $e) {
            // Cache fail non è critico
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
                $f->segnacollo_brt = null;
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

    public function noteGiornaliere(Request $request)
    {
        $data = $request->input('data', now()->toDateString());
        $nota = NotaSpedizione::where('data', $data)->first();

        // Se oggi non ha nota, carica la più recente
        if (!$nota || empty($nota->contenuto)) {
            $notaRecente = NotaSpedizione::where('data', '<', $data)
                ->whereNotNull('contenuto')
                ->where('contenuto', '!=', '')
                ->orderByDesc('data')
                ->first();
            if ($notaRecente) {
                return response()->json([
                    'data' => $data,
                    'contenuto' => $notaRecente->contenuto,
                    'da_data' => $notaRecente->data->format('Y-m-d'),
                ]);
            }
        }

        return response()->json([
            'data' => $data,
            'contenuto' => $nota ? $nota->contenuto : '',
        ]);
    }

    public function salvaNotaGiornaliera(Request $request)
    {
        $request->validate([
            'data' => 'required|date',
            'contenuto' => 'nullable|string',
        ]);

        NotaSpedizione::updateOrCreate(
            ['data' => $request->data],
            ['contenuto' => $request->contenuto]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Restituisce l'ultimo updated_at delle note spedizione di oggi.
     * Usato per polling notifiche owner.
     */
    public function noteUltimoAggiornamento()
    {
        // Cerca l'ultima nota modificata (qualsiasi data), non solo oggi
        $nota = NotaSpedizione::orderByDesc('updated_at')->first();
        return response()->json([
            'updated_at' => $nota ? $nota->updated_at->toIso8601String() : null,
            'contenuto'  => $nota ? $nota->contenuto : '',
            'data'       => $nota ? $nota->data->format('d/m/Y') : null,
        ]);
    }

    /**
     * Notifiche per la spedizione (invii esterni, ecc.)
     * Polling dalla dashboard spedizione.
     */
    public function notifiche()
    {
        $notifiche = \DB::table('notifiche_spedizione')
            ->where('letto', false)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'notifiche' => $notifiche,
            'count' => $notifiche->count(),
        ]);
    }

    /**
     * Segna una notifica come letta.
     */
    public function notificaLetta($id)
    {
        \DB::table('notifiche_spedizione')->where('id', $id)->update(['letto' => true]);
        return response()->json(['success' => true]);
    }

    /**
     * Segna tutte le notifiche come lette.
     */
    public function notificheLette()
    {
        \DB::table('notifiche_spedizione')->where('letto', false)->update(['letto' => true]);
        return response()->json(['success' => true]);
    }

    public function syncOnda(Request $request)
    {
        try {
            $risultato = \App\Services\OndaSyncService::sincronizza();
            $ddtFornitore = \App\Services\OndaSyncService::sincronizzaDDTFornitore();
            $ddtLavorazioni = \App\Services\OndaSyncService::sincronizzaDDTFornitureLavorazioni();
            $ddtVendita = \App\Services\OndaSyncService::sincronizzaDDTVendita();
            $msg = "Sync Onda: {$risultato['ordini_creati']} creati, "
                 . "{$risultato['ordini_aggiornati']} aggiornati, {$risultato['fasi_create']} fasi.";
            $totDDT = $ddtFornitore + $ddtLavorazioni;
            if ($totDDT > 0) $msg .= " DDT fornitore: {$totDDT} fasi esterne.";
            if ($ddtVendita > 0) $msg .= " DDT vendita: {$ddtVendita}.";
            return redirect()->back()->with('success', $msg);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Errore sync Onda: ' . $e->getMessage());
        }
    }
}
