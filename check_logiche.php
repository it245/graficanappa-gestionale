<?php
/**
 * Verifica tutte le logiche del MES su tutte le commesse attive.
 *
 * Uso: php check_logiche.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\FasiCatalogo;
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

// Tutte le commesse attive
$commesse = DB::table('ordini')
    ->join('ordine_fasi', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->whereNull('ordine_fasi.deleted_at')
    ->where('ordine_fasi.stato', '<', 4)
    ->distinct()
    ->pluck('ordini.commessa')
    ->sort()
    ->values();

echo "Commesse attive: " . $commesse->count() . "\n";
echo str_repeat('=', 80) . "\n\n";

$problemi = [];
$stats = [
    'brt_mancante' => 0,
    'brt_duplicato' => 0,
    'stampa_duplicata' => 0,
    'fustella_duplicata' => 0,
    'fase_duplicata_ordine' => 0,
    'ordine_desc_vuota' => 0,
    'fase_no_catalogo' => 0,
    'fase_no_reparto' => 0,
    'priorita_negativa' => 0,
    'stato_incoerente' => 0,
];

foreach ($commesse as $commessa) {
    $errori = [];

    $ordini = Ordine::where('commessa', $commessa)->get();
    $ordineIds = $ordini->pluck('id');

    $fasi = DB::table('ordine_fasi')
        ->whereIn('ordine_id', $ordineIds)
        ->whereNull('deleted_at')
        ->get();

    // ========================================
    // 1. BRT1: deve esistere esattamente 1
    // ========================================
    $brtCount = $fasi->filter(fn($f) => in_array($f->fase, ['BRT1', 'brt1', 'BRT']))->count();
    if ($brtCount === 0) {
        $errori[] = "BRT MANCANTE";
        $stats['brt_mancante']++;
    } elseif ($brtCount > 1) {
        $errori[] = "BRT DUPLICATO (x{$brtCount})";
        $stats['brt_duplicato']++;
    }

    // ========================================
    // 2. STAMPAXL106 dedup per commessa
    // ========================================
    $stampaXl = $fasi->filter(fn($f) =>
        $f->fase_catalogo_id && FasiCatalogo::find($f->fase_catalogo_id)?->nome &&
        str_starts_with(FasiCatalogo::find($f->fase_catalogo_id)->nome, 'STAMPAXL106')
    );
    $codArts = $ordini->pluck('cod_art')->toArray();
    $maxStampa = collect($codArts)->contains(fn($c) => in_array($c, $codArtMax2)) ? 2 : 1;
    if ($stampaXl->count() > $maxStampa) {
        $errori[] = "STAMPA XL DUPLICATA ({$stampaXl->count()}/{$maxStampa})";
        $stats['stampa_duplicata']++;
    }

    // ========================================
    // 3. Fustella dedup per commessa per fase_catalogo
    // ========================================
    $repartiFust = Reparto::whereIn('nome', ['fustella piana', 'fustella cilindrica', 'fustella'])->pluck('id');
    $fasiFust = $fasi->filter(fn($f) =>
        $f->fase_catalogo_id && FasiCatalogo::find($f->fase_catalogo_id) &&
        in_array(FasiCatalogo::find($f->fase_catalogo_id)->reparto_id, $repartiFust->toArray())
    );
    $fustPerCatalogo = $fasiFust->groupBy('fase_catalogo_id');
    foreach ($fustPerCatalogo as $catId => $group) {
        if ($group->count() > 1) {
            $nome = FasiCatalogo::find($catId)?->nome ?? '?';
            $errori[] = "FUSTELLA DUPLICATA: {$nome} x{$group->count()}";
            $stats['fustella_duplicata']++;
        }
    }

    // ========================================
    // 4. Duplicati per ordine+fase_catalogo (escluso STAMPAXL106)
    // ========================================
    $idStampaXL = FasiCatalogo::where('nome', 'like', 'STAMPAXL106%')->pluck('id')->toArray();
    $perOrdine = $fasi->filter(fn($f) =>
        $f->fase_catalogo_id && !in_array($f->fase_catalogo_id, $idStampaXL)
    )->groupBy(fn($f) => $f->ordine_id . '|' . $f->fase_catalogo_id);
    foreach ($perOrdine as $key => $group) {
        if ($group->count() > 1) {
            $catId = explode('|', $key)[1];
            $nome = FasiCatalogo::find($catId)?->nome ?? '?';
            $errori[] = "FASE DUPLICATA su ordine: {$nome} x{$group->count()}";
            $stats['fase_duplicata_ordine']++;
        }
    }

    // ========================================
    // 5. Ordini con descrizione vuota
    // ========================================
    foreach ($ordini as $o) {
        $descNorm = preg_replace('/\s+/', ' ', trim($o->descrizione ?? ''));
        if ($descNorm === '') {
            $hasFasi = $fasi->where('ordine_id', $o->id)->count();
            if ($hasFasi > 0) {
                $errori[] = "ORDINE #{$o->id} DESC VUOTA con {$hasFasi} fasi";
                $stats['ordine_desc_vuota']++;
            }
        }
    }

    // ========================================
    // 6. Fasi senza fase_catalogo_id
    // ========================================
    $senzaCatalogo = $fasi->whereNull('fase_catalogo_id')->count();
    if ($senzaCatalogo > 0) {
        $errori[] = "FASI SENZA CATALOGO: {$senzaCatalogo}";
        $stats['fase_no_catalogo']++;
    }

    // ========================================
    // 7. Fasi con fase_catalogo senza reparto
    // ========================================
    foreach ($fasi as $f) {
        if ($f->fase_catalogo_id) {
            $cat = FasiCatalogo::find($f->fase_catalogo_id);
            if ($cat && !$cat->reparto_id) {
                $errori[] = "FASE #{$f->id} ({$cat->nome}) SENZA REPARTO";
                $stats['fase_no_reparto']++;
            }
        }
    }

    // ========================================
    // 8. Priorità negativa
    // ========================================
    $negPri = $fasi->where('priorita', '<', 0)->count();
    if ($negPri > 0) {
        $errori[] = "PRIORITA NEGATIVA: {$negPri} fasi";
        $stats['priorita_negativa']++;
    }

    // ========================================
    // 9. Stato incoerente: fase a stato 1 (pronto) ma predecessore non terminato
    // ========================================
    $mappaPriorita = config('fasi_priorita');
    $fasiOrdinate = $fasi->sortBy('priorita');
    foreach ($fasiOrdinate as $f) {
        if ($f->stato == 1) {
            // Cerca fasi con priorità minore (predecessori) non terminate
            $predecessoriNonTerminati = $fasi->filter(fn($p) =>
                $p->id !== $f->id &&
                $p->priorita < $f->priorita &&
                $p->stato < 3 &&
                $p->ordine_id === $f->ordine_id
            )->count();
            // Non segnalare se la prima fase
            $isFirst = !$fasi->contains(fn($p) =>
                $p->id !== $f->id &&
                $p->priorita < $f->priorita &&
                $p->ordine_id === $f->ordine_id
            );
            if ($predecessoriNonTerminati > 0 && !$isFirst) {
                $nome = FasiCatalogo::find($f->fase_catalogo_id)?->nome ?? $f->fase;
                $errori[] = "STATO INCOERENTE: {$nome} #{$f->id} stato=1 ma {$predecessoriNonTerminati} predecessori non terminati";
                $stats['stato_incoerente']++;
            }
        }
    }

    if (!empty($errori)) {
        $problemi[$commessa] = $errori;
        echo "COMMESSA: {$commessa}\n";
        foreach ($errori as $e) {
            echo "  ⚠ {$e}\n";
        }
        echo "\n";
    }
}

echo str_repeat('=', 80) . "\n";
echo "RIEPILOGO\n";
echo str_repeat('-', 40) . "\n";
echo "Commesse attive:              " . $commesse->count() . "\n";
echo "Commesse con problemi:        " . count($problemi) . "\n";
echo str_repeat('-', 40) . "\n";
echo "BRT mancante:                 {$stats['brt_mancante']}\n";
echo "BRT duplicato:                {$stats['brt_duplicato']}\n";
echo "STAMPA XL duplicata:          {$stats['stampa_duplicata']}\n";
echo "Fustella duplicata:           {$stats['fustella_duplicata']}\n";
echo "Fase duplicata su ordine:     {$stats['fase_duplicata_ordine']}\n";
echo "Ordine desc vuota con fasi:   {$stats['ordine_desc_vuota']}\n";
echo "Fasi senza catalogo:          {$stats['fase_no_catalogo']}\n";
echo "Fasi senza reparto:           {$stats['fase_no_reparto']}\n";
echo "Priorita negativa:            {$stats['priorita_negativa']}\n";
echo "Stato incoerente:             {$stats['stato_incoerente']}\n";
