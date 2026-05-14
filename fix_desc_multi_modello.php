<?php
/**
 * Fix retroactive multi-modello: per commessa con ordini con stessa descrizione,
 * mappa fasi PI01/FIN01 a descrizioni distinte da Onda ATTDocRighe.
 *
 * Uso: php fix_desc_multi_modello.php 67203
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Ordine;
use App\Models\OrdineFase;

$num = $argv[1] ?? null;
if (!$num) { echo "Uso: php fix_desc_multi_modello.php <numero_commessa>\n"; exit(1); }

$comm = str_pad(ltrim($num, '0'), 7, '0', STR_PAD_LEFT) . '-26';
echo "Commessa: $comm\n";

// 1. Descrizioni distinte da Onda
$desc = DB::connection('onda')->select(
    "SELECT r.IdDoc, r.NrRiga, r.Descrizione, r.CodArt
     FROM ATTDocRighe r
     INNER JOIN ATTDocTeste t ON t.IdDoc = r.IdDoc
     WHERE CAST(t.CodCommessa AS VARCHAR) = ?
       AND r.TipoRiga = 1
     ORDER BY r.NrRiga",
    [$comm]
);
$descrizioni = array_column((array)$desc, 'Descrizione');
$codArt = $desc[0]->CodArt ?? null;
echo "Descrizioni Onda: " . count($descrizioni) . " (CodArt={$codArt})\n";
foreach ($descrizioni as $i => $d) echo "  [$i] " . substr($d, 0, 80) . "\n";

if (count($descrizioni) < 2) {
    echo "Non multi-modello (1 sola desc), niente da fare.\n";
    exit;
}

// 2. Per ogni gruppo fase (PI01, FIN01), ordina fasi per id, assegna desc
$ordini = Ordine::where('commessa', $comm)->orderBy('id')->get();
$ordineTemplate = $ordini->first();
echo "\nOrdini MES: " . $ordini->count() . "\n";

foreach (['PI01', 'FIN01'] as $faseCod) {
    echo "\n=== $faseCod ===\n";
    $fasi = OrdineFase::whereIn('ordine_id', $ordini->pluck('id'))
        ->where('fase', $faseCod)
        ->orderBy('id')
        ->get();
    echo "Fasi $faseCod: " . $fasi->count() . "\n";

    foreach ($fasi as $i => $f) {
        $targetDesc = $descrizioni[$i] ?? null;
        if (!$targetDesc) { echo "  [$i] no desc\n"; continue; }

        $ordineCorrente = Ordine::find($f->ordine_id);
        if ($ordineCorrente->descrizione === $targetDesc) {
            echo "  [$i] fase id={$f->id} OK\n";
            continue;
        }

        $altriFasi = OrdineFase::where('ordine_id', $ordineCorrente->id)
            ->where('id', '!=', $f->id)
            ->whereIn('fase', ['PI01', 'FIN01'])
            ->count();

        if ($altriFasi === 0) {
            echo "  [$i] fase id={$f->id} → update desc ordine {$ordineCorrente->id}\n";
            $ordineCorrente->descrizione = $targetDesc;
            $ordineCorrente->save();
        } else {
            echo "  [$i] fase id={$f->id} → clona ordine + sposto\n";
            $nuovo = $ordineTemplate->replicate();
            $nuovo->descrizione = $targetDesc;
            $nuovo->save();
            $f->ordine_id = $nuovo->id;
            $f->save();
        }
    }
}

// 3. Ripristina ordine template (8961) a prima desc (lettera A/E ecc)
$primaDesc = OrdineFase::join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->where('ordini.commessa', $comm)
    ->where('ordine_fasi.fase', 'PI01')
    ->orderBy('ordine_fasi.id')
    ->value('ordini.descrizione');
if ($primaDesc && $ordineTemplate) {
    $ordineTemplate->refresh();
    if ($ordineTemplate->descrizione !== $primaDesc) {
        echo "\nReset ordine template {$ordineTemplate->id} a prima desc\n";
        $ordineTemplate->descrizione = $primaDesc;
        $ordineTemplate->save();
    }
}

echo "\nFatto.\n";
