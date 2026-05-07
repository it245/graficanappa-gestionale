<?php

declare(strict_types=1);

namespace App\Modules\Onda\Services;

use App\Models\FasiCatalogo;
use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\Reparto;
use App\Modules\Onda\Contracts\OndaErpInterface;
use App\Modules\Reparti\Services\RepartoService;
use App\Services\FaseStatoService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sincronizza gli ordini di produzione (TBOrdini lato MES, ATTDocTeste TipoDocumento=2 lato Onda).
 *
 * Strangler Fig: contiene il body completo migrato da
 * {@see \App\Services\OndaSyncService::sincronizza()}.
 *
 * Responsabilità:
 *  - dedup ordini Onda (whitespace nelle descrizioni, descrizione vuota → merge);
 *  - dedup fasi pre-esistenti (1 sola per commessa per fustella/digitale/finestratura,
 *    max 2 STAMPAXL106 per cod_art multi-passaggio, ecc.);
 *  - upsert {@see Ordine} con preservazione di campi modificati manualmente
 *    (data_prevista_consegna, cliente_nome);
 *  - rimappa STAMPA generico → STAMPAXL106 / STAMPAINDIGO / STAMPAINDIGOBN
 *    in base a CodMacchina;
 *  - crea {@see OrdineFase} con la corretta assegnazione di reparto
 *    (via {@see RepartoService::mappaSlugToId()});
 *  - applica regole di dedup runtime per ogni reparto (fustella per commessa,
 *    tagliacarte per ordine, plastificazione/stampa offset somma qta distinte);
 *  - assicura sempre la fase BRT1 di spedizione;
 *  - chiama {@see FaseStatoService::ricalcolaTutti()} alla fine.
 *
 * Numeri attesi su sync incrementale post-migrazione (sessione test):
 *   ordini_creati ~ 179, ordini_aggiornati ~ 371, fasi_create ~ 191,
 *   duplicati_rimossi ~ 190 (variabile a seconda dello stato DB).
 *
 * NB: la finestra temporale è ancora hardcoded a 2026-02-27 per allineamento
 * bit-for-bit col legacy. Quando si introdurrà un parametro $dal effettivo
 * andrà propagato a {@see OndaErpInterface::getOrdiniDal()}.
 */
final class OrdineSyncService
{
    public function __construct(
        private readonly OndaErpInterface $onda,
    ) {}

    /**
     * Sincronizza ordini Onda dal momento $dal in poi.
     *
     * @param Carbon|null $dal Data soglia (default: 2026-02-27, allineato al legacy).
     *
     * @return array{
     *   inseriti:int,
     *   aggiornati:int,
     *   errori:int,
     *   ordini_creati:int,
     *   ordini_aggiornati:int,
     *   fasi_create:int,
     *   duplicati_rimossi:int,
     *   log_ordini_creati?:list<string>,
     *   log_ordini_aggiornati?:list<string>,
     *   log_fasi_create?:list<string>
     * }
     */
    public function sync(?Carbon $dal = null): array
    {
        $ordiniCreati = 0;
        $ordiniAggiornati = 0;
        $fasiCreate = 0;
        $logOrdiniCreati = [];
        $logOrdiniAggiornati = [];
        $logFasiCreate = [];

        $repartoSvc = app(RepartoService::class);
        $mappaReparti = $repartoSvc->mappaSlugToId();
        $tipiFase = $repartoSvc->tipoFromCodice();
        $mappaPriorita = config('fasi_priorita');
        $oggi = now()->format('Y-m-d');

        // Pre-fetch scarti previsti per macchina da Onda (via Adapter)
        $scartiMacchine = $this->onda->getScartiPrevistiPerMacchina();

        // Commesse gia completate nel MES (tutte le fasi stato >= 4) — le escludiamo dalla sync
        $commesseCompletate = DB::table('ordini')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('ordine_fasi')
                    ->whereColumn('ordine_fasi.ordine_id', 'ordini.id')
                    ->whereRaw("CAST(ordine_fasi.stato AS UNSIGNED) < 4");
            })
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('ordine_fasi')
                    ->whereColumn('ordine_fasi.ordine_id', 'ordini.id');
            })
            ->pluck('commessa')
            ->toArray();

        Log::info("OndaSync: " . count($commesseCompletate) . " commesse completate escluse dalla sync");

        // 1. Query ordini da Onda (via Adapter): finestra hardcoded 2026-02-27 come legacy
        $righeOnda = $this->onda->getOrdiniDal($dal ?? Carbon::create(2026, 2, 27));

        if (empty($righeOnda)) {
            return [
                'inseriti'          => 0,
                'aggiornati'        => 0,
                'errori'            => 0,
                'ordini_creati'     => 0,
                'ordini_aggiornati' => 0,
                'fasi_create'       => 0,
                'duplicati_rimossi' => 0,
            ];
        }

        // Filtra via le commesse gia completate nel MES
        if (!empty($commesseCompletate)) {
            $completateSet = array_flip($commesseCompletate);
            $prima = count($righeOnda);
            $righeOnda = array_values(array_filter($righeOnda, fn($r) => !isset($completateSet[$r->CodCommessa])));
            Log::info("OndaSync: filtrate " . ($prima - count($righeOnda)) . " righe di commesse completate, restano " . count($righeOnda));
        }

        if (empty($righeOnda)) {
            return [
                'inseriti'          => 0,
                'aggiornati'        => 0,
                'errori'            => 0,
                'ordini_creati'     => 0,
                'ordini_aggiornati' => 0,
                'fasi_create'       => 0,
                'duplicati_rimossi' => 0,
            ];
        }

        // 1a. Pulizia ordini duplicati per whitespace nella descrizione
        //     Unisce ordini con stessa commessa+cod_art ma descrizione diversa solo per \r\n vs spazio
        $ordiniDuplicati = DB::table('ordini')
            ->select('commessa', 'cod_art', DB::raw('COUNT(*) as cnt'))
            ->groupBy('commessa', 'cod_art')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($ordiniDuplicati as $dup) {
            $ordiniGruppo = Ordine::where('commessa', $dup->commessa)
                ->where('cod_art', $dup->cod_art)
                ->orderBy('id')
                ->get();

            $perDesc = $ordiniGruppo->groupBy(function ($o) {
                return preg_replace('/\s+/', ' ', trim($o->descrizione));
            });

            foreach ($perDesc as $descNorm => $ordini) {
                if ($ordini->count() <= 1) continue;

                $keeper = $ordini->first();
                $duplicates = $ordini->slice(1);

                foreach ($duplicates as $dupOrdine) {
                    OrdineFase::where('ordine_id', $dupOrdine->id)
                        ->update(['ordine_id' => $keeper->id]);
                    $dupOrdine->delete();
                    Log::info("OndaSync: unito ordine #{$dupOrdine->id} in #{$keeper->id} (commessa {$dup->commessa}, desc normalizzata)");
                }

                $keeper->update(['descrizione' => $descNorm]);
            }

            // Merge ordini con descrizione vuota in quelli con descrizione reale
            if ($perDesc->count() > 1 && $perDesc->has('')) {
                $ordiniVuoti = $perDesc->get('');
                $ordiniConDesc = $perDesc->reject(fn($v, $k) => $k === '')->flatten();
                $keeper = $ordiniConDesc->first();
                if ($keeper) {
                    foreach ($ordiniVuoti as $vuoto) {
                        OrdineFase::where('ordine_id', $vuoto->id)
                            ->update(['ordine_id' => $keeper->id]);
                        $vuoto->delete();
                        Log::info("OndaSync: unito ordine vuoto #{$vuoto->id} in #{$keeper->id} (commessa {$dup->commessa}, desc vuota)");
                    }
                }
            }
        }

        // 1b. Pulizia duplicati fasi: se ci sono più di 1 fasi uguali (stesso ordine + fase_catalogo),
        //     mantieni solo la prima e elimina il resto.
        //     Escludi STAMPAXL106 (gestito dal cleanup per commessa con logica max 2 fasi)
        $idStampaXL = FasiCatalogo::where('nome', 'like', 'STAMPAXL106%')->pluck('id');
        $duplicati = DB::table('ordine_fasi')
            ->select('ordine_id', 'fase_catalogo_id', DB::raw('COUNT(*) as cnt'))
            ->whereNull('deleted_at')
            ->where('manuale', false)
            ->whereNotIn('fase_catalogo_id', $idStampaXL)
            ->groupBy('ordine_id', 'fase_catalogo_id')
            ->having('cnt', '>', 1)
            ->get();

        $fasiEliminate = 0;
        foreach ($duplicati as $dup) {
            // Tieni solo la prima (id più basso)
            $keepIds = OrdineFase::where('ordine_id', $dup->ordine_id)
                ->where('fase_catalogo_id', $dup->fase_catalogo_id)
                ->where('manuale', false)
                ->orderBy('id')
                ->limit(1)
                ->pluck('id');

            $deleted = OrdineFase::where('ordine_id', $dup->ordine_id)
                ->where('fase_catalogo_id', $dup->fase_catalogo_id)
                ->whereNotIn('id', $keepIds)
                ->whereRaw("stato REGEXP '^[0-9]+$' AND stato <= 1")
                ->where('manuale', false)
                ->delete();
            $fasiEliminate += $deleted;
        }

        // Pulizia duplicati fustella per commessa: 1 sola fase per commessa per fase_catalogo
        // (FUSTBOBST75X106 e FUSTBOBSTRILIEVI sono fase_catalogo diverse → possono coesistere)
        $repartiFustella = Reparto::whereIn('nome', ['fustella piana', 'fustella cilindrica', 'fustella'])->pluck('id');
        if ($repartiFustella->isNotEmpty()) {
            $dupFustella = DB::table('ordine_fasi')
                ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
                ->join('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
                ->select('ordini.commessa', 'ordine_fasi.fase_catalogo_id', DB::raw('COUNT(*) as cnt'))
                ->whereIn('fasi_catalogo.reparto_id', $repartiFustella)
                ->whereNull('ordine_fasi.deleted_at')
                ->where('ordine_fasi.manuale', false)
                ->groupBy('ordini.commessa', 'ordine_fasi.fase_catalogo_id')
                ->having('cnt', '>', 1)
                ->get();

            foreach ($dupFustella as $dup) {
                $faseIds = OrdineFase::where('fase_catalogo_id', $dup->fase_catalogo_id)
                    ->whereHas('ordine', fn($q) => $q->where('commessa', $dup->commessa))
                    ->orderBy('id')
                    ->pluck('id');

                $keepId = $faseIds->first();
                $deleteIds = $faseIds->slice(1)->filter();
                if ($deleteIds->isNotEmpty()) {
                    $deleted = OrdineFase::whereIn('id', $deleteIds)
                        ->whereRaw("stato REGEXP '^[0-9]+$' AND stato <= 1")
                        ->where('manuale', false)
                        ->delete();
                    $fasiEliminate += $deleted;
                }
            }
        }

        // Pulizia duplicati stampa offset per commessa: raggruppa per commessa (non per fase_catalogo)
        // perché STAMPAXL106.1 e STAMPAXL106.2 sono fase_catalogo diverse ma stessa stampa fisica
        // cod_art multi-passaggio permettono max 2, gli altri max 1
        $codArtMax2 = [
            'Volumi','Vassoio','Vassoi','SPILLATI.OFFSET','SPILLATI.DIGITALE',
            'SOVRACOPERTA','RIVISTE.FRECCIA','riviste','RIVISTA.FRECCIA.128PP',
            'RICETTARI','Raccoglitori','Quaderni','Opuscoli','Libro.di.bordo',
            'Libricino','LibriBN','Libri','INSERTO.RIVISTA.NOTE.4pp',
            'I.Volumi','I.riviste','I.Raccoglitori','I.Quaderni','I.Poster',
            'I.Opuscoli','I.Menu','I.Libricino','I.Libri','I.copertina',
            'I.cataloghi','I.cartoline','I.Calendari.da.Tavolo',
            'I.Calendari.da.Muro','I.Calendari','I.Block.Notes',
            'I.Blocchi.autocopianti','I.Blocchi','I.Bilanci',
            'Espositori.da.Terra','Espositori.da.banco','Depliant','COPERTINA',
            'cataloghi','Calendari.da.Tavolo','Calendari.da.Muro','Calendari',
            'BROSSURATI.OFFSET','BROSSURATI.DIGITALE','brochure','Block.Notes',
            'Blocchi.Mod.TI','Blocchi.Mod.R1','Blocchi.Mod.K','Blocchi.Mod.CH69',
            'Blocchi.autocopianti.M40a','Blocchi.autocopianti','Blocchi','Bilanci',
        ];
        $repartiStampaOffset = Reparto::where('nome', 'stampa offset')->pluck('id');
        if ($repartiStampaOffset->isNotEmpty()) {
            $dupStampa = DB::table('ordine_fasi')
                ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
                ->join('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
                ->select('ordini.commessa', DB::raw('COUNT(*) as cnt'),
                    DB::raw('MAX(ordini.cod_art) as cod_art'))
                ->whereIn('fasi_catalogo.reparto_id', $repartiStampaOffset)
                ->where('fasi_catalogo.nome', 'like', 'STAMPAXL106%')
                ->whereNull('ordine_fasi.deleted_at')
                ->where('ordine_fasi.manuale', false)
                ->groupBy('ordini.commessa')
                ->having('cnt', '>', 1)
                ->get();

            foreach ($dupStampa as $dup) {
                $maxFasi = in_array($dup->cod_art, $codArtMax2) ? 2 : 1;
                if ($dup->cnt <= $maxFasi) continue;

                $faseIds = OrdineFase::whereHas('faseCatalogo', fn($q) =>
                        $q->whereIn('reparto_id', $repartiStampaOffset)
                          ->where('nome', 'like', 'STAMPAXL106%'))
                    ->whereHas('ordine', fn($q) => $q->where('commessa', $dup->commessa))
                    ->orderBy('id')
                    ->pluck('id');

                $keepIds = $faseIds->take($maxFasi);
                $deleteIds = $faseIds->slice($maxFasi)->filter();
                if ($deleteIds->isNotEmpty()) {
                    $deleted = OrdineFase::whereIn('id', $deleteIds)
                        ->whereRaw("stato REGEXP '^[0-9]+$' AND stato <= 1")
                        ->where('manuale', false)
                        ->delete();
                    $fasiEliminate += $deleted;
                }
            }
        }

        // Pulizia duplicati stampa a caldo per commessa: 1 sola per commessa per fase_catalogo
        $repartiStampaCaldo = Reparto::where('nome', 'stampa a caldo')->pluck('id');
        if ($repartiStampaCaldo->isNotEmpty()) {
            $dupCaldo = DB::table('ordine_fasi')
                ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
                ->join('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
                ->select('ordini.commessa', 'ordine_fasi.fase_catalogo_id', DB::raw('COUNT(*) as cnt'))
                ->whereIn('fasi_catalogo.reparto_id', $repartiStampaCaldo)
                ->whereNull('ordine_fasi.deleted_at')
                ->where('ordine_fasi.manuale', false)
                ->groupBy('ordini.commessa', 'ordine_fasi.fase_catalogo_id')
                ->having('cnt', '>', 1)
                ->get();

            foreach ($dupCaldo as $dup) {
                $faseIds = OrdineFase::withTrashed()
                    ->where('fase_catalogo_id', $dup->fase_catalogo_id)
                    ->whereHas('ordine', fn($q) => $q->where('commessa', $dup->commessa))
                    ->whereNull('deleted_at')
                    ->orderBy('id')
                    ->pluck('id');

                $keepId = $faseIds->first();
                $deleteIds = $faseIds->slice(1)->filter();
                if ($deleteIds->isNotEmpty()) {
                    $deleted = OrdineFase::whereIn('id', $deleteIds)
                        ->whereRaw("stato REGEXP '^[0-9]+$' AND stato <= 1")
                        ->where('manuale', false)
                        ->delete();
                    $fasiEliminate += $deleted;
                }
            }
        }

        // Pulizia duplicati BRT1 per commessa: 1 sola per commessa
        $repartiSpedizione = Reparto::where('nome', 'spedizione')->pluck('id');
        if ($repartiSpedizione->isNotEmpty()) {
            $dupBrt = DB::table('ordine_fasi')
                ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
                ->join('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
                ->select('ordini.commessa', 'ordine_fasi.fase_catalogo_id', DB::raw('COUNT(*) as cnt'))
                ->whereIn('fasi_catalogo.reparto_id', $repartiSpedizione)
                ->whereNull('ordine_fasi.deleted_at')
                ->where('ordine_fasi.manuale', false)
                ->groupBy('ordini.commessa', 'ordine_fasi.fase_catalogo_id')
                ->having('cnt', '>', 1)
                ->get();

            foreach ($dupBrt as $dup) {
                $faseIds = OrdineFase::withTrashed()
                    ->where('fase_catalogo_id', $dup->fase_catalogo_id)
                    ->whereHas('ordine', fn($q) => $q->where('commessa', $dup->commessa))
                    ->whereNull('deleted_at')
                    ->orderBy('id')
                    ->pluck('id');

                $keepId = $faseIds->first();
                $deleteIds = $faseIds->slice(1)->filter();
                if ($deleteIds->isNotEmpty()) {
                    $deleted = OrdineFase::whereIn('id', $deleteIds)
                        ->whereRaw("stato REGEXP '^[0-9]+$' AND stato <= 1")
                        ->where('manuale', false)
                        ->delete();
                    $fasiEliminate += $deleted;
                }
            }
        }

        // Pulizia duplicati TAGLIACARTE per commessa: 1 sola per commessa per fase_catalogo
        $repartiTagliacarte = Reparto::where('nome', 'tagliacarte')->pluck('id');
        if ($repartiTagliacarte->isNotEmpty()) {
            $dupTaglio = DB::table('ordine_fasi')
                ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
                ->join('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
                ->select('ordini.commessa', 'ordine_fasi.fase_catalogo_id', DB::raw('COUNT(*) as cnt'))
                ->whereIn('fasi_catalogo.reparto_id', $repartiTagliacarte)
                ->whereNull('ordine_fasi.deleted_at')
                ->where('ordine_fasi.manuale', false)
                ->groupBy('ordini.commessa', 'ordine_fasi.fase_catalogo_id')
                ->having('cnt', '>', 1)
                ->get();

            foreach ($dupTaglio as $dup) {
                $faseIds = OrdineFase::withTrashed()
                    ->where('fase_catalogo_id', $dup->fase_catalogo_id)
                    ->whereHas('ordine', fn($q) => $q->where('commessa', $dup->commessa))
                    ->whereNull('deleted_at')
                    ->orderBy('id')
                    ->pluck('id');

                $keepId = $faseIds->first();
                $deleteIds = $faseIds->slice(1)->filter();
                if ($deleteIds->isNotEmpty()) {
                    $deleted = OrdineFase::whereIn('id', $deleteIds)
                        ->whereRaw("stato REGEXP '^[0-9]+$' AND stato <= 1")
                        ->where('manuale', false)
                        ->delete();
                    $fasiEliminate += $deleted;
                }
            }
        }

        // Pulizia duplicati PLASTIFICAZIONE per commessa: 1 sola per commessa per fase_catalogo
        $repartiPlast = Reparto::where('nome', 'plastificazione')->pluck('id');
        if ($repartiPlast->isNotEmpty()) {
            $dupPlast = DB::table('ordine_fasi')
                ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
                ->join('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
                ->select('ordini.commessa', 'ordine_fasi.fase_catalogo_id', DB::raw('COUNT(*) as cnt'))
                ->whereIn('fasi_catalogo.reparto_id', $repartiPlast)
                ->whereNull('ordine_fasi.deleted_at')
                ->where('ordine_fasi.manuale', false)
                ->groupBy('ordini.commessa', 'ordine_fasi.fase_catalogo_id')
                ->having('cnt', '>', 1)
                ->get();

            foreach ($dupPlast as $dup) {
                $keepIds = OrdineFase::where('fase_catalogo_id', $dup->fase_catalogo_id)
                    ->whereHas('ordine', fn($q) => $q->where('commessa', $dup->commessa))
                    ->orderBy('id')
                    ->pluck('id');
                if ($keepIds->count() <= 1) continue;
                $deleteIds = $keepIds->slice(1)->values();
                if ($deleteIds->isNotEmpty()) {
                    $deleted = OrdineFase::whereIn('id', $deleteIds)
                        ->whereRaw("stato REGEXP '^[0-9]+$' AND stato <= 1")
                        ->where('manuale', false)
                        ->delete();
                    $fasiEliminate += $deleted;
                }
            }
        }

        if ($fasiEliminate > 0) {
            Log::info("OndaSync: eliminati $fasiEliminate duplicati");
        }

        // 2. Raggruppa per PrdIdDoc (documento di produzione Onda)
        // Ogni IdDoc è un articolo separato nella stessa commessa (es. copertina + interno)
        $gruppi = collect($righeOnda)->groupBy(function ($riga) {
            return $riga->CodCommessa . '|' . ($riga->PrdIdDoc ?? $riga->CodArt);
        });

        // Track fasi deduplicate per commessa (1 sola per commessa per fustella, digitale, finitura digitale)
        $dedupPerCommessa = [];
        // Track qta distinte per dedup (chiave => [qta1, qta2, ...])
        $dedupQta = [];

        foreach ($gruppi as $chiave => $righe) {
            $prima = $righe->first();
            $commessa = trim($prima->CodCommessa ?? '');
            $codArt = trim($prima->CodArt ?? '');
            $descrizione = preg_replace('/\s+/', ' ', trim($prima->OC_Descrizione ?? ''));

            if (!$commessa) continue;

            // 3. Upsert ordine (dedup per commessa + cod_art + descrizione)
            //    Se la descrizione Onda è vuota, usa l'ordine esistente con stessa commessa+cod_art
            $ordine = Ordine::where('commessa', $commessa)
                ->where('cod_art', $codArt)
                ->where('descrizione', $descrizione)
                ->first();

            if (!$ordine && $descrizione === '') {
                $ordine = Ordine::where('commessa', $commessa)
                    ->where('cod_art', $codArt)
                    ->first();
            }

            $datiOrdine = [
                'cliente_nome'           => trim($prima->ClienteNome ?? ''),
                'data_prevista_consegna' => $prima->DataPresConsegna && date('Y', strtotime($prima->DataPresConsegna)) >= 2024 ? date('Y-m-d', strtotime($prima->DataPresConsegna)) : null,
                'qta_richiesta'          => $prima->QtaDaProdurre ?? 0,
                'cod_carta'              => trim($prima->CodCarta ?? ''),
                'carta'                  => trim($prima->DescrizioneCarta ?? ''),
                'qta_carta'              => $prima->QtaCarta ?? 0,
                'UM_carta'               => trim($prima->UMCarta ?? ''),
                'supp_base_cm'           => ($prima->SuppBaseCM ?? 0) > 0 ? (float) $prima->SuppBaseCM : null,
                'supp_altezza_cm'        => ($prima->SuppAltezzaCM ?? 0) > 0 ? (float) $prima->SuppAltezzaCM : null,
                'resa'                   => ($prima->Resa ?? 0) > 0 ? (int) $prima->Resa : null,
                'tot_supporti'           => ($prima->TotSupporti ?? 0) > 0 ? (int) $prima->TotSupporti : null,
                'note_prestampa'         => trim($prima->NotePrestampa ?? ''),
                'responsabile'           => trim($prima->Responsabile ?? ''),
                'commento_produzione'    => trim($prima->CommentoProduzione ?? ''),
                'ordine_cliente'         => trim($prima->OrdineCliente ?? '') ?: null,
                'valore_ordine'          => ($prima->TotMerce ?? 0) > 0 ? (float) $prima->TotMerce : null,
                'costo_materiali'        => ($prima->CostoMateriali ?? 0) > 0 ? (float) $prima->CostoMateriali : null,
            ];

            if ($ordine) {
                // Non sovrascrivere data_prevista_consegna se modificata manualmente nel MES
                $dataConsegnaOnda = $datiOrdine['data_prevista_consegna'];
                if ($ordine->data_prevista_consegna && $ordine->data_prevista_consegna != $dataConsegnaOnda) {
                    unset($datiOrdine['data_prevista_consegna']);
                }
                // Non sovrascrivere cliente_nome se modificato manualmente nel MES
                $clienteOnda = $datiOrdine['cliente_nome'];
                if ($ordine->cliente_nome && $ordine->cliente_nome !== $clienteOnda && !empty($ordine->cliente_nome)) {
                    unset($datiOrdine['cliente_nome']);
                }
                // Aggiorna descrizione da Onda se diversa (corregge import iniziale errato)
                if ($descrizione && $ordine->descrizione !== $descrizione) {
                    $ordine->descrizione = $descrizione;
                }
                $ordine->update($datiOrdine);
                $ordiniAggiornati++;
                $logOrdiniAggiornati[] = $commessa;
            } else {
                $ordine = Ordine::create(array_merge([
                    'commessa'    => $commessa,
                    'cod_art'     => $codArt,
                    'descrizione' => $descrizione,
                    'stato'       => 0,
                    'data_registrazione' => $prima->DataRegistrazione ? date('Y-m-d', strtotime($prima->DataRegistrazione)) : $oggi,
                ], $datiOrdine));
                $ordiniCreati++;
                $logOrdiniCreati[] = $commessa . ' - ' . trim($prima->ClienteNome ?? '');
            }

            // 4. Assicura che esista sempre la fase BRT1 (spedizione) — 1 sola per commessa
            $hasBrt = OrdineFase::withTrashed()
                ->where(function ($q) {
                    $q->where('fase', 'BRT1')->orWhere('fase', 'brt1');
                })
                ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                ->exists();

            if (!$hasBrt) {
                $repartoBrt = Reparto::firstOrCreate(['nome' => 'spedizione']);
                $faseCatalogoBrt = FasiCatalogo::firstOrCreate(
                    ['nome' => 'BRT1'],
                    ['reparto_id' => $repartoBrt->id]
                );
                OrdineFase::create([
                    'ordine_id'        => $ordine->id,
                    'fase'             => 'BRT1',
                    'fase_catalogo_id' => $faseCatalogoBrt->id,
                    'qta_fase'         => $ordine->qta_richiesta ?? 0,
                    'um'               => 'FG',
                    'priorita'         => $mappaPriorita['BRT1'] ?? 96,
                    'stato'            => 0,
                ]);
                $fasiCreate++;
                $logFasiCreate[] = $commessa . ' → BRT1 (auto)';
            }

            // 5. Fasi: raccogli fasi uniche per questo gruppo
            $fasiViste = [];
            foreach ($righe as $riga) {
                $faseNome = trim($riga->CodFase ?? '');
                if (!$faseNome) continue;

                // Rimappa STAMPA generico in base alla macchina assegnata in Onda
                if ($faseNome === 'STAMPA') {
                    $macchina = trim($riga->CodMacchina ?? '');
                    // "NO STAMPA" = nessuna stampa, skip fase
                    if (stripos($macchina, 'NO STAMPA') !== false || $macchina === 'NO STAMPA') {
                        continue;
                    }
                    if (stripos($macchina, 'INDIGO') !== false) {
                        // HPINDIGOCO → STAMPAINDIGO, HPINDIGOBN → STAMPAINDIGOBN
                        if (stripos($macchina, 'BN') !== false || stripos($macchina, 'MONO') !== false) {
                            $faseNome = 'STAMPAINDIGOBN';
                        } else {
                            $faseNome = 'STAMPAINDIGO';
                        }
                    } elseif (preg_match('/XL106[.-]?(\d+)/i', $macchina, $m)) {
                        // XL106-1 → STAMPAXL106.1
                        $faseNome = 'STAMPAXL106.' . $m[1];
                    } else {
                        // STAMPA generico (qualsiasi macchina o nessuna) → offset
                        $faseNome = 'STAMPAXL106';
                    }
                }

                // Chiave dedup: include PrdIdDoc per non bloccare fasi tra ordini diversi
                $chiaveFase = $riga->PrdIdDoc . '|' . $faseNome . '|' . ($riga->QtaDaLavorare ?? 0);
                if (isset($fasiViste[$chiaveFase])) continue;
                $fasiViste[$chiaveFase] = true;

                // TipoRiga da Onda: 1=interna, 2=esterna
                $tipoRigaOnda = (int)($riga->TipoRigaFase ?? 1);
                $faseEsterna = ($tipoRigaOnda === 2);

                // Reparto: se esterna (TipoRiga=2) → esterno, altrimenti dalla mappa
                if ($faseEsterna) {
                    $repartoNome = 'esterno';
                } else {
                    $repartoNome = $mappaReparti[$faseNome] ?? 'legatoria';
                }
                $tipo = $tipiFase[$faseNome] ?? 'monofase';
                $prioritaFase = $mappaPriorita[$faseNome] ?? 500;

                // STAMPAXL106: max 2 fasi solo per cod_art multi-passaggio
                if (str_starts_with($faseNome, 'STAMPAXL106') && in_array($codArt, [
                    'Volumi','Vassoio','Vassoi','SPILLATI.OFFSET','SPILLATI.DIGITALE',
                    'SOVRACOPERTA','RIVISTE.FRECCIA','riviste','RIVISTA.FRECCIA.128PP',
                    'RICETTARI','Raccoglitori','Quaderni','Opuscoli','Libro.di.bordo',
                    'Libricino','LibriBN','Libri','INSERTO.RIVISTA.NOTE.4pp',
                    'I.Volumi','I.riviste','I.Raccoglitori','I.Quaderni','I.Poster',
                    'I.Opuscoli','I.Menu','I.Libricino','I.Libri','I.copertina',
                    'I.cataloghi','I.cartoline','I.Calendari.da.Tavolo',
                    'I.Calendari.da.Muro','I.Calendari','I.Block.Notes',
                    'I.Blocchi.autocopianti','I.Blocchi','I.Bilanci',
                    'Espositori.da.Terra','Espositori.da.banco','Depliant','COPERTINA',
                    'cataloghi','Calendari.da.Tavolo','Calendari.da.Muro','Calendari',
                    'BROSSURATI.OFFSET','BROSSURATI.DIGITALE','brochure','Block.Notes',
                    'Blocchi.Mod.TI','Blocchi.Mod.R1','Blocchi.Mod.K','Blocchi.Mod.CH69',
                    'Blocchi.autocopianti.M40a','Blocchi.autocopianti','Blocchi','Bilanci',
                ])) {
                    $tipo = 'max 2 fasi';
                }

                $reparto = Reparto::firstOrCreate(['nome' => $repartoNome]);
                $faseCatalogo = FasiCatalogo::updateOrCreate(
                    ['nome' => $faseNome],
                    ['reparto_id' => $reparto->id]
                );

                // Dedup fustella cilindrica per ordine (1 fase per cod_art, non per commessa)
                // Commesse vecchie gia deduplicate non vengono toccate
                if ($repartoNome === 'fustella cilindrica') {
                    $chiaveDedup = $commessa . '|fustcil|' . $faseNome . '|' . $codArt;
                    if (isset($dedupPerCommessa[$chiaveDedup])) continue;
                    $existsInOrdine = OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                        ->where('ordine_id', $ordine->id)
                        ->exists();
                    $dedupPerCommessa[$chiaveDedup] = true;
                    if ($existsInOrdine) continue;
                }

                // Dedup fustella piana/generica per commessa: 1 sola per commessa per fase_catalogo
                // (FUSTBOBST75X106 e FUSTBOBSTRILIEVI sono fasi diverse → possono coesistere)
                // qta_fase = somma delle qta distinte di tutti gli articoli
                if (in_array($repartoNome, ['fustella piana', 'fustella'])) {
                    $chiaveDedup = $commessa . '|fust|' . $faseNome;
                    $qtaRiga = (int)($riga->QtaDaLavorare ?? 0);

                    if (isset($dedupPerCommessa[$chiaveDedup])) {
                        if ($qtaRiga > 0 && !in_array($qtaRiga, $dedupQta[$chiaveDedup] ?? [])) {
                            $dedupQta[$chiaveDedup][] = $qtaRiga;
                            $nuovaQta = array_sum($dedupQta[$chiaveDedup]);
                            OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                                ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                                ->update(['qta_fase' => $nuovaQta]);
                        }
                        continue;
                    }

                    $existsInCommessa = OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                        ->exists();

                    $dedupPerCommessa[$chiaveDedup] = true;
                    $dedupQta[$chiaveDedup] = $qtaRiga > 0 ? [$qtaRiga] : [];
                    if ($existsInCommessa) {
                        $scartiValue = $scartiMacchine[trim($riga->CodMacchina ?? '')] ?? null;
                        if ($scartiValue !== null) {
                            OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                                ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                                ->whereNull('scarti_previsti')
                                ->update(['scarti_previsti' => $scartiValue]);
                        }
                        continue;
                    }
                }

                // Dedup digitale/finitura digitale: 1 sola fase per commessa SE stesso articolo (cod_art)
                if (in_array($repartoNome, ['digitale', 'finitura digitale'])) {
                    $chiaveDedup = $commessa . '|' . $faseNome . '|' . $codArt;
                    if (isset($dedupPerCommessa[$chiaveDedup])) continue;

                    $existsInCommessa = OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa)
                            ->where('cod_art', $codArt))
                        ->exists();

                    $dedupPerCommessa[$chiaveDedup] = true;
                    if ($existsInCommessa) {
                        $scartiValue = $scartiMacchine[trim($riga->CodMacchina ?? '')] ?? null;
                        if ($scartiValue !== null) {
                            OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                                ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa)
                                    ->where('cod_art', $codArt))
                                ->whereNull('scarti_previsti')
                                ->update(['scarti_previsti' => $scartiValue]);
                        }
                        continue;
                    }
                }

                // Dedup stampa offset per commessa: 1 sola STAMPAXL106 per commessa (max 2 per cod_art multi-passaggio)
                if ($repartoNome === 'stampa offset' && str_starts_with($faseNome, 'STAMPAXL106')) {
                    $chiaveDedup = $commessa . '|stampa_offset';
                    $maxStampa = in_array($codArt, $codArtMax2) ? 2 : 1;
                    $qtaRiga = (int)($riga->QtaDaLavorare ?? 0);

                    if (!isset($dedupPerCommessa[$chiaveDedup])) {
                        $existCount = OrdineFase::whereHas('faseCatalogo', fn($q) =>
                                $q->whereIn('reparto_id', $repartiStampaOffset)
                                  ->where('nome', 'like', 'STAMPAXL106%'))
                            ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                            ->count();
                        $dedupPerCommessa[$chiaveDedup] = $existCount;
                        $dedupQta[$chiaveDedup] = $qtaRiga > 0 ? [$qtaRiga] : [];
                    }

                    if ($dedupPerCommessa[$chiaveDedup] >= $maxStampa) {
                        if ($qtaRiga > 0 && !in_array($qtaRiga, $dedupQta[$chiaveDedup] ?? [])) {
                            $dedupQta[$chiaveDedup][] = $qtaRiga;
                            $nuovaQta = array_sum($dedupQta[$chiaveDedup]);
                            OrdineFase::whereHas('faseCatalogo', fn($q) =>
                                    $q->whereIn('reparto_id', $repartiStampaOffset)
                                      ->where('nome', 'like', 'STAMPAXL106%'))
                                ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                                ->update(['qta_fase' => $nuovaQta]);
                        }
                        continue;
                    }

                    // Controlla se questa specifica variante esiste già
                    $existsThisVariant = OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                        ->exists();

                    if ($existsThisVariant) {
                        $scartiValue = $scartiMacchine[trim($riga->CodMacchina ?? '')] ?? null;
                        if ($scartiValue !== null) {
                            OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                                ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                                ->whereNull('scarti_previsti')
                                ->update(['scarti_previsti' => $scartiValue]);
                        }
                        continue;
                    }

                    // Crea: incrementa contatore
                    $dedupPerCommessa[$chiaveDedup]++;
                    if ($qtaRiga > 0) $dedupQta[$chiaveDedup][] = $qtaRiga;
                }

                // Dedup stampa a caldo per commessa: 1 sola per commessa per fase_catalogo
                if ($repartoNome === 'stampa a caldo') {
                    $chiaveDedup = $commessa . '|stampa_caldo|' . $faseNome;
                    $qtaRiga = (int)($riga->QtaDaLavorare ?? 0);

                    if (isset($dedupPerCommessa[$chiaveDedup])) {
                        if ($qtaRiga > 0 && !in_array($qtaRiga, $dedupQta[$chiaveDedup] ?? [])) {
                            $dedupQta[$chiaveDedup][] = $qtaRiga;
                            $nuovaQta = array_sum($dedupQta[$chiaveDedup]);
                            OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                                ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                                ->update(['qta_fase' => $nuovaQta]);
                        }
                        continue;
                    }

                    $existsInCommessa = OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                        ->exists();

                    $dedupPerCommessa[$chiaveDedup] = true;
                    $dedupQta[$chiaveDedup] = $qtaRiga > 0 ? [$qtaRiga] : [];
                    if ($existsInCommessa) {
                        continue;
                    }
                }

                // Dedup BRT1 per commessa: 1 sola per commessa
                if ($repartoNome === 'spedizione') {
                    $chiaveDedup = $commessa . '|spedizione|' . $faseNome;
                    $qtaRiga = (int)($riga->QtaDaLavorare ?? 0);

                    if (isset($dedupPerCommessa[$chiaveDedup])) {
                        if ($qtaRiga > 0 && !in_array($qtaRiga, $dedupQta[$chiaveDedup] ?? [])) {
                            $dedupQta[$chiaveDedup][] = $qtaRiga;
                            $nuovaQta = array_sum($dedupQta[$chiaveDedup]);
                            OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                                ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                                ->update(['qta_fase' => $nuovaQta]);
                        }
                        continue;
                    }

                    $existsInCommessa = OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                        ->exists();

                    $dedupPerCommessa[$chiaveDedup] = true;
                    $dedupQta[$chiaveDedup] = $qtaRiga > 0 ? [$qtaRiga] : [];
                    if ($existsInCommessa) {
                        continue;
                    }
                }

                // Dedup TAGLIACARTE per ordine: 1 sola per ordine_id per fase_catalogo
                // Include withTrashed per non ricreare fasi eliminate manualmente
                if ($repartoNome === 'tagliacarte') {
                    $chiaveDedup = $ordine->id . '|tagliacarte|' . $faseNome;
                    $qtaRiga = (int)($riga->QtaDaLavorare ?? 0);

                    if (isset($dedupPerCommessa[$chiaveDedup])) {
                        if ($qtaRiga > 0 && !in_array($qtaRiga, $dedupQta[$chiaveDedup] ?? [])) {
                            $dedupQta[$chiaveDedup][] = $qtaRiga;
                            $nuovaQta = array_sum($dedupQta[$chiaveDedup]);
                            OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                                ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                                ->update(['qta_fase' => $nuovaQta]);
                        }
                        continue;
                    }

                    $existsInCommessa = OrdineFase::withTrashed()
                        ->where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                        ->exists();

                    $dedupPerCommessa[$chiaveDedup] = true;
                    $dedupQta[$chiaveDedup] = $qtaRiga > 0 ? [$qtaRiga] : [];
                    if ($existsInCommessa) {
                        continue;
                    }
                }

                // Dedup brossura esterna per commessa: 1 sola per commessa per fase_catalogo
                if ($repartoNome === 'esterno' && str_starts_with($faseNome, 'EXTBROSS')) {
                    $chiaveDedup = $commessa . '|extbross|' . $faseNome;
                    if (isset($dedupPerCommessa[$chiaveDedup])) continue;

                    $existsInCommessa = OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                        ->exists();

                    $dedupPerCommessa[$chiaveDedup] = true;
                    if ($existsInCommessa) continue;
                }

                // Dedup EXTALLEST.SHOPPER per commessa + descrizione: 1 per combinazione
                if ($repartoNome === 'esterno' && str_contains(strtoupper($faseNome), 'EXTALLEST')) {
                    $descDedup = strtolower(trim($ordine->descrizione ?? ''));
                    $chiaveDedup = $commessa . '|extallest|' . $faseNome . '|' . $descDedup;
                    if (isset($dedupPerCommessa[$chiaveDedup])) continue;

                    $existsInCommessa = OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa)
                            ->where('descrizione', $ordine->descrizione))
                        ->whereNull('deleted_at')
                        ->exists();

                    $dedupPerCommessa[$chiaveDedup] = true;
                    if ($existsInCommessa) continue;
                }

                // Dedup plastificazione per commessa: 1 sola per commessa per fase_catalogo
                // (stessa plastificazione su articoli diversi = unico passaggio macchina)
                if ($repartoNome === 'plastificazione') {
                    $chiaveDedup = $commessa . '|plast|' . $faseNome;
                    $qtaRiga = (int)($riga->QtaDaLavorare ?? 0);

                    if (isset($dedupPerCommessa[$chiaveDedup])) {
                        if ($qtaRiga > 0 && !in_array($qtaRiga, $dedupQta[$chiaveDedup] ?? [])) {
                            $dedupQta[$chiaveDedup][] = $qtaRiga;
                            $nuovaQta = array_sum($dedupQta[$chiaveDedup]);
                            OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                                ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                                ->update(['qta_fase' => $nuovaQta]);
                        }
                        continue;
                    }

                    $existsInCommessa = OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                        ->exists();

                    $dedupPerCommessa[$chiaveDedup] = true;
                    $dedupQta[$chiaveDedup] = $qtaRiga > 0 ? [$qtaRiga] : [];
                    if ($existsInCommessa) {
                        continue;
                    }
                }

                $dataFase = [
                    'ordine_id'        => $ordine->id,
                    'fase'             => $faseNome,
                    'fase_catalogo_id' => $faseCatalogo->id,
                    'qta_fase'         => $riga->QtaDaLavorare ?? 0,
                    'um'               => trim($riga->UMFase ?? 'FG'),
                    'priorita'         => $prioritaFase,
                    'stato'            => 0,
                    'scarti_previsti'   => $scartiMacchine[trim($riga->CodMacchina ?? '')] ?? null,
                    'sequenza'         => config('sequenza_fasi')[$faseNome] ?? 500,
                    'esterno'          => $repartoNome === 'esterno',
                ];

                // Se la fase è stata rimappata da STAMPA generico, aggiorna la fase esistente
                $faseOriginaleOnda = trim($riga->CodFase ?? '');
                if ($faseOriginaleOnda === 'STAMPA' && $faseNome !== 'STAMPA') {
                    $faseStampaGenerica = OrdineFase::where('ordine_id', $ordine->id)
                        ->where('fase', 'STAMPA')
                        ->first();
                    if ($faseStampaGenerica) {
                        $faseStampaGenerica->update([
                            'fase'             => $faseNome,
                            'fase_catalogo_id' => $faseCatalogo->id,
                            'qta_fase'         => $riga->QtaDaLavorare ?? 0,
                            'um'               => trim($riga->UMFase ?? 'FG'),
                            'priorita'         => $prioritaFase,
                            'scarti_previsti'   => $scartiMacchine[trim($riga->CodMacchina ?? '')] ?? null,
                        ]);
                        $fasiCreate++;
                        $logFasiCreate[] = $commessa . ' → ' . $faseNome;
                        continue;
                    }
                }

                // Logica dedup: monofase / max 2 fasi / multifase
                // Usa fase_catalogo_id (FK intera) per check più affidabile
                if ($tipo === 'monofase') {
                    $exists = OrdineFase::withTrashed()
                        ->where('ordine_id', $ordine->id)
                        ->where('fase_catalogo_id', $faseCatalogo->id)
                        ->exists();

                    if (!$exists) {
                        OrdineFase::create($dataFase);
                        $fasiCreate++;
                        $logFasiCreate[] = $commessa . ' → ' . $faseNome;
                    }

                } elseif ($tipo === 'max 2 fasi') {
                    $count = OrdineFase::withTrashed()
                        ->where('ordine_id', $ordine->id)
                        ->where('fase_catalogo_id', $faseCatalogo->id)
                        ->count();

                    if ($count < 2) {
                        OrdineFase::create($dataFase);
                        $fasiCreate++;
                        $logFasiCreate[] = $commessa . ' → ' . $faseNome;
                    }

                } else {
                    $exists = OrdineFase::withTrashed()
                        ->where('ordine_id', $ordine->id)
                        ->where('fase_catalogo_id', $faseCatalogo->id)
                        ->exists();

                    if (!$exists) {
                        OrdineFase::create($dataFase);
                        $fasiCreate++;
                        $logFasiCreate[] = $commessa . ' → ' . $faseNome;
                    }
                }

                // Aggiorna scarti_previsti su fasi esistenti se mancante
                $scartiValue = $scartiMacchine[trim($riga->CodMacchina ?? '')] ?? null;
                if ($scartiValue !== null) {
                    OrdineFase::where('ordine_id', $ordine->id)
                        ->where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereNull('scarti_previsti')
                        ->update(['scarti_previsti' => $scartiValue]);
                }
            }
        }

        // NOTA: ricalcolo priorità/ore NON in sync Onda (lento).
        // DashboardOwnerController::index() calcola in memoria al page load.

        // Ricalcola stati (promuove fasi con predecessori terminati, rispetta modifiche manuali)
        FaseStatoService::ricalcolaTutti();

        Log::info("Sync Onda completato: $ordiniCreati creati, $ordiniAggiornati aggiornati, $fasiCreate fasi");

        return [
            'inseriti'              => $ordiniCreati,
            'aggiornati'            => $ordiniAggiornati,
            'errori'                => 0,
            'ordini_creati'         => $ordiniCreati,
            'ordini_aggiornati'     => $ordiniAggiornati,
            'fasi_create'           => $fasiCreate,
            'duplicati_rimossi'     => $fasiEliminate,
            'log_ordini_creati'     => $logOrdiniCreati,
            'log_ordini_aggiornati' => $logOrdiniAggiornati,
            'log_fasi_create'       => $logFasiCreate,
        ];
    }

    /**
     * Espone l'adapter ai test (vedi OrdineSyncServiceTest).
     */
    public function adapter(): OndaErpInterface
    {
        return $this->onda;
    }
}
