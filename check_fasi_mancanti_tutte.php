<?php
/**
 * Controlla TUTTE le commesse nel MES: per ciascuna confronta le fasi
 * presenti nel MES con quelle in Onda e segnala le mancanti.
 * - Ignora "STAMPA" (fase generica Onda, coperta da fasi specifiche nel MES)
 * - Gestisce prefisso EXT (EXTALLEST.SHOPPER = ALLEST.SHOPPER)
 * - Gestisce STAMPAXL106 senza suffisso (= STAMPAXL106.1 nel MES)
 * - Gestisce STAMPAINDIGO/STAMPAINDIGOBN → "STAMPA" digitale
 *
 * Uso: php check_fasi_mancanti_tutte.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Tutte le commesse attive nel MES (con almeno 1 fase non consegnata)
$commesse = DB::table('ordini')
    ->join('ordine_fasi', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->whereNull('ordine_fasi.deleted_at')
    ->where('ordine_fasi.stato', '<', 4)
    ->distinct()
    ->pluck('ordini.commessa')
    ->sort()
    ->values();

echo "Commesse attive nel MES: " . $commesse->count() . "\n";
echo str_repeat('=', 80) . "\n\n";

$problemi = 0;

// Fasi da ignorare nel confronto (generiche Onda coperte da fasi specifiche MES)
$fasiIgnorate = ['STAMPA'];

foreach ($commesse as $commessa) {
    // Fasi nel MES (non soft-deleted)
    $fasiMes = DB::table('ordine_fasi')
        ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
        ->leftJoin('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
        ->where('ordini.commessa', $commessa)
        ->whereNull('ordine_fasi.deleted_at')
        ->select('fasi_catalogo.nome as fase_nome')
        ->pluck('fase_nome')
        ->filter()
        ->unique()
        ->values();

    // Fasi in Onda
    $fasiOnda = collect(DB::connection('onda')->select("
        SELECT DISTINCT f.CodFase
        FROM PRDDocTeste p
        JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
        WHERE p.CodCommessa = ?
        ORDER BY f.CodFase
    ", [$commessa]))->pluck('CodFase');

    if ($fasiOnda->isEmpty()) continue;

    // Trova mancanti con logica intelligente
    $mancanti = [];
    foreach ($fasiOnda as $faseOnda) {
        // Ignora fasi generiche
        if (in_array($faseOnda, $fasiIgnorate)) continue;

        // Match diretto
        if ($fasiMes->contains($faseOnda)) continue;

        // Match EXT: EXTALLEST.SHOPPER → ALLEST.SHOPPER
        if (str_starts_with($faseOnda, 'EXT')) {
            $senzaExt = substr($faseOnda, 3);
            if ($fasiMes->contains($senzaExt)) continue;
            // Match parziale (troncamento Onda): EXTACCOPP.FUST.INCOLL.FOG → ACCOPP.FUST.INCOLL.FOGLI
            $found = $fasiMes->first(fn($m) => str_starts_with($m, $senzaExt) || str_starts_with($senzaExt, $m));
            if ($found) continue;
        }

        // Match inverso: MES ha "esterno" o fase con EXT prefix
        if ($fasiMes->contains('EXT' . $faseOnda)) continue;
        if ($fasiMes->contains('esterno')) {
            // Fase esterna generica nel MES
            // Non contarla come mancante se il MES ha una fase "esterno" catch-all
        }

        // STAMPAXL106 senza suffisso → STAMPAXL106.1 nel MES
        if ($faseOnda === 'STAMPAXL106' && $fasiMes->contains('STAMPAXL106.1')) continue;

        // STAMPA.ESTERNA → check se MES ha la fase
        // STAMPACALDO04 → variante stampa a caldo
        // STAMPACALDOJOH0,1 / 0,2 → varianti stampa a caldo

        $mancanti[] = $faseOnda;
    }

    // Controlla anche il contrario: fasi nel MES che non sono in Onda (fasi fantasma)
    $fantasma = [];
    foreach ($fasiMes as $faseMes) {
        if ($faseMes === 'BRT1') continue; // BRT1 è sempre aggiunto dal MES

        // Match diretto
        if ($fasiOnda->contains($faseMes)) continue;

        // Match EXT inverso: MES ha ALLEST.SHOPPER, Onda ha EXTALLEST.SHOPPER
        if ($fasiOnda->contains('EXT' . $faseMes)) continue;

        // STAMPAXL106.1 → Onda potrebbe avere STAMPAXL106 (senza suffisso)
        if (str_starts_with($faseMes, 'STAMPAXL106') && $fasiOnda->contains('STAMPAXL106')) continue;

        // STAMPAINDIGO/STAMPAINDIGOBN → Onda ha "STAMPA" (ignorata)
        if (in_array($faseMes, ['STAMPAINDIGO', 'STAMPAINDIGOBN', 'STAMPAXL106', 'STAMPAXL106.1'])) {
            if ($fasiOnda->contains('STAMPA')) continue;
        }

        // Fase "esterno" generica → potrebbe corrispondere a qualsiasi EXT
        if ($faseMes === 'esterno') continue;

        // Match parziale per troncamento
        $found = $fasiOnda->first(fn($o) => str_starts_with($o, 'EXT' . $faseMes) || $o === $faseMes);
        if ($found) continue;

        // 4graph → fase manuale, non in Onda
        if ($faseMes === '4graph') continue;

        $fantasma[] = $faseMes;
    }

    if (!empty($mancanti) || !empty($fantasma)) {
        $problemi++;
        echo "COMMESSA: {$commessa}\n";
        echo "  MES:     " . $fasiMes->implode(', ') . "\n";
        echo "  ONDA:    " . $fasiOnda->implode(', ') . "\n";
        if (!empty($mancanti)) {
            echo "  MANCA:   " . implode(', ', $mancanti) . "\n";
        }
        if (!empty($fantasma)) {
            echo "  EXTRA:   " . implode(', ', $fantasma) . " (nel MES ma non in Onda)\n";
        }
        echo "\n";
    }
}

echo str_repeat('=', 80) . "\n";
echo "Commesse con differenze: {$problemi} / " . $commesse->count() . "\n";
