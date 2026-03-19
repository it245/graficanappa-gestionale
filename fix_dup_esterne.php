<?php
/**
 * Trova e rimuove tutte le fasi duplicate create dallo script DDT esterne
 * che ha cambiato fase_catalogo_id a EXT*. Per ogni fase con catalogo EXT
 * nello stesso ordine dove esiste anche l'originale, elimina la EXT.
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use App\Models\Reparto;
use App\Models\FasiCatalogo;

$repartoEsterno = Reparto::where('nome', 'esterno')->first();
if (!$repartoEsterno) {
    echo "Reparto 'esterno' non trovato.\n";
    exit;
}

// Trova tutte le fasi con catalogo EXT* nel reparto esterno
$fasiExt = OrdineFase::with(['ordine', 'faseCatalogo'])
    ->whereHas('faseCatalogo', fn($q) => $q->where('reparto_id', $repartoEsterno->id)
        ->where('nome', 'LIKE', 'EXT%'))
    ->get();

echo "Fasi con catalogo EXT* trovate: " . $fasiExt->count() . "\n\n";

$eliminate = 0;
$riparate = 0;

foreach ($fasiExt as $faseExt) {
    $commessa = $faseExt->ordine->commessa ?? '-';
    $nomeExt = $faseExt->faseCatalogo->nome ?? '';
    // Ricava il nome originale togliendo EXT dal prefisso
    $nomeOriginale = preg_replace('/^EXT/', '', $nomeExt);

    if (!$nomeOriginale) continue;

    // Cerca se esiste la fase originale (stesso ordine, stesso nome fase, reparto NON esterno)
    $faseOriginale = OrdineFase::where('ordine_id', $faseExt->ordine_id)
        ->where('fase', $nomeOriginale)
        ->whereHas('faseCatalogo', fn($q) => $q->where('reparto_id', '!=', $repartoEsterno->id))
        ->first();

    if ($faseOriginale) {
        // Esiste l'originale → la EXT è un duplicato, eliminala
        // Ma prima trasferisci i dati utili (fornitore, ddt_fornitore_id) all'originale
        if ($faseExt->ddt_fornitore_id && !$faseOriginale->ddt_fornitore_id) {
            $faseOriginale->esterno = 1;
            $faseOriginale->ddt_fornitore_id = $faseExt->ddt_fornitore_id;
            if ($faseExt->note && !$faseOriginale->note) {
                $faseOriginale->note = $faseExt->note;
            }
            if ($faseExt->data_inizio && !$faseOriginale->data_inizio) {
                $faseOriginale->data_inizio = $faseExt->data_inizio;
                $faseOriginale->stato = 2;
            }
            $faseOriginale->save();
            $riparate++;
        }

        echo "  [ELIMINO] {$commessa} | {$nomeExt} (ID:{$faseExt->id}) → originale {$nomeOriginale} (ID:{$faseOriginale->id}) esiste\n";
        $faseExt->delete();
        $eliminate++;
    } else {
        // Non esiste l'originale → la EXT è l'unica fase, ripristina il catalogo originale
        $catOriginale = FasiCatalogo::where('nome', $nomeOriginale)->first();
        if ($catOriginale) {
            echo "  [RIPRISTINO] {$commessa} | {$nomeExt} (ID:{$faseExt->id}) → catalogo {$nomeOriginale} (cat:{$catOriginale->id})\n";
            $faseExt->fase = $nomeOriginale;
            $faseExt->fase_catalogo_id = $catOriginale->id;
            $faseExt->save();
            $riparate++;
        } else {
            echo "  [SKIP] {$commessa} | {$nomeExt} (ID:{$faseExt->id}) → catalogo originale '{$nomeOriginale}' non trovato\n";
        }
    }
}

echo "\n=== RIEPILOGO ===\n";
echo "Duplicate eliminate: $eliminate\n";
echo "Fasi riparate/trasferite: $riparate\n";
