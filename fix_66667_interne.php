<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;

$commessa = '0066667-26';

$fasi = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
    ->where('esterno', 1)
    ->get();

echo "=== RIPRISTINO 66667: tutte interne ===" . PHP_EOL;

foreach ($fasi as $f) {
    $note = $f->note;
    // Rimuovi "Inviato a: ..." dalla nota
    $note = preg_replace('/,?\s*Inviato a:\s*.+/i', '', $note ?? '');
    $note = trim($note, ", \t\n\r") ?: null;

    $f->esterno = 0;
    $f->ddt_fornitore_id = null;
    $f->note = $note;
    $f->stato = 0;
    $f->data_inizio = null;
    $f->save();
    echo "FIX: {$f->fase} (ID:{$f->id}) → esterno:NO, stato:0, nota pulita" . PHP_EOL;
}

\App\Services\FaseStatoService::ricalcolaCommessa($commessa);
echo PHP_EOL . "Ricalcolato. {$fasi->count()} fasi ripristinate." . PHP_EOL;
