<?php
// Reset + re-sync + verifica DDT fornitori per una commessa.
// Uso: php fix_ddt_67269.php [commessa]
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$cm = $argv[1] ?? '0067269-26';

// === STEP 1: Reset fasi esterne ===
echo "=== Reset fasi esterne $cm ===\n";
$reset = \App\Models\OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $cm))
    ->where('esterno', 1)
    ->update([
        'esterno' => 0,
        'ddt_fornitore_id' => null,
        'stato' => 1,
        'note' => null,
        'data_inizio' => null,
    ]);
echo "  $reset fasi resettate\n\n";

// === STEP 2: Re-sync DDT fornitori (logica nuova con keyword articolo) ===
echo "=== Re-sync DDT fornitori ===\n";
$updated = \App\Services\OndaSyncService::sincronizzaDDTFornitureLavorazioni();
echo "  $updated fasi marcate esterne (totale)\n\n";

// === STEP 3: Verifica risultato finale ===
echo "=== Stato finale fasi $cm ===\n";
$ordini = \App\Models\Ordine::where('commessa', $cm)->with(['fasi.faseCatalogo.reparto'])->get();
foreach ($ordini as $o) {
    echo "\nORDINE #{$o->id} — {$o->cod_art} — " . substr($o->descrizione ?? '', 0, 50) . "\n";
    foreach ($o->fasi as $f) {
        $rep = $f->faseCatalogo->reparto->nome ?? '?';
        $ext = $f->esterno ? '[EXT]' : '     ';
        $ddt = $f->ddt_fornitore_id ? "DDT={$f->ddt_fornitore_id}" : 'no DDT';
        $forn = '';
        if ($f->note && str_contains($f->note, 'Inviato a:')) {
            $forn = trim(str_replace('Inviato a:', '', $f->note));
        }
        echo sprintf("  %-25s %-15s stato=%-3s %s | %-15s | %s\n",
            $f->fase, $rep, $f->stato, $ext, $ddt, $forn);
    }
}
