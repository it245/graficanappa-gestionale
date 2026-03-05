<?php
/**
 * Confronta fasi Onda vs MES per più commesse.
 * Uso: php check_commesse_multi.php 0066600-26 0066609-26 0066611-26
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commesse = array_slice($argv, 1);
if (empty($commesse)) {
    echo "Uso: php check_commesse_multi.php <commessa1> <commessa2> ...\n";
    exit(1);
}

foreach ($commesse as $commessa) {
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "COMMESSA: {$commessa}\n";
    echo str_repeat('=', 80) . "\n";

    // Fasi nel MES
    $ordini = DB::table('ordini')->where('commessa', $commessa)->get();
    echo "\nMES - Ordini: " . $ordini->count() . "\n";
    foreach ($ordini as $o) {
        echo "  Ordine #{$o->id} | Art: {$o->cod_art} | " . substr($o->descrizione ?? '', 0, 50) . "\n";
        $fasi = DB::table('ordine_fasi')
            ->leftJoin('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
            ->leftJoin('reparti', 'reparti.id', '=', 'fasi_catalogo.reparto_id')
            ->where('ordine_fasi.ordine_id', $o->id)
            ->select('ordine_fasi.*', 'reparti.nome as reparto_nome')
            ->get();
        foreach ($fasi as $f) {
            $del = $f->deleted_at ? ' [DELETED]' : '';
            $desc = substr($f->fase ?? '-', 0, 25);
            echo "    Fase #{$f->id} | {$desc} | rep: " . ($f->reparto_nome ?? 'N/A')
                . " | stato:{$f->stato} | pri:{$f->priorita} | cat_id:{$f->fase_catalogo_id}{$del}\n";
        }
    }

    // Fasi in Onda
    $base = preg_replace('/-\d+$/', '', $commessa);
    $righeOnda = DB::connection('onda')->select("
        SELECT DISTINCT f.CodFase, f.CodMacchina, f.QtaDaLavorare
        FROM PRDDocTeste p
        JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
        WHERE p.CodCommessa = ?
        ORDER BY f.CodFase
    ", [$commessa]);

    echo "\nONDA - Fasi: " . count($righeOnda) . "\n";
    foreach ($righeOnda as $r) {
        // Check se esiste nel MES
        $existsMES = DB::table('ordine_fasi')
            ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
            ->where('ordini.commessa', $commessa)
            ->where('ordine_fasi.fase', $r->CodFase)
            ->whereNull('ordine_fasi.deleted_at')
            ->exists();
        $status = $existsMES ? 'OK' : 'MANCA';
        echo "  {$r->CodFase} | macchina: {$r->CodMacchina} | qta: {$r->QtaDaLavorare} → [{$status}]\n";
    }
}
