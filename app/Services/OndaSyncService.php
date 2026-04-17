<?php

namespace App\Services;

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\FasiCatalogo;
use App\Models\Reparto;
use App\Models\DdtSpedizione;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FaseStatoService;
use App\Services\DdtPdfService;

class OndaSyncService
{
    public static function sincronizza(): array
    {
        $ordiniCreati = 0;
        $ordiniAggiornati = 0;
        $fasiCreate = 0;
        $logOrdiniCreati = [];
        $logOrdiniAggiornati = [];
        $logFasiCreate = [];

        $mappaReparti = self::getMappaReparti();
        $tipiFase = self::getTipoReparto();
        $mappaPriorita = config('fasi_priorita');
        $oggi = now()->format('Y-m-d');

        // Pre-fetch scarti previsti per macchina da Onda
        $scartiMacchine = collect(DB::connection('onda')->select(
            "SELECT CodMacchina, OC_FogliScartoIniz FROM PRDMacchinari WHERE OC_FogliScartoIniz > 0"
        ))->pluck('OC_FogliScartoIniz', 'CodMacchina')->toArray();

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

        // 1. Query ordini da Onda: solo quelli ancora attivi nel MES o nuovi
        $righeOnda = DB::connection('onda')->select("
            SELECT
                t.CodCommessa,
                p.IdDoc AS PrdIdDoc,
                p.CodArt,
                p.OC_Descrizione,
                COALESCE(NULLIF(p.NCPRagioneSociale, ''), a.RagioneSociale) AS ClienteNome,
                p.QtaDaProdurre,
                p.DataPresConsegna,
                t.DataRegistrazione,
                carta.CodArt AS CodCarta,
                carta.Descrizione AS DescrizioneCarta,
                carta.Qta AS QtaCarta,
                carta.CodUnMis AS UMCarta,
                t.TotMerce,
                t.ncpcommentoprestampa AS NotePrestampa,
                t.ncprespocommessa AS Responsabile,
                t.OC_CommentoProduz AS CommentoProduzione,
                t.ncpordinecliente AS OrdineCliente,
                materiali.CostoMateriali,
                supporto.OC_SuppBaseCM AS SuppBaseCM,
                supporto.OC_SuppAltezzaCM AS SuppAltezzaCM,
                supporto.OC_Resa AS Resa,
                supporto.OC_TotSupporti AS TotSupporti,
                f.CodFase,
                f.CodMacchina,
                f.QtaDaLavorare,
                f.CodUnMis AS UMFase,
                f.TipoRiga AS TipoRigaFase,
                rigaAtt.CodArt AS CodFaseRiga
            FROM ATTDocTeste t
            INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
            LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
            LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
            OUTER APPLY (
                SELECT TOP 1 r.CodArt
                FROM ATTDocRighe r
                WHERE r.IdDoc = t.IdDoc
                  AND (r.CodArt = f.CodFase OR r.CodArt = SUBSTRING(f.CodFase, 4, LEN(f.CodFase)))
            ) rigaAtt
            OUTER APPLY (
                SELECT TOP 1 r.CodArt, r.Descrizione, r.Qta, r.CodUnMis
                FROM PRDDocRighe r WHERE r.IdDoc = p.IdDoc
                ORDER BY r.Sequenza
            ) carta
            OUTER APPLY (
                SELECT SUM(r2.Totale) AS CostoMateriali
                FROM PRDDocRighe r2 WHERE r2.IdDoc = p.IdDoc
            ) materiali
            OUTER APPLY (
                SELECT TOP 1 e.OC_SuppBaseCM, e.OC_SuppAltezzaCM, e.OC_Resa, e.OC_TotSupporti
                FROM OC_ATTDocRigheExt e
                WHERE e.OC_IdDoc = t.IdDoc
                  AND e.OC_CodArtSupporto IS NOT NULL AND e.OC_CodArtSupporto != ''
            ) supporto
            WHERE t.TipoDocumento = '2'
              AND t.DataRegistrazione >= CAST('20260227' AS datetime)
        ");

        if (empty($righeOnda)) {
            return ['ordini_creati' => 0, 'ordini_aggiornati' => 0, 'fasi_create' => 0];
        }

        // Filtra via le commesse gia completate nel MES
        if (!empty($commesseCompletate)) {
            $completateSet = array_flip($commesseCompletate);
            $prima = count($righeOnda);
            $righeOnda = array_values(array_filter($righeOnda, fn($r) => !isset($completateSet[$r->CodCommessa])));
            Log::info("OndaSync: filtrate " . ($prima - count($righeOnda)) . " righe di commesse completate, restano " . count($righeOnda));
        }

        if (empty($righeOnda)) {
            return ['ordini_creati' => 0, 'ordini_aggiornati' => 0, 'fasi_create' => 0];
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
            $hasBrt = OrdineFase::where(function ($q) {
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
                    $exists = OrdineFase::where('ordine_id', $ordine->id)
                        ->where('fase_catalogo_id', $faseCatalogo->id)
                        ->exists();

                    if (!$exists) {
                        OrdineFase::create($dataFase);
                        $fasiCreate++;
                        $logFasiCreate[] = $commessa . ' → ' . $faseNome;
                    }

                } elseif ($tipo === 'max 2 fasi') {
                    $count = OrdineFase::where('ordine_id', $ordine->id)
                        ->where('fase_catalogo_id', $faseCatalogo->id)
                        ->count();

                    if ($count < 2) {
                        OrdineFase::create($dataFase);
                        $fasiCreate++;
                        $logFasiCreate[] = $commessa . ' → ' . $faseNome;
                    }

                } else {
                    $exists = OrdineFase::where('ordine_id', $ordine->id)
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

        // Ricalcola priorità e ore per tutte le fasi
        $controller = app(\App\Http\Controllers\DashboardOwnerController::class);
        $tutteLeFasi = OrdineFase::with('ordine')->get();
        foreach ($tutteLeFasi as $fase) {
            $controller->calcolaOreEPriorita($fase);
            $fase->save();
        }

        // Ricalcola stati (promuove fasi con predecessori terminati, rispetta modifiche manuali)
        FaseStatoService::ricalcolaTutti();

        Log::info("Sync Onda completato: $ordiniCreati creati, $ordiniAggiornati aggiornati, $fasiCreate fasi");

        return [
            'ordini_creati'     => $ordiniCreati,
            'ordini_aggiornati' => $ordiniAggiornati,
            'fasi_create'       => $fasiCreate,
            'log_ordini_creati'     => $logOrdiniCreati,
            'log_ordini_aggiornati' => $logOrdiniAggiornati,
            'log_fasi_create'       => $logFasiCreate,
        ];
    }

    /**
     * Sincronizza una singola commessa da Onda, senza filtro data.
     * Utile per commesse vecchie che il sync normale non prende.
     */
    public static function sincronizzaSingolaCommessa(string $codCommessa): array
    {
        $mappaReparti = self::getMappaReparti();
        $tipiFase = self::getTipoReparto();
        $mappaPriorita = config('fasi_priorita');
        $oggi = now()->format('Y-m-d');

        $scartiMacchine = collect(DB::connection('onda')->select(
            "SELECT CodMacchina, OC_FogliScartoIniz FROM PRDMacchinari WHERE OC_FogliScartoIniz > 0"
        ))->pluck('OC_FogliScartoIniz', 'CodMacchina')->toArray();

        $righeOnda = DB::connection('onda')->select("
            SELECT
                t.CodCommessa,
                p.IdDoc AS PrdIdDoc,
                p.CodArt,
                p.OC_Descrizione,
                COALESCE(NULLIF(p.NCPRagioneSociale, ''), a.RagioneSociale) AS ClienteNome,
                p.QtaDaProdurre,
                p.DataPresConsegna,
                t.DataRegistrazione,
                carta.CodArt AS CodCarta,
                carta.Descrizione AS DescrizioneCarta,
                carta.Qta AS QtaCarta,
                carta.CodUnMis AS UMCarta,
                t.TotMerce,
                t.ncpcommentoprestampa AS NotePrestampa,
                t.ncprespocommessa AS Responsabile,
                t.OC_CommentoProduz AS CommentoProduzione,
                t.ncpordinecliente AS OrdineCliente,
                materiali.CostoMateriali,
                supporto.OC_SuppBaseCM AS SuppBaseCM,
                supporto.OC_SuppAltezzaCM AS SuppAltezzaCM,
                supporto.OC_Resa AS Resa,
                supporto.OC_TotSupporti AS TotSupporti,
                f.CodFase,
                f.CodMacchina,
                f.QtaDaLavorare,
                f.CodUnMis AS UMFase,
                f.TipoRiga AS TipoRigaFase,
                rigaAtt.CodArt AS CodFaseRiga
            FROM ATTDocTeste t
            INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
            LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
            LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
            OUTER APPLY (
                SELECT TOP 1 r.CodArt
                FROM ATTDocRighe r
                WHERE r.IdDoc = t.IdDoc
                  AND (r.CodArt = f.CodFase OR r.CodArt = SUBSTRING(f.CodFase, 4, LEN(f.CodFase)))
            ) rigaAtt
            OUTER APPLY (
                SELECT TOP 1 r.CodArt, r.Descrizione, r.Qta, r.CodUnMis
                FROM PRDDocRighe r WHERE r.IdDoc = p.IdDoc
                ORDER BY r.Sequenza
            ) carta
            OUTER APPLY (
                SELECT SUM(r2.Totale) AS CostoMateriali
                FROM PRDDocRighe r2 WHERE r2.IdDoc = p.IdDoc
            ) materiali
            OUTER APPLY (
                SELECT TOP 1 e.OC_SuppBaseCM, e.OC_SuppAltezzaCM, e.OC_Resa, e.OC_TotSupporti
                FROM OC_ATTDocRigheExt e
                WHERE e.OC_IdDoc = t.IdDoc
                  AND e.OC_CodArtSupporto IS NOT NULL AND e.OC_CodArtSupporto != ''
            ) supporto
            WHERE t.TipoDocumento = '2'
              AND t.CodCommessa = ?
        ", [$codCommessa]);

        if (empty($righeOnda)) {
            return ['trovata' => false, 'messaggio' => "Commessa $codCommessa non trovata in Onda"];
        }

        $ordiniCreati = 0;
        $ordiniAggiornati = 0;
        $fasiCreate = 0;
        $logFasi = [];

        // Raggruppa per PrdIdDoc (documento di produzione Onda)
        $gruppi = collect($righeOnda)->groupBy(function ($riga) {
            return $riga->CodCommessa . '|' . ($riga->PrdIdDoc ?? $riga->CodArt);
        });

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
        $dedupPerCommessa = [];
        $dedupQta = [];

        foreach ($gruppi as $chiave => $righe) {
            $prima = $righe->first();
            $commessa = trim($prima->CodCommessa ?? '');
            $codArt = trim($prima->CodArt ?? '');
            $descrizione = preg_replace('/\s+/', ' ', trim($prima->OC_Descrizione ?? ''));

            if (!$commessa) continue;

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
                $ordine->update($datiOrdine);
                $ordiniAggiornati++;
            } else {
                $ordine = Ordine::create(array_merge([
                    'commessa'    => $commessa,
                    'cod_art'     => $codArt,
                    'descrizione' => $descrizione,
                    'stato'       => 0,
                    'data_registrazione' => $prima->DataRegistrazione ? date('Y-m-d', strtotime($prima->DataRegistrazione)) : $oggi,
                ], $datiOrdine));
                $ordiniCreati++;
            }

            // BRT1 auto
            $hasBrt = OrdineFase::where(function ($q) {
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
                $logFasi[] = 'BRT1 (auto)';
            }

            // Fasi
            $fasiViste = [];
            foreach ($righe as $riga) {
                $faseNome = trim($riga->CodFase ?? '');
                if (!$faseNome) continue;

                if ($faseNome === 'STAMPA') {
                    $macchina = trim($riga->CodMacchina ?? '');
                    if (stripos($macchina, 'INDIGO') !== false) {
                        $faseNome = (stripos($macchina, 'BN') !== false || stripos($macchina, 'MONO') !== false)
                            ? 'STAMPAINDIGOBN' : 'STAMPAINDIGO';
                    } elseif (preg_match('/XL106[.-]?(\d+)/i', $macchina, $m)) {
                        $faseNome = 'STAMPAXL106.' . $m[1];
                    } else {
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

                if ($faseEsterna) {
                    $repartoNome = 'esterno';
                } else {
                    $repartoNome = $mappaReparti[$faseNome] ?? 'legatoria';
                }
                $tipo = $tipiFase[$faseNome] ?? 'monofase';
                $prioritaFase = $mappaPriorita[$faseNome] ?? 500;

                if (str_starts_with($faseNome, 'STAMPAXL106') && in_array($codArt, $codArtMax2)) {
                    $tipo = 'max 2 fasi';
                }

                $reparto = Reparto::firstOrCreate(['nome' => $repartoNome]);
                $faseCatalogo = FasiCatalogo::updateOrCreate(
                    ['nome' => $faseNome],
                    ['reparto_id' => $reparto->id]
                );

                // Dedup fustella cilindrica per ordine (1 fase per cod_art)
                if ($repartoNome === 'fustella cilindrica') {
                    $chiaveDedup = $commessa . '|fustcil|' . $faseNome . '|' . $codArt;
                    if (isset($dedupPerCommessa[$chiaveDedup])) continue;
                    $existsInOrdine = OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                        ->where('ordine_id', $ordine->id)->exists();
                    $dedupPerCommessa[$chiaveDedup] = true;
                    if ($existsInOrdine) continue;
                }

                // Dedup fustella piana/generica per commessa
                if (in_array($repartoNome, ['fustella piana', 'fustella'])) {
                    $chiaveDedup = $commessa . '|fust|' . $faseNome;
                    if (isset($dedupPerCommessa[$chiaveDedup])) continue;
                    $existsInCommessa = OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))->exists();
                    $dedupPerCommessa[$chiaveDedup] = true;
                    if ($existsInCommessa) continue;
                }

                if ($repartoNome === 'stampa offset' && str_starts_with($faseNome, 'STAMPAXL106')) {
                    $chiaveDedup = $commessa . '|stampa_offset';
                    $maxStampa = in_array($codArt, $codArtMax2) ? 2 : 1;
                    if (!isset($dedupPerCommessa[$chiaveDedup])) {
                        $existCount = OrdineFase::whereHas('faseCatalogo', fn($q) =>
                                $q->whereIn('reparto_id', $repartiStampaOffset)
                                  ->where('nome', 'like', 'STAMPAXL106%'))
                            ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                            ->count();
                        $dedupPerCommessa[$chiaveDedup] = $existCount;
                    }
                    if ($dedupPerCommessa[$chiaveDedup] >= $maxStampa) continue;
                    $existsThisVariant = OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))->exists();
                    if ($existsThisVariant) continue;
                    $dedupPerCommessa[$chiaveDedup]++;
                }

                if ($repartoNome === 'stampa a caldo') {
                    $chiaveDedup = $commessa . '|stampa_caldo|' . $faseNome;
                    if (isset($dedupPerCommessa[$chiaveDedup])) continue;
                    $existsInCommessa = OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))->exists();
                    $dedupPerCommessa[$chiaveDedup] = true;
                    if ($existsInCommessa) continue;
                }

                if ($repartoNome === 'spedizione') {
                    $chiaveDedup = $commessa . '|spedizione|' . $faseNome;
                    if (isset($dedupPerCommessa[$chiaveDedup])) continue;
                    $existsInCommessa = OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))->exists();
                    $dedupPerCommessa[$chiaveDedup] = true;
                    if ($existsInCommessa) continue;
                }

                // Dedup TAGLIACARTE per ordine: 1 sola per ordine_id per fase_catalogo
                // Include withTrashed per non ricreare fasi eliminate manualmente
                if ($repartoNome === 'tagliacarte') {
                    $chiaveDedup = $ordine->id . '|tagliacarte|' . $faseNome;
                    if (isset($dedupPerCommessa[$chiaveDedup])) continue;
                    $existsInCommessa = OrdineFase::withTrashed()
                        ->where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))->exists();
                    $dedupPerCommessa[$chiaveDedup] = true;
                    if ($existsInCommessa) continue;
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
                    'esterno'          => $repartoNome === 'esterno',
                ];

                // Rimappa STAMPA generico
                $faseOriginaleOnda = trim($riga->CodFase ?? '');
                if ($faseOriginaleOnda === 'STAMPA' && $faseNome !== 'STAMPA') {
                    $faseStampaGenerica = OrdineFase::where('ordine_id', $ordine->id)
                        ->where('fase', 'STAMPA')->first();
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
                        $logFasi[] = $faseNome . ' (remapped)';
                        continue;
                    }
                }

                $exists = OrdineFase::where('ordine_id', $ordine->id)
                    ->where('fase_catalogo_id', $faseCatalogo->id)->exists();

                if ($tipo === 'max 2 fasi') {
                    $count = OrdineFase::where('ordine_id', $ordine->id)
                        ->where('fase_catalogo_id', $faseCatalogo->id)->count();
                    if ($count < 2) {
                        OrdineFase::create($dataFase);
                        $fasiCreate++;
                        $logFasi[] = $faseNome;
                    }
                } elseif (!$exists) {
                    OrdineFase::create($dataFase);
                    $fasiCreate++;
                    $logFasi[] = $faseNome;
                }
            }
        }

        // Ricalcola priorità e stati per la commessa
        $controller = app(\App\Http\Controllers\DashboardOwnerController::class);
        $fasiCommessa = OrdineFase::with('ordine')
            ->whereHas('ordine', fn($q) => $q->where('commessa', $codCommessa))
            ->get();
        foreach ($fasiCommessa as $fase) {
            $controller->calcolaOreEPriorita($fase);
            $fase->save();
        }
        FaseStatoService::ricalcolaCommessa($codCommessa);

        return [
            'trovata'           => true,
            'ordini_creati'     => $ordiniCreati,
            'ordini_aggiornati' => $ordiniAggiornati,
            'fasi_create'       => $fasiCreate,
            'fasi'              => $logFasi,
            'messaggio'         => "Commessa $codCommessa: $ordiniCreati ordini creati, $ordiniAggiornati aggiornati, $fasiCreate fasi create" .
                                   ($logFasi ? ' (' . implode(', ', $logFasi) . ')' : ''),
        ];
    }

    /**
     * Sincronizza DDT emesse a fornitore da Onda: avvia automaticamente
     * le fasi esterne nel MES quando trova una DDT con commessa.
     */
    public static function sincronizzaDDTFornitore(): int
    {
        $avviate = 0;

        // Query DDT a fornitore degli ultimi 30 giorni
        $righeDDT = DB::connection('onda')->select("
            SELECT t.IdDoc, t.DataDocumento, t.IdAnagrafica, a.RagioneSociale,
                   r.Descrizione, r.Qta, r.CodUnMis
            FROM ATTDocTeste t
            JOIN ATTDocRighe r ON t.IdDoc = r.IdDoc
            LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
            WHERE t.TipoDocumento = 7
              AND t.DataRegistrazione >= DATEADD(day, -30, GETDATE())
        ");

        if (empty($righeDDT)) {
            return 0;
        }

        $repartoEsterno = Reparto::where('nome', 'esterno')->first();
        if (!$repartoEsterno) {
            Log::warning('DDT Fornitore sync: reparto "esterno" non trovato');
            return 0;
        }

        foreach ($righeDDT as $riga) {
            $descrizione = $riga->Descrizione ?? '';

            // Estrai numero commessa dalla descrizione
            if (!preg_match('/Commessa n°\s*(\d+)/i', $descrizione, $m)) {
                continue;
            }

            $numGrezzo = $m[1];
            $fornitore = trim($riga->RagioneSociale ?? '');
            $idDoc = $riga->IdDoc;
            $dataDoc = $riga->DataDocumento ? date('Y-m-d H:i:s', strtotime($riga->DataDocumento)) : now();

            // Formato MES: 00XXXXX-YY (7 cifre zero-padded + trattino + anno 2 cifre)
            $anno = $riga->DataDocumento ? date('y', strtotime($riga->DataDocumento)) : date('y');
            $numCommessa = str_pad($numGrezzo, 7, '0', STR_PAD_LEFT) . '-' . $anno;

            // Cerca la prima fase esterna non ancora avviata per questa commessa
            $fase = OrdineFase::whereHas('ordine', function ($q) use ($numCommessa) {
                    $q->where('commessa', $numCommessa);
                })
                ->whereHas('faseCatalogo', function ($q) use ($repartoEsterno) {
                    $q->where('reparto_id', $repartoEsterno->id);
                })
                ->whereIn('stato', [0, 1])
                ->whereNull('ddt_fornitore_id')
                ->orderBy('id')
                ->first();

            if (!$fase) {
                continue;
            }

            $fase->update([
                'esterno'          => 1,
                'stato'            => 5,
                'data_inizio'      => $dataDoc,
                'note'             => 'Inviato a: ' . $fornitore,
                'ddt_fornitore_id' => $idDoc,
            ]);

            $avviate++;

            Log::info("DDT Fornitore: avviata fase esterna #{$fase->id} per commessa {$numCommessa} (DDT {$idDoc}, fornitore: {$fornitore})");
        }

        return $avviate;
    }

    /**
     * Interpreta le descrizioni DDT fornitore per mandare fasi all'esterno.
     * Legge parole chiave (plastificare, fustellare, stampa a caldo, ecc.)
     * e marca le fasi corrispondenti come esterne.
     */
    public static function sincronizzaDDTFornitureLavorazioni(): int
    {
        $mappingLavorazioni = [
            ['pattern' => '/uv\s*spot\s*spessorat/iu', 'fasi' => ['UVSPOTSPESSEST', 'UVSPOTEST', 'UVSPOT.MGI.30M', 'UVSPOT.MGI.9M']],
            ['pattern' => '/uv\s*spot/iu', 'fasi' => ['UVSPOTEST', 'UVSPOT.MGI.30M', 'UVSPOT.MGI.9M', 'UVSPOTSPESSEST']],
            ['pattern' => '/verniciare\s*uv/iu', 'fasi' => ['UVSPOTEST', 'UVSPOTSPESSEST', 'UVSPOT.MGI.30M']],
            ['pattern' => '/plastificare\s*opac/iu', 'fasi' => ['PLAOPA1LATO', 'PLAOPABV']],
            ['pattern' => '/plastificare\s*lucid/iu', 'fasi' => ['PLALUX1LATO', 'PLALUXBV']],
            ['pattern' => '/plastificar/iu', 'fasi' => ['PLAOPA1LATO', 'PLAOPABV', 'PLALUX1LATO', 'PLALUXBV', 'PLASOFTTOUCH1']],
            ['pattern' => '/stamp\w*\s*a\s*caldo/iu', 'fasi' => ['STAMPACALDOJOHEST', 'STAMPACALDOJOH', 'stampalaminaoro', 'STAMPALAMINAORO']],
            ['pattern' => '/oro\s*a\s*caldo/iu', 'fasi' => ['STAMPACALDOJOHEST', 'STAMPACALDOJOH', 'stampalaminaoro']],
            ['pattern' => '/foil\s*oro|foil\s+\w+/iu', 'fasi' => ['FOIL.MGI.30M', 'FOIL.MGI.9M', 'FOILMGI', 'FOIL.MGI']],
            ['pattern' => '/\bfoil\b/iu', 'fasi' => ['FOIL.MGI.30M', 'FOIL.MGI.9M', 'FOILMGI', 'FOIL.MGI']],
            ['pattern' => '/fustellatura|fustellare|da\s*fustellare/iu', 'fasi' => ['FUSTBOBST75X106', 'FUSTBIML75X106', 'FUSTSTELG33.44', 'FUSTSTELP25.35', 'FUST.STARPACK.74X104']],
            ['pattern' => '/brossura\s*filo\s*refe/iu', 'fasi' => ['BROSSFILOREFE/A4EST', 'BROSSFILOREFE/A5EST', 'BROSSCOPEST']],
            ['pattern' => '/brossura\s*fresat/iu', 'fasi' => ['BROSSFRESATA/A5EST', 'BROSSFRESATA/A4EST']],
            ['pattern' => '/punt[io]\s*metallic/iu', 'fasi' => ['PUNTOMETALLICOEST', 'PUNTOMETALLICO']],
            ['pattern' => '/incollare|incollaggio|piega\s*incolla/iu', 'fasi' => ['PI01', 'PI02', 'PI03']],
            ['pattern' => '/accoppiar/iu', 'fasi' => ['accopp+fust', 'ACCOPPIATURA.FOGLI', 'ACCOPPIATURA.FOG.33.48INT']],
            ['pattern' => '/allestimento|allestire/iu', 'fasi' => ['Allest.Manuale', 'ALLEST.SHOPPER', 'ALLESTIMENTO.ESPOSITORI']],
        ];

        $righeDDT = DB::connection('onda')->select("
            SELECT t.IdDoc, t.DataDocumento, t.IdAnagrafica, a.RagioneSociale, r.Descrizione
            FROM ATTDocTeste t
            JOIN ATTDocRighe r ON t.IdDoc = r.IdDoc
            LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
            WHERE t.TipoDocumento = 7
              AND t.DataRegistrazione >= DATEADD(day, -60, GETDATE())
        ");

        if (empty($righeDDT)) return 0;

        $repartoEsterno = Reparto::firstOrCreate(['nome' => 'esterno']);
        $aggiornate = 0;

        foreach ($righeDDT as $riga) {
            $descrizione = $riga->Descrizione ?? '';
            if (!preg_match('/Commessa\s*n?[°º.]?\s*(\d{5,7})/iu', $descrizione, $m)) continue;

            $numGrezzo = $m[1];
            $fornitore = trim($riga->RagioneSociale ?? '');
            $idDoc = $riga->IdDoc;
            $dataDoc = $riga->DataDocumento ? date('Y-m-d H:i:s', strtotime($riga->DataDocumento)) : now();
            $anno = $riga->DataDocumento ? date('y', strtotime($riga->DataDocumento)) : date('y');
            $numCommessa = str_pad($numGrezzo, 7, '0', STR_PAD_LEFT) . '-' . $anno;

            $lavorazioniTrovate = [];
            foreach ($mappingLavorazioni as $map) {
                if (preg_match($map['pattern'], $descrizione)) {
                    $lavorazioniTrovate[] = $map;
                }
            }
            if (empty($lavorazioniTrovate)) continue;

            $fasiGiaMatchate = [];
            foreach ($lavorazioniTrovate as $lav) {
                $fase = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $numCommessa))
                    ->whereIn('fase', $lav['fasi'])
                    ->where(fn($q) => $q->where('esterno', false)->orWhereNull('esterno'))
                    ->whereNull('ddt_fornitore_id')
                    ->whereRaw("stato REGEXP '^[0-9]+$' AND stato < 3")
                    ->orderBy('id')
                    ->first();

                if (!$fase) {
                    foreach ($lav['fasi'] as $nomeFase) {
                        $fase = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $numCommessa))
                            ->where('fase', 'LIKE', $nomeFase . '%')
                            ->where(fn($q) => $q->where('esterno', false)->orWhereNull('esterno'))
                            ->whereNull('ddt_fornitore_id')
                            ->whereRaw("stato REGEXP '^[0-9]+$' AND stato < 3")
                            ->orderBy('id')
                            ->first();
                        if ($fase) break;
                    }
                }

                if (!$fase || in_array($fase->id, $fasiGiaMatchate)) continue;
                if ($fase->esterno && $fase->ddt_fornitore_id) continue;
                $fasiGiaMatchate[] = $fase->id;

                $fase->update([
                    'esterno' => 1,
                    'stato' => 5,
                    'data_inizio' => $dataDoc,
                    'note' => 'Inviato a: ' . $fornitore,
                    'ddt_fornitore_id' => $idDoc,
                ]);

                $aggiornate++;
                Log::info("DDT Fornitore lavorazione: fase {$fase->fase} (#{$fase->id}) → esterno per commessa {$numCommessa} (DDT {$idDoc}, fornitore: {$fornitore})");
            }
        }

        return $aggiornate;
    }

    /**
     * Sincronizza DDT vendita (TipoDocumento=3) da Onda:
     * segna sull'ordine MES che è stata emessa una DDT vendita,
     * così la dashboard spedizione può mostrare le consegne da confermare.
     */
    public static function sincronizzaDDTVendita(): int
    {
        $aggiornati = 0;

        // CodCommessa è nelle RIGHE della DDT, non nella testa
        $righeDDT = DB::connection('onda')->select("
            SELECT t.IdDoc, r.CodCommessa, t.DataDocumento, t.NumeroDocumento,
                   a.RagioneSociale AS Cliente,
                   v.RagioneSociale AS Vettore,
                   SUM(r.Qta) AS QtaDDT
            FROM ATTDocTeste t
            JOIN ATTDocRighe r ON t.IdDoc = r.IdDoc
            LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
            LEFT JOIN ATTDocCoda c ON t.IdDoc = c.IdDoc
            LEFT JOIN STDAnagrafiche v ON c.IdVettore1 = v.IdAnagrafica
            WHERE t.TipoDocumento = 3
              AND t.DataRegistrazione >= DATEADD(day, -7, GETDATE())
              AND r.CodCommessa IS NOT NULL AND r.CodCommessa != ''
              AND r.TipoRiga = 1
            GROUP BY t.IdDoc, r.CodCommessa, t.DataDocumento, t.NumeroDocumento, a.RagioneSociale, v.RagioneSociale
        ");

        if (empty($righeDDT)) {
            return 0;
        }

        $pdfGenerati = []; // traccia DDT per cui abbiamo già generato il PDF

        foreach ($righeDDT as $riga) {
            $codCommessa = trim($riga->CodCommessa ?? '');
            if (!$codCommessa) continue;

            $idDoc = $riga->IdDoc;
            $qtaDDT = (float) ($riga->QtaDDT ?? 0);
            $numeroDDT = trim($riga->NumeroDocumento ?? '');
            $vettore = trim($riga->Vettore ?? '');
            $cliente = trim($riga->Cliente ?? '');

            // Cerca ordine con match diretto
            $ordine = Ordine::where('commessa', $codCommessa)->first();

            // Salva nella tabella ddt_spedizioni (supporta più DDT per commessa)
            $ddtNuovo = false;
            if ($ordine) {
                $esistente = DdtSpedizione::where('onda_id_doc', $idDoc)->where('commessa', $codCommessa)->exists();
                DdtSpedizione::updateOrCreate(
                    ['onda_id_doc' => $idDoc, 'commessa' => $codCommessa],
                    [
                        'numero_ddt'   => $numeroDDT,
                        'data_ddt'     => $riga->DataDocumento ? substr($riga->DataDocumento, 0, 10) : null,
                        'vettore'      => $vettore,
                        'cliente_nome' => $cliente,
                        'ordine_id'    => $ordine->id,
                        'qta'          => $qtaDDT,
                    ]
                );
                if (!$esistente) $ddtNuovo = true;
            }

            // Aggiorna anche il campo legacy sull'ordine (primo DDT trovato)
            if ($ordine && !$ordine->ddt_vendita_id) {
                $ordine->update([
                    'ddt_vendita_id'      => $idDoc,
                    'numero_ddt_vendita'  => $numeroDDT,
                    'vettore_ddt'         => $vettore,
                    'qta_ddt_vendita'     => $qtaDDT,
                ]);
            }

            $aggiornati++;
            Log::info("DDT Vendita: commessa {$codCommessa} DDT {$numeroDDT} (IdDoc {$idDoc}, qta: {$qtaDDT})");

            // Genera PDF automaticamente solo per DDT nuovi (non già in DB)
            if ($ddtNuovo && $numeroDDT && !in_array($numeroDDT, $pdfGenerati)) {
                try {
                    DdtPdfService::generaESalva($numeroDDT);
                    $pdfGenerati[] = $numeroDDT;
                } catch (\Exception $e) {
                    Log::warning("DDT PDF: errore generazione DDT {$numeroDDT}: " . $e->getMessage());
                }
            }
        }

        return $aggiornati;
    }

    public static function getMappaReparti(): array
    {
        return [
            'accopp+fust' => 'esterno',
            'ACCOPPIATURA.FOG.33.48INT' => 'esterno',
            'ACCOPPIATURA.FOGLI' => 'esterno',
            'Allest.Manuale' => 'legatoria',
            'Allest.Manuale0,2' => 'legatoria',
            'ALLEST.SHOPPER' => 'legatoria',
            'ALLEST.SHOPPER024' => 'legatoria',
            'ALLEST.SHOPPER030' => 'legatoria',
            'ALLESTIMENTO.CALENDARI' => 'legatoria',
            'ALLESTIMENTO.ESPOSITORI' => 'esterno',
            'CARTONATO.GEN' => 'legatoria',
            'APPL.BIADESIVO30' => 'esterno',
            'appl.laccetto' => 'esterno',
            'ARROT2ANGOLI' => 'esterno',
            'ARROT4ANGOLI' => 'esterno',
            'AVVIAMENTISTAMPA.EST1.1' => 'esterno',
            'blocchi.manuale' => 'esterno',
            'BROSSCOPBANDELLAEST' => 'esterno',
            'BROSSCOPEST' => 'esterno',
            'BROSSFILOREFE/A4EST' => 'esterno',
            'BROSSFILOREFE/A5EST' => 'esterno',
            'BRT1' => 'spedizione',
            'brt1' => 'spedizione',
            'CARTONATO.GEN' => 'esterno',
            'CORDONATURAPETRATTO' => 'legatoria',
            'DEKIA-Difficile' => 'legatoria',
            'FIN01' => 'finestratura',
            'FIN03' => 'finestratura',
            'FIN04' => 'finestratura',
            'FOIL.MGI.30M' => 'finitura digitale',
            'FOILMGI' => 'finitura digitale',
            'FUST.STARPACK.74X104' => 'esterno',
            'FUSTBIML75X106' => 'fustella piana',
            'FUSTbIML75X106' => 'fustella piana',
            'FUSTBOBST75X106' => 'fustella piana',
            'FUSTBOBSTRILIEVI' => 'fustella piana',
            'FUSTSTELG33.44' => 'fustella cilindrica',
            'FUSTSTELP25.35' => 'fustella cilindrica',
            'FUSTIML75X106' => 'fustella piana',
            'FUSTELLATURA72X51' => 'fustella',
            'FINESTRATURA.INT' => 'finestratura',
            'INCOLLAGGIO.PATTINA' => 'legatoria',
            'INCOLLAGGIOBLOCCHI' => 'legatoria',
            'LAVGEN' => 'legatoria',
            'NUM.PROGR.' => 'legatoria',
            'NUM33.44' => 'legatoria',
            'PERF.BUC' => 'legatoria',
            'PI01' => 'piegaincolla',
            'PI02' => 'piegaincolla',
            'PI03' => 'piegaincolla',
            'PIEGA2ANTECORDONE' => 'legatoria',
            'PIEGA2ANTESINGOLO' => 'legatoria',
            'PIEGA3ANTESINGOLO' => 'legatoria',
            'PIEGA8ANTESINGOLO' => 'legatoria',
            'PIEGA8TTAVO' => 'legatoria',
            'PIEGAMANUALE' => 'legatoria',
            'PLALUX1LATO' => 'plastificazione',
            'PLALUXBV' => 'plastificazione',
            'PLAOPA1LATO' => 'plastificazione',
            'PLAOPABV' => 'plastificazione',
            'PLAPOLIESARG1LATO' => 'plastificazione',
            'PLASAB1LATO' => 'plastificazione',
            'PLASABBIA1LATO' => 'plastificazione',
            'PLASOFTBV' => 'plastificazione',
            'PLASOFTBVEST' => 'plastificazione',
            'PLASOFTTOUCH1' => 'plastificazione',
            'PUNTOMETALLICO' => 'legatoria',
            'PUNTOMETALLICOEST' => 'legatoria',
            'PUNTOMETALLICOESTCOPERT.' => 'legatoria',
            'PUNTOMETAMANUALE' => 'legatoria',
            'RILIEVOASECCOJOH' => 'fustella piana',
            'SFUST' => 'legatoria',
            'SFUST.IML.FUSTELLATO' => 'legatoria',
            'SPIRBLOCCOLIBROA3' => 'legatoria',
            'SPIRBLOCCOLIBROA4' => 'legatoria',
            'SPIRBLOCCOLIBROA5' => 'legatoria',
            'STAMPA' => 'stampa offset',
            'STAMPA.OFFSET11.EST' => 'esterno',
            'STAMPABUSTE.EST' => 'esterno',
            'STAMPACALDOJOH' => 'stampa a caldo',
            'STAMPACALDOJOH0,1' => 'stampa a caldo',
            'STAMPAINDIGO' => 'digitale',
            'STAMPAINDIGOBN' => 'digitale',
            'STAMPAXL106' => 'stampa offset',
            'STAMPAXL106.1' => 'stampa offset',
            'STAMPAXL106.2' => 'stampa offset',
            'STAMPAXL106.3' => 'stampa offset',
            'STAMPAXL106.4' => 'stampa offset',
            'STAMPAXL106.5' => 'stampa offset',
            'STAMPAXL106.6' => 'stampa offset',
            'STAMPAXL106.7' => 'stampa offset',
            'TAGLIACARTE' => 'tagliacarte',
            'TAGLIACARTE.IML' => 'tagliacarte',
            'TAGLIOINDIGO' => 'tagliacarte',
            'UVSERIGRAFICOEST' => 'esterno',
            'UVSPOT.MGI.30M' => 'finitura digitale',
            'UVSPOT.MGI.30M.10' => 'finitura digitale',
            'UVSPOT.MGI.9M' => 'finitura digitale',
            'UVSPOTEST' => 'esterno',
            'UVSPOTSPESSEST' => 'esterno',
            'ZUND' => 'finitura digitale',
            'APPL.CORDONCINO0,035' => 'legatoria',
            '4graph' => 'esterno',
            'stampalaminaoro' => 'stampa a caldo',
            'STAMPALAMINAORO' => 'stampa a caldo',
            'ALL.COFANETTO.ISMAsrl' => 'esterno',
            'PMDUPLO36COP' => 'legatoria',
            'FINESTRATURA.MANUALE' => 'finestratura',
            'STAMPACALDOJOHEST' => 'esterno',
            'BROSSFRESATA/A5EST' => 'esterno',
            'PIEGA6ANTESINGOLO' => 'legatoria',
            'PIEGA4ANTESINGOLO' => 'legatoria',
            'PMDUPLO40AUTO' => 'legatoria',
            'BROSSPUR' => 'legatoria',

            // Fasi con "est" nel nome o prefisso "EXT" → esterno
            'est STAMPACALDOJOH' => 'esterno',
            'est FUSTSTELG33.44' => 'esterno',
            'est FUSTBOBST75X106' => 'esterno',
            'STAMPA.ESTERNA' => 'esterno',
            'EXTALL.COFANETTO.LEGOKART' => 'esterno',
            'EXTAllest.Manuale' => 'esterno',
            'EXTALLEST.SHOPPER' => 'esterno',
            'EXTALLESTIMENTO.ESPOSITOR' => 'esterno',
            'EXTAPPL.CORDONCINO0,035' => 'esterno',
            'EXTAVVIAMENTISTAMPA.EST1.' => 'esterno',
            'EXTBROSSCOPEST' => 'esterno',
            'EXTBROSSFILOREFE/A4EST' => 'esterno',
            'EXTBROSSFILOREFE/A5EST' => 'esterno',
            'EXTBROSSFRESATA/A4EST' => 'esterno',
            'EXTBROSSFRESATA/A5EST' => 'esterno',
            'EXTCARTONATO' => 'esterno',
            'EXTCARTONATO.GEN' => 'esterno',
            'EXTFUSTELLATURA72X51' => 'esterno',
            'EXTPUNTOMETALLICOEST' => 'esterno',
            'EXTSTAMPA.OFFSET11.EST' => 'esterno',
            'EXTSTAMPABUSTE.EST' => 'esterno',
            'EXTSTAMPASECCO' => 'esterno',
            'EXTUVSPOTEST' => 'esterno',
            'EXTUVSPOTSPESSEST' => 'esterno',

            // Altre fasi nuove
            'DEKIA-semplice' => 'legatoria',
            'STAMPASECCO' => 'fustella',
            'STAMPACALDO04' => 'stampa a caldo',
            'STAMPACALDOBR' => 'stampa a caldo',
            'STAMPAINDIGOBIANCO' => 'digitale',
        ];
    }

    public static function getTipoReparto(): array
    {
        return [
            // multifase
            'accopp+fust' => 'multifase',
            'FIN01' => 'multifase',
            'FIN03' => 'multifase',
            'FIN04' => 'multifase',
            'PI01' => 'multifase',
            'PI02' => 'multifase',
            'PI03' => 'multifase',
            'SFUST' => 'multifase',
            'SFUST.IML.FUSTELLATO' => 'multifase',
            // monofase
            'ACCOPPIATURA.FOG.33.48INT' => 'monofase',
            'ACCOPPIATURA.FOGLI' => 'monofase',
            'Allest.Manuale' => 'monofase',
            'ALLEST.SHOPPER' => 'monofase',
            'ALLEST.SHOPPER030' => 'monofase',
            'APPL.BIADESIVO30' => 'monofase',
            'appl.laccetto' => 'monofase',
            'ARROT2ANGOLI' => 'monofase',
            'ARROT4ANGOLI' => 'monofase',
            'AVVIAMENTISTAMPA.EST1.1' => 'monofase',
            'blocchi.manuale' => 'monofase',
            'BROSSCOPBANDELLAEST' => 'monofase',
            'BROSSCOPEST' => 'monofase',
            'BROSSFILOREFE/A4EST' => 'monofase',
            'BROSSFILOREFE/A5EST' => 'monofase',
            'BRT1' => 'monofase',
            'brt1' => 'monofase',
            'CARTONATO.GEN' => 'monofase',
            'CORDONATURAPETRATTO' => 'monofase',
            'DEKIA-Difficile' => 'monofase',
            'FOIL.MGI.30M' => 'monofase',
            'FOILMGI' => 'monofase',
            'FUST.STARPACK.74X104' => 'monofase',
            'FUSTBIML75X106' => 'monofase',
            'FUSTbIML75X106' => 'monofase',
            'FUSTBOBST75X106' => 'monofase',
            'FUSTBOBSTRILIEVI' => 'monofase',
            'FUSTSTELG33.44' => 'monofase',
            'FUSTSTELP25.35' => 'monofase',
            'INCOLLAGGIO.PATTINA' => 'monofase',
            'INCOLLAGGIOBLOCCHI' => 'monofase',
            'LAVGEN' => 'monofase',
            'NUM.PROGR.' => 'monofase',
            'NUM33.44' => 'monofase',
            'PERF.BUC' => 'monofase',
            'PIEGA2ANTECORDONE' => 'monofase',
            'PIEGA2ANTESINGOLO' => 'monofase',
            'PIEGA3ANTESINGOLO' => 'monofase',
            'PIEGA8ANTESINGOLO' => 'monofase',
            'PIEGA8TTAVO' => 'monofase',
            'PIEGAMANUALE' => 'monofase',
            'PLALUX1LATO' => 'monofase',
            'PLALUXBV' => 'monofase',
            'PLAOPA1LATO' => 'monofase',
            'PLAOPABV' => 'monofase',
            'PLAPOLIESARG1LATO' => 'monofase',
            'PLASAB1LATO' => 'monofase',
            'PLASABBIA1LATO' => 'monofase',
            'PLASOFTBV' => 'monofase',
            'PLASOFTBVEST' => 'monofase',
            'PLASOFTTOUCH1' => 'monofase',
            'PUNTOMETALLICO' => 'monofase',
            'PUNTOMETALLICOEST' => 'monofase',
            'PUNTOMETALLICOESTCOPERT.' => 'monofase',
            'PUNTOMETAMANUALE' => 'monofase',
            'RILIEVOASECCOJOH' => 'monofase',
            'SPIRBLOCCOLIBROA3' => 'monofase',
            'SPIRBLOCCOLIBROA4' => 'monofase',
            'SPIRBLOCCOLIBROA5' => 'monofase',
            'STAMPA' => 'monofase',
            'STAMPA.OFFSET11.EST' => 'monofase',
            'STAMPABUSTE.EST' => 'monofase',
            'STAMPACALDOJOH' => 'monofase',
            'STAMPACALDOJOH0,1' => 'monofase',
            'UVSERIGRAFICOEST' => 'monofase',
            'UVSPOT.MGI.30M' => 'monofase',
            'UVSPOT.MGI.9M' => 'monofase',
            'UVSPOTEST' => 'monofase',
            'UVSPOTSPESSEST' => 'monofase',
            'ZUND' => 'monofase',
            'APPL.CORDONCINO0,035' => 'monofase',
            '4graph' => 'monofase',
            'stampalaminaoro' => 'monofase',
            'STAMPALAMINAORO' => 'monofase',
            'ALL.COFANETTO.ISMAsrl' => 'monofase',
            'PMDUPLO36COP' => 'monofase',
            'FINESTRATURA.MANUALE' => 'monofase',
            'STAMPACALDOJOHEST' => 'monofase',
            'BROSSFRESATA/A5EST' => 'monofase',
            'PIEGA6ANTESINGOLO' => 'monofase',
            // max 2 fasi
            'STAMPAINDIGO' => 'monofase',
            'STAMPAINDIGOBN' => 'monofase',
            'STAMPAXL106' => 'monofase',
            'STAMPAXL106.1' => 'monofase',
            'STAMPAXL106.2' => 'monofase',
            'STAMPAXL106.3' => 'monofase',
            'STAMPAXL106.4' => 'monofase',
            'STAMPAXL106.5' => 'monofase',
            'STAMPAXL106.6' => 'monofase',
            'STAMPAXL106.7' => 'monofase',
            'STAMPAINDIGOBIANCO' => 'monofase',

            // Nuove fasi EXT e altre → monofase
            'est STAMPACALDOJOH' => 'monofase',
            'est FUSTSTELG33.44' => 'monofase',
            'est FUSTBOBST75X106' => 'monofase',
            'STAMPA.ESTERNA' => 'monofase',
            'EXTALL.COFANETTO.LEGOKART' => 'monofase',
            'EXTAllest.Manuale' => 'monofase',
            'EXTALLEST.SHOPPER' => 'monofase',
            'EXTALLESTIMENTO.ESPOSITOR' => 'monofase',
            'EXTAPPL.CORDONCINO0,035' => 'monofase',
            'EXTAVVIAMENTISTAMPA.EST1.' => 'monofase',
            'EXTBROSSCOPEST' => 'monofase',
            'EXTBROSSFILOREFE/A4EST' => 'monofase',
            'EXTBROSSFILOREFE/A5EST' => 'monofase',
            'EXTBROSSFRESATA/A4EST' => 'monofase',
            'EXTBROSSFRESATA/A5EST' => 'monofase',
            'EXTCARTONATO' => 'monofase',
            'EXTCARTONATO.GEN' => 'monofase',
            'EXTFUSTELLATURA72X51' => 'monofase',
            'EXTPUNTOMETALLICOEST' => 'monofase',
            'EXTSTAMPA.OFFSET11.EST' => 'monofase',
            'EXTSTAMPABUSTE.EST' => 'monofase',
            'EXTSTAMPASECCO' => 'monofase',
            'EXTUVSPOTEST' => 'monofase',
            'EXTUVSPOTSPESSEST' => 'monofase',
            'DEKIA-semplice' => 'monofase',
            'STAMPASECCO' => 'monofase',
            'STAMPACALDO04' => 'monofase',
            'STAMPACALDOBR' => 'monofase',
        ];
    }
}
