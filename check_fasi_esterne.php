<?php
// Mostra fasi MES esterne di una commessa, distingue confermate da DDT vs non confermate.
// Uso: php check_fasi_esterne.php 0067269-26
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$cm = $argv[1] ?? '0067269-26';

echo "=== Fasi MES per commessa $cm ===\n\n";

$ordini = \App\Models\Ordine::where('commessa', $cm)->with(['fasi.faseCatalogo.reparto'])->get();
if ($ordini->isEmpty()) {
    echo "Nessun ordine MES per $cm.\n";
    exit(0);
}

foreach ($ordini as $o) {
    echo "ORDINE #{$o->id} — {$o->cod_art} — " . substr($o->descrizione ?? '', 0, 60) . "\n";
    foreach ($o->fasi as $f) {
        $rep = $f->faseCatalogo->reparto->nome ?? '?';
        $ext = $f->esterno ? 'EXT' : '   ';
        $stato = $f->stato;
        $ddt = $f->ddt_fornitore_id ? "DDT {$f->ddt_fornitore_id}" : 'no DDT';
        $forn = $f->fornitore_esterno ?? ($f->note && str_starts_with($f->note ?? '', 'Inviato') ? trim(str_replace('Inviato a:', '', $f->note)) : '-');
        echo sprintf("  %-25s %-15s stato=%-3s %s | %-15s | %s\n",
            $f->fase, $rep, $stato, $ext, $ddt, $forn);
    }
    echo "\n";
}

// DDT collegati
$ddtIds = \App\Models\OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $cm))
    ->whereNotNull('ddt_fornitore_id')
    ->pluck('ddt_fornitore_id')
    ->unique();

if ($ddtIds->isNotEmpty()) {
    echo "=== DDT fornitore collegati ===\n";
    foreach ($ddtIds as $idDoc) {
        $r = DB::connection('onda')->select("
            SELECT TOP 1 t.NumeroDocumento, t.DataDocumento, an.RagioneSociale
            FROM ATTDocTeste t
            LEFT JOIN STDAnagrafiche an ON t.IdAnagrafica = an.IdAnagrafica
            WHERE t.IdDoc = ?
        ", [$idDoc]);
        if (!empty($r)) {
            echo "  IdDoc={$idDoc} | DDT n.{$r[0]->NumeroDocumento} | " . substr($r[0]->DataDocumento, 0, 10) . " | " . ($r[0]->RagioneSociale ?? '-') . "\n";
        }
    }
}
