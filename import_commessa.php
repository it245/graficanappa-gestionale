<?php
/**
 * Script per importare una singola commessa da Onda al gestionale MES.
 * Uso: php import_commessa.php 0066035
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\FasiCatalogo;
use App\Models\Reparto;
use Illuminate\Support\Facades\DB;

$commessaArg = $argv[1] ?? null;
if (!$commessaArg) {
    echo "Uso: php import_commessa.php <numero_commessa>\n";
    echo "Esempio: php import_commessa.php 0066035\n";
    exit(1);
}

echo "Cerco commessa '{$commessaArg}' in Onda...\n";

// Query Onda senza filtro data, cerca per commessa (con LIKE per gestire suffisso anno)
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
      AND t.CodCommessa LIKE ?
", [$commessaArg . '%']);

if (empty($righeOnda)) {
    echo "Commessa non trovata in Onda. Provo ricerca esatta...\n";
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
          AND t.CodCommessa = ?
    ", [$commessaArg]);
}

if (empty($righeOnda)) {
    echo "Commessa '{$commessaArg}' non trovata in Onda.\n";
    exit(1);
}

echo "Trovate " . count($righeOnda) . " righe in Onda.\n";

// Usa la stessa logica di OndaSyncService
$mappaReparti = \App\Services\OndaSyncService::getMappaReparti();
$tipiFase = \App\Services\OndaSyncService::getTipoReparto();
$mappaPriorita = config('fasi_priorita');
$oggi = now()->format('Y-m-d');

// Pre-fetch scarti previsti per macchina da Onda
$scartiMacchine = collect(DB::connection('onda')->select(
    "SELECT CodMacchina, OC_FogliScartoIniz FROM PRDMacchinari WHERE OC_FogliScartoIniz > 0"
))->pluck('OC_FogliScartoIniz', 'CodMacchina')->toArray();

// Raggruppa per (CodCommessa, CodArt, OC_Descrizione)
$gruppi = collect($righeOnda)->groupBy(function ($riga) {
    return $riga->CodCommessa . '|' . $riga->CodArt . '|' . $riga->OC_Descrizione;
});

$ordiniCreati = 0;
$fasiCreate = 0;
$dedupPerCommessa = []; // 1 sola fase per commessa per fustella/digitale
$dedupQta = []; // Track qta distinte per dedup

foreach ($gruppi as $chiave => $righe) {
    $prima = $righe->first();
    $commessa = trim($prima->CodCommessa ?? '');
    $codArt = trim($prima->CodArt ?? '');
    $descrizione = trim($prima->OC_Descrizione ?? '');

    if (!$commessa) continue;

    echo "\n--- Commessa: {$commessa} | Art: {$codArt}\n";
    echo "    Cliente: " . trim($prima->ClienteNome ?? '') . "\n";
    echo "    Descrizione: {$descrizione}\n";

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
        echo "    -> Ordine AGGIORNATO (id: {$ordine->id})\n";
    } else {
        $ordine = Ordine::create(array_merge([
            'commessa'    => $commessa,
            'cod_art'     => $codArt,
            'descrizione' => $descrizione,
            'stato'       => 0,
            'data_registrazione' => $prima->DataRegistrazione ? date('Y-m-d', strtotime($prima->DataRegistrazione)) : $oggi,
        ], $datiOrdine));
        $ordiniCreati++;
        echo "    -> Ordine CREATO (id: {$ordine->id})\n";
    }

    // Fase BRT1 (spedizione) — 1 sola per commessa
    $hasBrt = OrdineFase::withTrashed()
        ->where(function ($q) { $q->where('fase', 'BRT1')->orWhere('fase', 'brt1'); })
        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
        ->exists();

    if (!$hasBrt) {
        $repartoBrt = Reparto::firstOrCreate(['nome' => 'spedizione']);
        $faseCatalogoBrt = FasiCatalogo::firstOrCreate(['nome' => 'BRT1'], ['reparto_id' => $repartoBrt->id]);
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
        echo "    -> Fase BRT1 creata\n";
    }

    // Fasi produzione
    $fasiViste = [];
    foreach ($righe as $riga) {
        $faseNome = trim($riga->CodFase ?? '');
        if (!$faseNome) continue;

        // Rimappa STAMPA generico
        if ($faseNome === 'STAMPA') {
            $macchina = trim($riga->CodMacchina ?? '');
            if (stripos($macchina, 'INDIGO') !== false) {
                $faseNome = (stripos($macchina, 'BN') !== false || stripos($macchina, 'MONO') !== false) ? 'STAMPAINDIGOBN' : 'STAMPAINDIGO';
            } elseif (preg_match('/XL106[.-]?(\d+)/i', $macchina, $m)) {
                $faseNome = 'STAMPAXL106.' . $m[1];
            } else {
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
        $faseCatalogo = FasiCatalogo::firstOrCreate(['nome' => $faseNome], ['reparto_id' => $reparto->id]);

        // Dedup fustella per commessa: 1 sola fase per commessa per reparto (la fustella fisica è condivisa)
        // qta_fase = somma delle qta distinte di tutti gli articoli
        if (in_array($repartoNome, ['fustella piana', 'fustella cilindrica', 'fustella'])) {
            $chiaveDedup = $commessa . '|fust|' . $repartoNome;
            $qtaRiga = (int)($riga->QtaDaLavorare ?? 0);

            if (isset($dedupPerCommessa[$chiaveDedup])) {
                if ($qtaRiga > 0 && !in_array($qtaRiga, $dedupQta[$chiaveDedup] ?? [])) {
                    $dedupQta[$chiaveDedup][] = $qtaRiga;
                    $nuovaQta = array_sum($dedupQta[$chiaveDedup]);
                    OrdineFase::withTrashed()
                        ->whereHas('faseCatalogo', fn($q) => $q->where('reparto_id', $reparto->id))
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                        ->update(['qta_fase' => $nuovaQta]);
                    echo "    -> Fase {$faseNome} fustella qta aggiornata a {$nuovaQta}\n";
                } else {
                    echo "    -> Fase {$faseNome} fustella già creata per commessa, skip\n";
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
                echo "    -> Fase {$faseNome} fustella già esistente per commessa, skip\n";
                continue;
            }
        }

        // Dedup digitale/finitura digitale: 1 sola se stesso articolo (cod_art)
        if (in_array($repartoNome, ['digitale', 'finitura digitale'])) {
            $chiaveDedup = $commessa . '|' . $faseNome . '|' . $codArt;
            if (isset($dedupPerCommessa[$chiaveDedup])) {
                echo "    -> Fase {$faseNome} digitale già creata per stesso articolo, skip\n";
                continue;
            }
            $existsInCommessa = OrdineFase::withTrashed()
                ->where('fase_catalogo_id', $faseCatalogo->id)
                ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa)
                    ->where('cod_art', $codArt))
                ->exists();
            $dedupPerCommessa[$chiaveDedup] = true;
            if ($existsInCommessa) {
                echo "    -> Fase {$faseNome} digitale già esistente per stesso articolo, skip\n";
                continue;
            }
        }

        // Dedup stampa offset per commessa: 1 sola STAMPAXL106 per commessa
        // qta_fase = somma delle qta distinte
        if ($repartoNome === 'stampa offset' && str_starts_with($faseNome, 'STAMPAXL106')) {
            $chiaveDedup = $commessa . '|stampa_offset|' . $faseNome;
            $qtaRiga = (int)($riga->QtaDaLavorare ?? 0);

            if (isset($dedupPerCommessa[$chiaveDedup])) {
                if ($qtaRiga > 0 && !in_array($qtaRiga, $dedupQta[$chiaveDedup] ?? [])) {
                    $dedupQta[$chiaveDedup][] = $qtaRiga;
                    $nuovaQta = array_sum($dedupQta[$chiaveDedup]);
                    OrdineFase::withTrashed()
                        ->where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                        ->update(['qta_fase' => $nuovaQta]);
                    echo "    -> Fase {$faseNome} stampa offset qta aggiornata a {$nuovaQta}\n";
                } else {
                    echo "    -> Fase {$faseNome} stampa offset già creata per commessa, skip\n";
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
                echo "    -> Fase {$faseNome} stampa offset già esistente per commessa, skip\n";
                continue;
            }
        }

        // Dedup stampa a caldo per commessa: 1 sola per commessa per fase_catalogo
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
                    echo "    -> Fase {$faseNome} stampa a caldo qta aggiornata a {$nuovaQta}\n";
                } else {
                    echo "    -> Fase {$faseNome} stampa a caldo già creata per commessa, skip\n";
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
                echo "    -> Fase {$faseNome} stampa a caldo già esistente per commessa, skip\n";
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
                    OrdineFase::withTrashed()
                        ->where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                        ->update(['qta_fase' => $nuovaQta]);
                    echo "    -> Fase {$faseNome} BRT1 qta aggiornata a {$nuovaQta}\n";
                } else {
                    echo "    -> Fase {$faseNome} BRT1 già creata per commessa, skip\n";
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
                echo "    -> Fase {$faseNome} BRT1 già esistente per commessa, skip\n";
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

        $exists = OrdineFase::withTrashed()
            ->where('ordine_id', $ordine->id)
            ->where('fase_catalogo_id', $faseCatalogo->id)
            ->exists();

        if (!$exists) {
            OrdineFase::create($dataFase);
            $fasiCreate++;
            echo "    -> Fase {$faseNome} creata (reparto: {$repartoNome})\n";
        } else {
            echo "    -> Fase {$faseNome} già esistente, skip\n";
        }
    }
}

echo "\n=== RISULTATO ===\n";
echo "Ordini creati: {$ordiniCreati}\n";
echo "Fasi create: {$fasiCreate}\n";
echo "Fine.\n";
