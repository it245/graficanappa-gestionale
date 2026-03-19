<?php
/**
 * Pulisce i duplicati creati dal fix_dup_esterne + sync Onda.
 * Le fasi rinominate dal fix (da EXT* a originale) sono ora duplicate
 * delle nuove EXT* ricreate da Onda. Elimina le vecchie rinominate.
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use App\Models\Reparto;
use App\Models\FasiCatalogo;

$repartoEsterno = Reparto::where('nome', 'esterno')->first();

// Trova fasi EXT* appena create da Onda
$fasiExt = OrdineFase::with(['ordine', 'faseCatalogo'])
    ->whereHas('faseCatalogo', fn($q) => $q->where('reparto_id', $repartoEsterno->id)
        ->where('nome', 'LIKE', 'EXT%'))
    ->get();

$eliminate = 0;

foreach ($fasiExt as $faseExt) {
    $nomeOriginale = preg_replace('/^EXT/', '', $faseExt->faseCatalogo->nome);
    $ordineId = $faseExt->ordine_id;

    // Cerca la vecchia fase rinominata (stesso ordine, nome originale, qualsiasi reparto)
    $vecchia = OrdineFase::where('ordine_id', $ordineId)
        ->where('fase', $nomeOriginale)
        ->where('id', '!=', $faseExt->id)
        ->first();

    if ($vecchia) {
        $commessa = $faseExt->ordine->commessa ?? '-';
        echo "  [ELIMINO VECCHIA] {$commessa} | {$vecchia->fase} (ID:{$vecchia->id}) → duplicato di {$faseExt->faseCatalogo->nome} (ID:{$faseExt->id})\n";

        // Trasferisci dati utili dalla vecchia alla nuova EXT
        if ($vecchia->stato > $faseExt->stato) $faseExt->stato = $vecchia->stato;
        if ($vecchia->data_inizio && !$faseExt->data_inizio) $faseExt->data_inizio = $vecchia->data_inizio;
        if ($vecchia->data_fine && !$faseExt->data_fine) $faseExt->data_fine = $vecchia->data_fine;
        if ($vecchia->note && !$faseExt->note) $faseExt->note = $vecchia->note;
        if ($vecchia->ddt_fornitore_id && !$faseExt->ddt_fornitore_id) $faseExt->ddt_fornitore_id = $vecchia->ddt_fornitore_id;
        if ($vecchia->qta_prod > 0 && $faseExt->qta_prod == 0) $faseExt->qta_prod = $vecchia->qta_prod;
        $faseExt->esterno = 1;
        $faseExt->save();

        $vecchia->delete();
        $eliminate++;
    }
}

echo "\nDuplicate vecchie eliminate: $eliminate\n";
