<?php
/**
 * Correggi pivot fase 23763 (FUSTBOBST75X106 commessa 67387):
 * data_fine sbagliata 18/05 05:52 → corretta 15/05 19:07 (~3h dopo inizio 16:07).
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$confirm = in_array('--confirm', $argv);

$pivot = DB::table('fase_operatore')->where('fase_id', 23763)->first();
if (!$pivot) { echo "Pivot non trovato\n"; exit(1); }

echo "Prima:\n";
echo "  fase_id={$pivot->fase_id} | op_id={$pivot->operatore_id} | inizio={$pivot->data_inizio} | fine={$pivot->data_fine}\n";

$nuovaFine = '2026-05-15 19:07:19';
if (!$confirm) {
    echo "\n⚠️ PREVIEW. Esegui con --confirm per aggiornare data_fine a {$nuovaFine}\n";
    exit(0);
}

DB::table('fase_operatore')->where('fase_id', 23763)->update(['data_fine' => $nuovaFine]);
DB::table('ordine_fasi')->where('id', 23763)->update(['data_fine' => $nuovaFine]);
echo "\n✅ Aggiornato. Nuova data_fine: {$nuovaFine} (durata ~3h)\n";
