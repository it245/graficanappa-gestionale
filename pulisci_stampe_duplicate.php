<?php
/**
 * Elimina fasi STAMPA XL duplicate create dalla fix IdDoc.
 * Tiene solo la prima per commessa (o max 2 per codArtMax2).
 * Eseguire su .60: php pulisci_stampe_duplicate.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\OrdineFase;
use App\Models\Reparto;

$codArtMax2 = [
    'Volumi','Vassoio','Vassoi','SPILLATI.OFFSET','SPILLATI.DIGITALE',
    'SOVRACOPERTA','RIVISTE.FRECCIA','riviste','RIVISTA.FRECCIA.128PP',
    'RICETTARI','Raccoglitori','Quaderni','Opuscoli','Libro.di.bordo',
    'Libricino','LibriBN','Libri','INSERTO.RIVISTA.NOTE.4pp',
    'I.Volumi','I.riviste','I.Raccoglitori','I.Quaderni','I.Poster',
    'I.Opuscoli','I.Menu','I.Libricino','I.Libri','I.copertina',
    'I.cataloghi','I.cartoline','I.Calendari.da.Tavolo',
    'I.Calendari.da.Muro','I.Calendari','I.Block.Notes',
    'I.Blocchi.autocopianti','I.Blocchi','I.Bilanci',
    'Espositori.da.Terra','Espositori.da.banco','Depliant','COPERTINA',
    'cataloghi','Calendari.da.Tavolo','Calendari.da.Muro','Calendari',
    'BROSSURATI.OFFSET','BROSSURATI.DIGITALE','brochure','Block.Notes',
    'Blocchi.Mod.TI','Blocchi.Mod.R1','Blocchi.Mod.K','Blocchi.Mod.CH69',
    'Blocchi.autocopianti.M40a','Blocchi.autocopianti','Blocchi','Bilanci',
];

$repartiStampaOffset = Reparto::where('nome', 'stampa offset')->pluck('id');

$dupStampa = DB::table('ordine_fasi')
    ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->join('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
    ->select('ordini.commessa', DB::raw('COUNT(*) as cnt'), DB::raw('MAX(ordini.cod_art) as cod_art'))
    ->whereIn('fasi_catalogo.reparto_id', $repartiStampaOffset)
    ->where('fasi_catalogo.nome', 'like', 'STAMPAXL106%')
    ->whereNull('ordine_fasi.deleted_at')
    ->groupBy('ordini.commessa')
    ->having('cnt', '>', 1)
    ->get();

$totEliminate = 0;

foreach ($dupStampa as $dup) {
    $maxStampa = in_array($dup->cod_art, $codArtMax2) ? 2 : 1;

    if ($dup->cnt <= $maxStampa) continue;

    $faseIds = OrdineFase::whereHas('faseCatalogo', fn($q) =>
            $q->whereIn('reparto_id', $repartiStampaOffset)
              ->where('nome', 'like', 'STAMPAXL106%'))
        ->whereHas('ordine', fn($q) => $q->where('commessa', $dup->commessa))
        ->whereNull('deleted_at')
        ->orderBy('id')
        ->pluck('id');

    $keepIds = $faseIds->take($maxStampa);
    $deleteIds = $faseIds->slice($maxStampa);

    if ($deleteIds->isNotEmpty()) {
        $deleted = OrdineFase::whereIn('id', $deleteIds)->delete();
        $totEliminate += $deleted;
        echo "  {$dup->commessa}: eliminati $deleted duplicati (tenuti $maxStampa, c'erano {$dup->cnt})" . PHP_EOL;
    }
}

// Elimina anche STAMPACALDOJOH duplicate per commessa
$dupJoh = DB::table('ordine_fasi')
    ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->join('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
    ->select('ordini.commessa', 'ordine_fasi.fase_catalogo_id', DB::raw('COUNT(*) as cnt'))
    ->whereIn('fasi_catalogo.reparto_id', Reparto::where('nome', 'stampa a caldo')->pluck('id'))
    ->whereNull('ordine_fasi.deleted_at')
    ->groupBy('ordini.commessa', 'ordine_fasi.fase_catalogo_id')
    ->having('cnt', '>', 1)
    ->get();

foreach ($dupJoh as $dup) {
    $faseIds = OrdineFase::where('fase_catalogo_id', $dup->fase_catalogo_id)
        ->whereHas('ordine', fn($q) => $q->where('commessa', $dup->commessa))
        ->whereNull('deleted_at')
        ->orderBy('id')
        ->pluck('id');

    $keepId = $faseIds->first();
    $deleteIds = $faseIds->slice(1);

    if ($deleteIds->isNotEmpty()) {
        $deleted = OrdineFase::whereIn('id', $deleteIds)->delete();
        $totEliminate += $deleted;
        echo "  {$dup->commessa}: eliminati $deleted JOH duplicati" . PHP_EOL;
    }
}

echo PHP_EOL . "Totale fasi duplicate eliminate: $totEliminate" . PHP_EOL;
