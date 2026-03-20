<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use Illuminate\Support\Facades\DB;

$commessa = '0066667-26';

echo "=== FIX DEDUP 66667 ===" . PHP_EOL;

// Onda: quante accopp+fust ha?
$ondaCount = DB::connection('onda')->select("
    SELECT f.CodFase, COUNT(*) as cnt
    FROM PRDDocFasi f
    JOIN PRDDocTeste p ON f.IdDoc = p.IdDoc
    WHERE p.CodCommessa = ?
    AND f.CodFase = 'accopp+fust'
    GROUP BY f.CodFase
", [$commessa]);

$ondaN = $ondaCount[0]->cnt ?? 0;
echo "Onda ha {$ondaN} fasi accopp+fust" . PHP_EOL;

// MES: quante ne ha?
$fasiMes = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
    ->where('fase', 'accopp+fust')
    ->orderBy('id')
    ->get();

echo "MES ha {$fasiMes->count()} fasi accopp+fust" . PHP_EOL . PHP_EOL;

if ($fasiMes->count() <= $ondaN) {
    echo "Nessun duplicato da eliminare." . PHP_EOL;
    exit;
}

// Tieni le prime $ondaN, elimina il resto
$daTenere = $fasiMes->take($ondaN);
$daEliminare = $fasiMes->skip($ondaN);

echo "Tengo {$daTenere->count()}, elimino {$daEliminare->count()}" . PHP_EOL . PHP_EOL;

foreach ($daEliminare as $f) {
    echo "ELIMINA: ID:{$f->id} | qta:{$f->qta_fase} | stato:{$f->stato} | note:" . substr($f->note ?? '-', 0, 40) . PHP_EOL;
    $f->delete();
}

\App\Services\FaseStatoService::ricalcolaCommessa($commessa);
echo PHP_EOL . "Ricalcolato. DONE." . PHP_EOL;
