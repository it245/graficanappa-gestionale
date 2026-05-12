<?php
/**
 * Diagnosi commessa multi-articolo: confronta DB MES vs Onda.
 *
 * Usage:
 *   php check_commessa_multi.php 0067364-26
 *   php check_commessa_multi.php 0067074-26
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = $argv[1] ?? null;
if (!$commessa) {
    echo "Usage: php check_commessa_multi.php <commessa>\n";
    exit(1);
}

echo "\n=== MES: ordini per commessa {$commessa} ===\n";
$ords = DB::table('ordini')->where('commessa', $commessa)->get(['id','cod_art','descrizione','qta_richiesta']);
echo "Ordini in MES: " . count($ords) . "\n";
foreach ($ords as $o) {
    $d = substr($o->descrizione ?? '', 0, 80);
    echo "  ord_id={$o->id} cod_art={$o->cod_art} qta={$o->qta_richiesta} desc={$d}\n";
}

echo "\n=== MES: fasi FIN01 + PI01 + STAMPAXL106* per commessa ===\n";
$ids = array_map(fn($o) => $o->id, (array) $ords->all());
$fasi = DB::table('ordine_fasi')
    ->whereIn('ordine_id', $ids)
    ->whereNull('deleted_at')
    ->where(function ($q) {
        $q->whereIn('fase', ['FIN01','PI01'])
          ->orWhere('fase', 'LIKE', 'STAMPAXL106%');
    })
    ->orderBy('fase')->orderBy('ordine_id')
    ->get(['id','ordine_id','fase','priorita','scarti']);
echo "Fasi: " . count($fasi) . "\n";
$perOrdine = [];
foreach ($fasi as $f) {
    $perOrdine[$f->ordine_id][$f->fase] = ($perOrdine[$f->ordine_id][$f->fase] ?? 0) + 1;
    echo "  fase_id={$f->id} ord_id={$f->ordine_id} fase={$f->fase}\n";
}
echo "\nRiepilogo fasi per ordine MES:\n";
foreach ($perOrdine as $oid => $faseCount) {
    echo "  ord_id={$oid}: ";
    foreach ($faseCount as $fname => $n) echo "{$fname}={$n} ";
    echo "\n";
}

echo "\n=== ONDA: righe ATTDocRighe per commessa ===\n";
try {
    $righeOnda = DB::connection('onda')->select("
        SELECT t.IdDoc, r.NrRiga, r.TipoRiga, r.CodArt, r.Descrizione, r.QtaDaLavorare
        FROM ATTDocTeste t
        INNER JOIN ATTDocRighe r ON t.IdDoc = r.IdDoc
        WHERE t.TipoDocumento = '2'
          AND r.CodCommessa = ?
        ORDER BY r.NrRiga
    ", [$commessa]);
    echo "Righe Onda: " . count($righeOnda) . "\n";
    foreach ($righeOnda as $r) {
        $tipoLabel = $r->TipoRiga == 1 ? 'ART' : ($r->TipoRiga == 2 ? 'FASE' : 'TIPO'.$r->TipoRiga);
        $d = substr($r->Descrizione ?? '', 0, 60);
        echo "  NrRiga={$r->NrRiga} {$tipoLabel} CodArt={$r->CodArt} Qta={$r->QtaDaLavorare} desc={$d}\n";
    }
} catch (\Exception $e) {
    echo "Errore Onda: " . $e->getMessage() . "\n";
}

echo "\n=== ONDA: PRDDocFasi per commessa (raggruppate per IdDoc) ===\n";
try {
    $prdFasi = DB::connection('onda')->select("
        SELECT p.IdDoc, p.CodArt, p.OC_Descrizione, p.QtaDaProdurre, f.CodFase, f.QtaDaLavorare
        FROM PRDDocTeste p
        LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
        WHERE p.CodCommessa = ?
        ORDER BY p.IdDoc, f.CodFase
    ", [$commessa]);

    $perIdDoc = [];
    foreach ($prdFasi as $r) {
        $perIdDoc[$r->IdDoc]['CodArt'] = $r->CodArt;
        $perIdDoc[$r->IdDoc]['desc'] = $r->OC_Descrizione;
        $perIdDoc[$r->IdDoc]['qta'] = $r->QtaDaProdurre;
        $perIdDoc[$r->IdDoc]['fasi'][] = [$r->CodFase, $r->QtaDaLavorare];
    }
    echo "Documenti PRD distinti: " . count($perIdDoc) . "\n";
    foreach ($perIdDoc as $idDoc => $info) {
        $d = substr($info['desc'] ?? '', 0, 60);
        echo "  IdDoc={$idDoc} CodArt={$info['CodArt']} qta={$info['qta']} desc={$d}\n";
        foreach ($info['fasi'] as $f) {
            echo "    fase={$f[0]} qta={$f[1]}\n";
        }
    }
} catch (\Exception $e) {
    echo "Errore Onda PRD: " . $e->getMessage() . "\n";
}

echo "\n";
