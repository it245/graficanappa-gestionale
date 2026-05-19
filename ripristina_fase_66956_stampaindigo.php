<?php
/**
 * Ripristina fase STAMPAINDIGO copertina (id=15707) commessa 0066956-26.
 * Era soft-deleted il 2026-05-12 (eliminata per errore).
 * Esegui con --confirm.
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$id = 15707;
$confirm = in_array('--confirm', $argv);

$fase = DB::table('ordine_fasi')->where('id', $id)->first();
if (!$fase) { echo "Fase id={$id} non trovata\n"; exit(1); }

echo "Fase trovata:\n";
echo "  id={$fase->id} | {$fase->fase} | stato={$fase->stato} | deleted_at=" . ($fase->deleted_at ?? 'NULL') . "\n";

if (!$fase->deleted_at) {
    echo "Già attiva (deleted_at NULL). Nulla da fare.\n";
    exit(0);
}

if (!$confirm) {
    echo "\n⚠️  PREVIEW. Esegui con --confirm per ripristinare.\n";
    exit(0);
}

$rows = DB::table('ordine_fasi')->where('id', $id)->update(['deleted_at' => null]);
echo "\n✅ Ripristinata. Righe aggiornate: {$rows}\n";
