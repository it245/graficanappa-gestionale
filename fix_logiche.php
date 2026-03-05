<?php
/**
 * Sistema tutti i problemi di logica trovati da check_logiche.php.
 * - Aggiunge BRT1 alle commesse che non ce l'hanno
 * - Rimuove BRT duplicati (1 solo per commessa)
 * - Rimuove STAMPA XL duplicate (rispetta max per cod_art)
 * - Rimuove fasi duplicate sullo stesso ordine+fase_catalogo
 * - Unisce ordini con descrizione vuota
 *
 * Uso: php fix_logiche.php [--dry-run]
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\FasiCatalogo;
use App\Models\Reparto;

$dryRun = in_array('--dry-run', $argv);
if ($dryRun) echo "=== DRY RUN ===\n\n";

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

$mappaPriorita = config('fasi_priorita');
$totFix = 0;

// Tutte le commesse attive
$commesse = DB::table('ordini')
    ->join('ordine_fasi', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->whereNull('ordine_fasi.deleted_at')
    ->where('ordine_fasi.stato', '<', 4)
    ->distinct()
    ->pluck('ordini.commessa')
    ->sort()
    ->values();

foreach ($commesse as $commessa) {
    $ordini = Ordine::where('commessa', $commessa)->get();
    $ordineIds = $ordini->pluck('id');
    $fasi = DB::table('ordine_fasi')
        ->whereIn('ordine_id', $ordineIds)
        ->whereNull('deleted_at')
        ->get();

    // ========================================
    // 1. BRT mancante → aggiungi
    // ========================================
    $brt = $fasi->filter(fn($f) => in_array($f->fase, ['BRT1', 'brt1', 'BRT']));
    if ($brt->isEmpty()) {
        $targetOrdine = $ordini->first();
        echo "  FIX BRT: {$commessa} → aggiungo BRT1 su ordine #{$targetOrdine->id}\n";
        if (!$dryRun) {
            $repartoBrt = Reparto::firstOrCreate(['nome' => 'spedizione']);
            $faseCatalogoBrt = FasiCatalogo::firstOrCreate(
                ['nome' => 'BRT1'],
                ['reparto_id' => $repartoBrt->id]
            );
            OrdineFase::create([
                'ordine_id' => $targetOrdine->id,
                'fase' => 'BRT1',
                'fase_catalogo_id' => $faseCatalogoBrt->id,
                'qta_fase' => $targetOrdine->qta_richiesta ?? 0,
                'priorita' => $mappaPriorita['BRT1'] ?? 96,
                'stato' => 0,
                'manuale' => false,
            ]);
        }
        $totFix++;
    }

    // ========================================
    // 2. BRT duplicato → tieni solo 1 per commessa
    // ========================================
    if ($brt->count() > 1) {
        $brtIds = $brt->sortBy('id')->pluck('id');
        $keepId = $brtIds->first();
        $deleteIds = $brtIds->slice(1);
        echo "  FIX BRT DUP: {$commessa} → tengo #{$keepId}, elimino " . $deleteIds->implode(', ') . "\n";
        if (!$dryRun) {
            OrdineFase::whereIn('id', $deleteIds)
                ->where('stato', '<=', 1)
                ->where('manuale', false)
                ->forceDelete();
        }
        $totFix++;
    }

    // ========================================
    // 3. STAMPA XL duplicata → rispetta max
    // ========================================
    $idStampaXL = FasiCatalogo::where('nome', 'like', 'STAMPAXL106%')->pluck('id')->toArray();
    $stampaXl = $fasi->filter(fn($f) => in_array($f->fase_catalogo_id, $idStampaXL));
    $codArts = $ordini->pluck('cod_art')->toArray();
    $maxStampa = collect($codArts)->contains(fn($c) => in_array($c, $codArtMax2)) ? 2 : 1;

    if ($stampaXl->count() > $maxStampa) {
        // Tieni le prime $maxStampa ordinate per stato DESC (più avanzate), poi id ASC
        $sorted = $stampaXl->sortByDesc('stato')->sortBy('id')->values();
        // Ri-ordina: prima per stato desc, poi id asc
        $sorted = $stampaXl->sort(function ($a, $b) {
            if ($a->stato !== $b->stato) return $b->stato - $a->stato;
            return $a->id - $b->id;
        })->values();
        $keepIds = $sorted->take($maxStampa)->pluck('id');
        $deleteIds = $sorted->slice($maxStampa)->pluck('id');

        echo "  FIX STAMPA XL: {$commessa} → tengo " . $keepIds->implode(', ') . ", elimino " . $deleteIds->implode(', ') . "\n";
        if (!$dryRun) {
            foreach ($deleteIds as $delId) {
                $fase = OrdineFase::find($delId);
                if ($fase && $fase->stato <= 1) {
                    $fase->forceDelete();
                } elseif ($fase) {
                    // Soft-delete se già avviata/terminata
                    $fase->delete();
                }
            }
        }
        $totFix++;
    }

    // ========================================
    // 4. Fasi duplicate sullo stesso ordine+fase_catalogo (escluso STAMPAXL106)
    // ========================================
    $perOrdine = $fasi->filter(fn($f) =>
        $f->fase_catalogo_id && !in_array($f->fase_catalogo_id, $idStampaXL)
    )->groupBy(fn($f) => $f->ordine_id . '|' . $f->fase_catalogo_id);

    foreach ($perOrdine as $key => $group) {
        if ($group->count() <= 1) continue;
        $sorted = $group->sort(function ($a, $b) {
            if ($a->stato !== $b->stato) return $b->stato - $a->stato;
            return $a->id - $b->id;
        })->values();
        $keepId = $sorted->first()->id;
        $deleteIds = $sorted->slice(1)->pluck('id');
        $catId = explode('|', $key)[1];
        $nome = FasiCatalogo::find($catId)?->nome ?? '?';

        echo "  FIX DUP: {$commessa} → {$nome} tengo #{$keepId}, elimino " . $deleteIds->implode(', ') . "\n";
        if (!$dryRun) {
            foreach ($deleteIds as $delId) {
                $fase = OrdineFase::find($delId);
                if ($fase && $fase->stato <= 1) {
                    $fase->forceDelete();
                } elseif ($fase) {
                    $fase->delete();
                }
            }
        }
        $totFix++;
    }

    // ========================================
    // 5. Ordini con descrizione vuota → unisci
    // ========================================
    foreach ($ordini as $o) {
        $descNorm = preg_replace('/\s+/', ' ', trim($o->descrizione ?? ''));
        if ($descNorm === '') {
            $hasFasi = $fasi->where('ordine_id', $o->id)->count();
            if ($hasFasi > 0) {
                $keeper = Ordine::where('commessa', $commessa)
                    ->where('cod_art', $o->cod_art)
                    ->where('id', '!=', $o->id)
                    ->whereRaw("TRIM(descrizione) != ''")
                    ->first();
                if ($keeper) {
                    echo "  FIX DESC VUOTA: {$commessa} → unisco ordine #{$o->id} in #{$keeper->id}\n";
                    if (!$dryRun) {
                        OrdineFase::where('ordine_id', $o->id)->update(['ordine_id' => $keeper->id]);
                        $o->delete();
                    }
                    $totFix++;
                }
            }
        }
    }
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "Totale fix: {$totFix}" . ($dryRun ? " (dry-run)" : "") . "\n";

if (!$dryRun && $totFix > 0) {
    echo "\nRicalcolo stati...\n";
    \App\Services\FaseStatoService::ricalcolaTutti();
    echo "Fatto.\n";
}
