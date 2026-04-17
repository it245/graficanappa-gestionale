<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = $argv[1] ?? '0066535-26';

echo "=== ONDA (PRDDocFasi) commessa {$commessa} ===\n";
$fasiOnda = DB::connection('onda')->select("
    SELECT p.CodCommessa, p.IdDoc, f.CodFase, f.CodMacchina, f.QtaDaLavorare, f.CodUnMis, f.TipoRiga
    FROM PRDDocTeste p
    LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
    WHERE p.CodCommessa = ?
    ORDER BY p.IdDoc, f.CodFase
", [$commessa]);

if (empty($fasiOnda)) {
    echo "Nessuna fase Onda.\n";
} else {
    foreach ($fasiOnda as $r) {
        echo "  IdDoc={$r->IdDoc} CodFase={$r->CodFase} Mac={$r->CodMacchina} Qta={$r->QtaDaLavorare} UM={$r->CodUnMis} TipoRiga={$r->TipoRiga}\n";
    }
}

echo "\n=== MES (ordine_fasi) commessa {$commessa} ===\n";
$ordini = App\Models\Ordine::where('commessa', $commessa)->get();
foreach ($ordini as $o) {
    echo "\n-- Ordine id={$o->id}\n";
    $fasi = App\Models\OrdineFase::where('ordine_id', $o->id)->withTrashed()->get();
    foreach ($fasi as $f) {
        $trashed = $f->trashed() ? ' [SOFT-DELETED]' : '';
        echo "  id={$f->id} fase={$f->fase} stato={$f->stato} qta_prod={$f->qta_prod} prior={$f->priorita}{$trashed}\n";
    }
}
