<?php

namespace App\Services;

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\FasiCatalogo;
use App\Models\Reparto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FaseStatoService;

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

        // 1. Query tutti gli ordini aperti (TipoDocumento=2, non chiusi)
        $righeOnda = DB::connection('onda')->select("
            SELECT
                t.CodCommessa,
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
                f.CodFase,
                f.CodMacchina,
                f.QtaDaLavorare,
                f.CodUnMis AS UMFase
            FROM ATTDocTeste t
            INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
            LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
            LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
            OUTER APPLY (
                SELECT TOP 1 r.CodArt, r.Descrizione, r.Qta, r.CodUnMis
                FROM PRDDocRighe r WHERE r.IdDoc = p.IdDoc
                ORDER BY r.Sequenza
            ) carta
            OUTER APPLY (
                SELECT SUM(r2.Totale) AS CostoMateriali
                FROM PRDDocRighe r2 WHERE r2.IdDoc = p.IdDoc
            ) materiali
            WHERE t.TipoDocumento = '2'
              AND t.DataRegistrazione >= CAST('20260227' AS datetime)
        ");

        if (empty($righeOnda)) {
            return ['ordini_creati' => 0, 'ordini_aggiornati' => 0, 'fasi_create' => 0];
        }

        // 1b. Pulizia duplicati: se ci sono più di 2 fasi uguali (stesso ordine + fase_catalogo),
        //     mantieni le prime 2 (per "max 2 fasi") e elimina il resto
        $duplicati = DB::table('ordine_fasi')
            ->select('ordine_id', 'fase_catalogo_id', DB::raw('COUNT(*) as cnt'))
            ->whereNull('deleted_at')
            ->groupBy('ordine_id', 'fase_catalogo_id')
            ->having('cnt', '>', 2)
            ->get();

        $fasiEliminate = 0;
        foreach ($duplicati as $dup) {
            // Tieni le prime 2 (id più bassi)
            $keepIds = OrdineFase::where('ordine_id', $dup->ordine_id)
                ->where('fase_catalogo_id', $dup->fase_catalogo_id)
                ->orderBy('id')
                ->limit(2)
                ->pluck('id');

            $deleted = OrdineFase::where('ordine_id', $dup->ordine_id)
                ->where('fase_catalogo_id', $dup->fase_catalogo_id)
                ->whereNotIn('id', $keepIds)
                ->where('stato', '<=', 1)
                ->delete();
            $fasiEliminate += $deleted;
        }

        // Pulizia duplicati fustella per commessa: 1 sola fase per commessa per reparto fustella
        $repartiFustella = Reparto::whereIn('nome', ['fustella piana', 'fustella cilindrica', 'fustella'])->pluck('id');
        if ($repartiFustella->isNotEmpty()) {
            $dupFustella = DB::table('ordine_fasi')
                ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
                ->join('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
                ->select('ordini.commessa', 'fasi_catalogo.reparto_id', DB::raw('COUNT(*) as cnt'))
                ->whereIn('fasi_catalogo.reparto_id', $repartiFustella)
                ->whereNull('ordine_fasi.deleted_at')
                ->groupBy('ordini.commessa', 'fasi_catalogo.reparto_id')
                ->having('cnt', '>', 1)
                ->get();

            foreach ($dupFustella as $dup) {
                $faseIds = OrdineFase::withTrashed()
                    ->whereHas('faseCatalogo', fn($q) => $q->where('reparto_id', $dup->reparto_id))
                    ->whereHas('ordine', fn($q) => $q->where('commessa', $dup->commessa))
                    ->whereNull('deleted_at')
                    ->orderBy('id')
                    ->pluck('id');

                $keepId = $faseIds->first();
                $deleteIds = $faseIds->slice(1)->filter();
                if ($deleteIds->isNotEmpty()) {
                    $deleted = OrdineFase::whereIn('id', $deleteIds)
                        ->where('stato', '<=', 1)
                        ->delete();
                    $fasiEliminate += $deleted;
                }
            }
        }

        // Pulizia duplicati stampa offset per commessa: 1 sola STAMPAXL106 per commessa
        $repartiStampaOffset = Reparto::where('nome', 'stampa offset')->pluck('id');
        if ($repartiStampaOffset->isNotEmpty()) {
            $dupStampa = DB::table('ordine_fasi')
                ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
                ->join('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
                ->select('ordini.commessa', 'ordine_fasi.fase_catalogo_id', DB::raw('COUNT(*) as cnt'))
                ->whereIn('fasi_catalogo.reparto_id', $repartiStampaOffset)
                ->where('fasi_catalogo.nome', 'like', 'STAMPAXL106%')
                ->whereNull('ordine_fasi.deleted_at')
                ->groupBy('ordini.commessa', 'ordine_fasi.fase_catalogo_id')
                ->having('cnt', '>', 1)
                ->get();

            foreach ($dupStampa as $dup) {
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
                        ->where('stato', '<=', 1)
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
                        ->where('stato', '<=', 1)
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
                        ->where('stato', '<=', 1)
                        ->delete();
                    $fasiEliminate += $deleted;
                }
            }
        }

        if ($fasiEliminate > 0) {
            Log::info("OndaSync: eliminati $fasiEliminate duplicati");
        }

        // 2. Raggruppa per (CodCommessa, CodArt, OC_Descrizione)
        $gruppi = collect($righeOnda)->groupBy(function ($riga) {
            return $riga->CodCommessa . '|' . $riga->CodArt . '|' . $riga->OC_Descrizione;
        });

        // Track fasi deduplicate per commessa (1 sola per commessa per fustella, digitale, finitura digitale)
        $dedupPerCommessa = [];
        // Track qta distinte per dedup (chiave => [qta1, qta2, ...])
        $dedupQta = [];

        foreach ($gruppi as $chiave => $righe) {
            $prima = $righe->first();
            $commessa = trim($prima->CodCommessa ?? '');
            $codArt = trim($prima->CodArt ?? '');
            $descrizione = trim($prima->OC_Descrizione ?? '');

            if (!$commessa) continue;

            // 3. Upsert ordine (dedup per commessa + cod_art + descrizione)
            $ordine = Ordine::where('commessa', $commessa)
                ->where('cod_art', $codArt)
                ->where('descrizione', $descrizione)
                ->first();

            $datiOrdine = [
                'cliente_nome'           => trim($prima->ClienteNome ?? ''),
                'data_prevista_consegna' => $prima->DataPresConsegna && date('Y', strtotime($prima->DataPresConsegna)) >= 2024 ? date('Y-m-d', strtotime($prima->DataPresConsegna)) : null,
                'qta_richiesta'          => $prima->QtaDaProdurre ?? 0,
                'cod_carta'              => trim($prima->CodCarta ?? ''),
                'carta'                  => trim($prima->DescrizioneCarta ?? ''),
                'qta_carta'              => $prima->QtaCarta ?? 0,
                'UM_carta'               => trim($prima->UMCarta ?? ''),
                'note_prestampa'         => trim($prima->NotePrestampa ?? ''),
                'responsabile'           => trim($prima->Responsabile ?? ''),
                'commento_produzione'    => trim($prima->CommentoProduzione ?? ''),
                'ordine_cliente'         => trim($prima->OrdineCliente ?? '') ?: null,
                'valore_ordine'          => ($prima->TotMerce ?? 0) > 0 ? (float) $prima->TotMerce : null,
                'costo_materiali'        => ($prima->CostoMateriali ?? 0) > 0 ? (float) $prima->CostoMateriali : null,
            ];

            if ($ordine) {
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
                    } elseif (stripos($macchina, 'XL106') !== false) {
                        $faseNome = 'STAMPAXL106';
                    }
                }

                $chiaveFase = $faseNome . '|' . ($riga->QtaDaLavorare ?? 0);
                if (isset($fasiViste[$chiaveFase])) continue;
                $fasiViste[$chiaveFase] = true;

                $repartoNome = $mappaReparti[$faseNome] ?? 'generico';
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

                // Dedup fustella per commessa: 1 sola per commessa per reparto (la fustella fisica è condivisa)
                // qta_fase = somma delle qta distinte di tutti gli articoli
                if (in_array($repartoNome, ['fustella piana', 'fustella cilindrica', 'fustella'])) {
                    $chiaveDedup = $commessa . '|fust|' . $repartoNome;
                    $qtaRiga = (int)($riga->QtaDaLavorare ?? 0);

                    if (isset($dedupPerCommessa[$chiaveDedup])) {
                        // Riga successiva: somma qta se distinta
                        if ($qtaRiga > 0 && !in_array($qtaRiga, $dedupQta[$chiaveDedup] ?? [])) {
                            $dedupQta[$chiaveDedup][] = $qtaRiga;
                            $nuovaQta = array_sum($dedupQta[$chiaveDedup]);
                            OrdineFase::withTrashed()
                                ->whereHas('faseCatalogo', fn($q) => $q->where('reparto_id', $reparto->id))
                                ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                                ->update(['qta_fase' => $nuovaQta]);
                        }
                        continue;
                    }

                    $existsInCommessa = OrdineFase::withTrashed()
                        ->whereHas('faseCatalogo', fn($q) => $q->where('reparto_id', $reparto->id))
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                        ->exists();

                    $dedupPerCommessa[$chiaveDedup] = true;
                    $dedupQta[$chiaveDedup] = $qtaRiga > 0 ? [$qtaRiga] : [];
                    if ($existsInCommessa) {
                        // Aggiorna scarti_previsti se mancante + qta_fase
                        $scartiValue = $scartiMacchine[trim($riga->CodMacchina ?? '')] ?? null;
                        if ($scartiValue !== null) {
                            OrdineFase::withTrashed()
                                ->whereHas('faseCatalogo', fn($q) => $q->where('reparto_id', $reparto->id))
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

                    $existsInCommessa = OrdineFase::withTrashed()
                        ->where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa)
                            ->where('cod_art', $codArt))
                        ->exists();

                    $dedupPerCommessa[$chiaveDedup] = true;
                    if ($existsInCommessa) {
                        // Aggiorna scarti_previsti se mancante su fase esistente
                        $scartiValue = $scartiMacchine[trim($riga->CodMacchina ?? '')] ?? null;
                        if ($scartiValue !== null) {
                            OrdineFase::withTrashed()
                                ->where('fase_catalogo_id', $faseCatalogo->id)
                                ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa)
                                    ->where('cod_art', $codArt))
                                ->whereNull('scarti_previsti')
                                ->update(['scarti_previsti' => $scartiValue]);
                        }
                        continue;
                    }
                }

                // Dedup stampa offset per commessa: 1 sola STAMPAXL106 per commessa
                // (la stampa offset è per foglio, condivisa tra tutti gli articoli della commessa)
                // qta_fase = somma delle qta distinte
                if ($repartoNome === 'stampa offset' && str_starts_with($faseNome, 'STAMPAXL106')) {
                    $chiaveDedup = $commessa . '|stampa_offset|' . $faseNome;
                    $qtaRiga = (int)($riga->QtaDaLavorare ?? 0);

                    if (isset($dedupPerCommessa[$chiaveDedup])) {
                        // Riga successiva: somma qta se distinta
                        if ($qtaRiga > 0 && !in_array($qtaRiga, $dedupQta[$chiaveDedup] ?? [])) {
                            $dedupQta[$chiaveDedup][] = $qtaRiga;
                            $nuovaQta = array_sum($dedupQta[$chiaveDedup]);
                            OrdineFase::withTrashed()
                                ->where('fase_catalogo_id', $faseCatalogo->id)
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
                        $scartiValue = $scartiMacchine[trim($riga->CodMacchina ?? '')] ?? null;
                        if ($scartiValue !== null) {
                            OrdineFase::withTrashed()
                                ->where('fase_catalogo_id', $faseCatalogo->id)
                                ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                                ->whereNull('scarti_previsti')
                                ->update(['scarti_previsti' => $scartiValue]);
                        }
                        continue;
                    }
                }

                // Dedup stampa a caldo per commessa: 1 sola per commessa per fase_catalogo
                // qta_fase = somma delle qta distinte
                if ($repartoNome === 'stampa a caldo') {
                    $chiaveDedup = $commessa . '|stampa_caldo|' . $faseNome;
                    $qtaRiga = (int)($riga->QtaDaLavorare ?? 0);

                    if (isset($dedupPerCommessa[$chiaveDedup])) {
                        if ($qtaRiga > 0 && !in_array($qtaRiga, $dedupQta[$chiaveDedup] ?? [])) {
                            $dedupQta[$chiaveDedup][] = $qtaRiga;
                            $nuovaQta = array_sum($dedupQta[$chiaveDedup]);
                            OrdineFase::withTrashed()
                                ->where('fase_catalogo_id', $faseCatalogo->id)
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

                // Dedup BRT1 per commessa: 1 sola per commessa
                // qta_fase = somma delle qta distinte
                if ($repartoNome === 'spedizione') {
                    $chiaveDedup = $commessa . '|spedizione|' . $faseNome;
                    $qtaRiga = (int)($riga->QtaDaLavorare ?? 0);

                    if (isset($dedupPerCommessa[$chiaveDedup])) {
                        if ($qtaRiga > 0 && !in_array($qtaRiga, $dedupQta[$chiaveDedup] ?? [])) {
                            $dedupQta[$chiaveDedup][] = $qtaRiga;
                            $nuovaQta = array_sum($dedupQta[$chiaveDedup]);
                            OrdineFase::withTrashed()
                                ->where('fase_catalogo_id', $faseCatalogo->id)
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

                $dataFase = [
                    'ordine_id'        => $ordine->id,
                    'fase'             => $faseNome,
                    'fase_catalogo_id' => $faseCatalogo->id,
                    'qta_fase'         => $riga->QtaDaLavorare ?? 0,
                    'um'               => trim($riga->UMFase ?? 'FG'),
                    'priorita'         => $prioritaFase,
                    'stato'            => 0,
                    'scarti_previsti'   => $scartiMacchine[trim($riga->CodMacchina ?? '')] ?? null,
                ];

                // Se la fase è stata rimappata da STAMPA generico, aggiorna la fase esistente
                $faseOriginaleOnda = trim($riga->CodFase ?? '');
                if ($faseOriginaleOnda === 'STAMPA' && $faseNome !== 'STAMPA') {
                    $faseStampaGenerica = OrdineFase::withTrashed()
                        ->where('ordine_id', $ordine->id)
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
                    // Max 1: crea solo se non esiste (include soft-deleted per non ricreare fasi eliminate)
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
                    // Max 2: crea solo se ne esistono meno di 2 (include soft-deleted)
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
                    // Multifase: crea solo se non esiste già (include soft-deleted)
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
                    OrdineFase::withTrashed()
                        ->where('ordine_id', $ordine->id)
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
                'stato'            => 2,
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

        foreach ($righeDDT as $riga) {
            $codCommessa = trim($riga->CodCommessa ?? '');
            if (!$codCommessa) continue;

            $idDoc = $riga->IdDoc;
            $qtaDDT = (float) ($riga->QtaDDT ?? 0);

            // CodCommessa nelle righe è già nel formato completo (es. 0066398-26)
            // Cerca ordine con match diretto
            $ordine = Ordine::where('commessa', $codCommessa)->first();

            if (!$ordine) {
                continue;
            }

            // Idempotenza: skip se ordine ha già un ddt_vendita_id
            if ($ordine->ddt_vendita_id) {
                continue;
            }

            $ordine->update([
                'ddt_vendita_id'      => $idDoc,
                'numero_ddt_vendita'  => trim($riga->NumeroDocumento ?? ''),
                'vettore_ddt'         => trim($riga->Vettore ?? ''),
                'qta_ddt_vendita'     => $qtaDDT,
            ]);

            $aggiornati++;
            Log::info("DDT Vendita: aggiornato ordine #{$ordine->id} commessa {$ordine->commessa} con DDT {$idDoc} (qta DDT: {$qtaDDT})");
        }

        return $aggiornati;
    }

    public static function getMappaReparti(): array
    {
        return [
            'accopp+fust' => 'esterno',
            'ACCOPPIATURA.FOG.33.48INT' => 'esterno',
            'ACCOPPIATURA.FOGLI' => 'esterno',
            'Allest.Manuale' => 'esterno',
            'ALLEST.SHOPPER' => 'esterno',
            'ALLEST.SHOPPER030' => 'esterno',
            'ALLESTIMENTO.ESPOSITORI' => 'esterno',
            'APPL.BIADESIVO30' => 'esterno',
            'appl.laccetto' => 'esterno',
            'ARROT2ANGOLI' => 'esterno',
            'ARROT4ANGOLI' => 'esterno',
            'AVVIAMENTISTAMPA.EST1.1' => 'stampa offset',
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
            'FIN01' => 'legatoria',
            'FIN03' => 'legatoria',
            'FIN04' => 'legatoria',
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
            'FINESTRATURA.INT' => 'finestre',
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
            'UVSPOT.MGI.30M' => 'esterno',
            'UVSPOT.MGI.9M' => 'esterno',
            'UVSPOTEST' => 'esterno',
            'UVSPOTSPESSEST' => 'esterno',
            'ZUND' => 'finitura digitale',
            'APPL.CORDONCINO0,035' => 'legatoria',
            '4graph' => 'esterno',
            'stampalaminaoro' => 'stampa a caldo',
            'STAMPALAMINAORO' => 'stampa a caldo',
            'ALL.COFANETTO.ISMAsrl' => 'esterno',
            'PMDUPLO36COP' => 'esterno',
            'FINESTRATURA.MANUALE' => 'finestre',
            'STAMPACALDOJOHEST' => 'esterno',
            'BROSSFRESATA/A5EST' => 'esterno',
            'PIEGA6ANTESINGOLO' => 'legatoria',

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
            'TAGLIACARTE' => 'multifase',
            'TAGLIACARTE.IML' => 'multifase',
            'TAGLIOINDIGO' => 'multifase',
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
