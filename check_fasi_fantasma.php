<?php
/**
 * DRY-RUN: lista fasi fantasma (in PRDDocFasi ma NON in ATTDocRighe).
 * Mostra quali commesse verrebbero modificate dal sync intelligente.
 *
 * Usage:
 *   php check_fasi_fantasma.php            # tutte commesse MES attive
 *   php check_fasi_fantasma.php 0067339-26 # singola commessa
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$onlyCommessa = $argv[1] ?? null;
$onda = DB::connection('onda');
$mes = DB::connection();

echo "\n=== DRY-RUN: fasi fantasma (PRD only, NON in ATT) ===\n\n";

if ($onlyCommessa) {
    $commesse = [$onlyCommessa];
} else {
    $commesse = $mes->table('ordini')
        ->join('ordine_fasi', 'ordini.id', '=', 'ordine_fasi.ordine_id')
        ->whereIn('ordine_fasi.stato', ['0', '1'])
        ->whereNull('ordine_fasi.deleted_at')
        ->distinct()
        ->pluck('ordini.commessa')
        ->filter()
        ->values()
        ->all();
}

echo "Commesse da analizzare: " . count($commesse) . "\n\n";

$totaleFantasme = 0;
$commesseAffette = [];

foreach ($commesse as $commessa) {
    try {
        $prd = $onda->select("
            SELECT f.CodFase, f.QtaDaLavorare
            FROM PRDDocTeste p
            INNER JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
            WHERE p.CodCommessa = ?
        ", [$commessa]);
    } catch (\Exception $e) {
        echo "  ERR PRD {$commessa}: " . $e->getMessage() . "\n";
        continue;
    }

    try {
        $att = $onda->select("
            SELECT CodArt, Descrizione, TipoRiga
            FROM ATTDocRighe
            WHERE CodCommessa = ?
        ", [$commessa]);
    } catch (\Exception $e) {
        echo "  ERR ATT {$commessa}: " . $e->getMessage() . "\n";
        continue;
    }

    if (empty($prd)) continue;

    $attFasi = [];
    foreach ($att as $r) {
        if ($r->TipoRiga == 2 && !empty($r->CodArt)) {
            $attFasi[] = strtoupper($r->CodArt);
        }
    }
    $attDescConcat = strtoupper(implode(' ', array_map(fn ($r) => $r->Descrizione ?? '', $att)));

    $fantasme = [];
    foreach ($prd as $f) {
        $codFase = $f->CodFase ?? '';
        if ($codFase === '') continue;
        $norm = preg_replace('/^EXT/', '', strtoupper($codFase));
        $found = false;

        foreach ($attFasi as $a) {
            if ($a === '') continue;
            if (stripos($a, $norm) !== false || stripos($norm, $a) !== false) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $key = substr($norm, 0, 4);
            if ($key !== '' && stripos($attDescConcat, $key) !== false) {
                $found = true;
            }
        }

        if (!$found) {
            $fantasme[] = sprintf("%s (Qta=%s)", $codFase, $f->QtaDaLavorare);
        }
    }

    if (!empty($fantasme)) {
        $totaleFantasme += count($fantasme);
        $commesseAffette[] = $commessa;
        echo sprintf("Commessa %s — fasi fantasma:\n", $commessa);
        foreach ($fantasme as $f) echo "  - {$f}\n";

        $mesFasi = $mes->table('ordini')
            ->join('ordine_fasi', 'ordini.id', '=', 'ordine_fasi.ordine_id')
            ->where('ordini.commessa', $commessa)
            ->whereNull('ordine_fasi.deleted_at')
            ->pluck('ordine_fasi.fase')
            ->map(fn ($f) => strtoupper($f))
            ->all();
        $attiveMes = [];
        foreach ($fantasme as $fLabel) {
            $name = explode(' ', $fLabel)[0];
            if (in_array(strtoupper($name), $mesFasi, true)) {
                $attiveMes[] = $name;
            }
        }
        if (!empty($attiveMes)) {
            echo "  ⚠ ATTIVE NEL MES (da soft-delete): " . implode(', ', $attiveMes) . "\n";
        }
        echo "\n";
    }
}

echo "=== RIEPILOGO ===\n";
echo "Commesse analizzate: " . count($commesse) . "\n";
echo "Commesse con fasi fantasma: " . count($commesseAffette) . "\n";
echo "Fasi fantasma totali: {$totaleFantasme}\n\n";
