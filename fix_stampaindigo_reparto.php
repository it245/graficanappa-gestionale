<?php
/**
 * Fix STAMPAINDIGO assente/errato nel catalogo fasi reparto digitale.
 * Uso: php fix_stampaindigo_reparto.php          → dry-run
 *       php fix_stampaindigo_reparto.php --apply → applica
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\FasiCatalogo;
use App\Models\OrdineFase;
use App\Models\Reparto;
use Illuminate\Support\Facades\DB;

$apply = in_array('--apply', $argv);

// 1. Recupera reparto digitale
$repartoDigitale = Reparto::where('nome', 'digitale')->first();
if (!$repartoDigitale) {
    die("ERR: reparto 'digitale' non trovato\n");
}
echo "Reparto digitale id = {$repartoDigitale->id}\n\n";

// 2. Verifica stato STAMPAINDIGO nel catalogo
$catIndigo = FasiCatalogo::where('nome', 'STAMPAINDIGO')->first();

if (!$catIndigo) {
    echo "STAMPAINDIGO NON presente in fasi_catalogo\n";
    if ($apply) {
        $nuova = FasiCatalogo::create([
            'nome' => 'STAMPAINDIGO',
            'reparto_id' => $repartoDigitale->id,
        ]);
        echo "  → CREATA FasiCatalogo id={$nuova->id}\n";
    } else {
        echo "  → [dry-run] creerei FasiCatalogo STAMPAINDIGO → digitale\n";
    }
} else {
    $repAttuale = $catIndigo->reparto->nome ?? 'NULL';
    echo "STAMPAINDIGO già in catalogo id={$catIndigo->id} reparto attuale: {$repAttuale}\n";
    if ($catIndigo->reparto_id !== $repartoDigitale->id) {
        if ($apply) {
            $catIndigo->reparto_id = $repartoDigitale->id;
            $catIndigo->save();
            echo "  → AGGIORNATO reparto_id a {$repartoDigitale->id}\n";
        } else {
            echo "  → [dry-run] sposterei da {$repAttuale} a digitale\n";
        }
    } else {
        echo "  → OK già nel reparto digitale\n";
    }
}

// 3. Ri-catalogo: aggiorna ordine_fasi.fase_catalogo_id per tutte STAMPAINDIGO senza link o con link vecchio
$catIndigo = FasiCatalogo::where('nome', 'STAMPAINDIGO')->where('reparto_id', $repartoDigitale->id)->first();

if ($catIndigo) {
    $fasiDaAggiornare = OrdineFase::where('fase', 'STAMPAINDIGO')
        ->where(function ($q) use ($catIndigo) {
            $q->whereNull('fase_catalogo_id')
              ->orWhere('fase_catalogo_id', '!=', $catIndigo->id);
        })
        ->count();

    echo "\nFasi STAMPAINDIGO con fase_catalogo_id errato/mancante: {$fasiDaAggiornare}\n";

    if ($apply && $fasiDaAggiornare > 0) {
        $n = OrdineFase::where('fase', 'STAMPAINDIGO')
            ->where(function ($q) use ($catIndigo) {
                $q->whereNull('fase_catalogo_id')
                  ->orWhere('fase_catalogo_id', '!=', $catIndigo->id);
            })
            ->update(['fase_catalogo_id' => $catIndigo->id]);
        echo "  → {$n} fasi aggiornate con fase_catalogo_id={$catIndigo->id}\n";
    } elseif ($fasiDaAggiornare > 0) {
        echo "  → [dry-run] aggiornerei {$fasiDaAggiornare} fasi ordine_fasi\n";
    }
}

// 4. Verifica finale conteggi dopo (se apply)
if ($apply) {
    echo "\n=== Verifica finale ===\n";
    $rows = DB::table('ordine_fasi as of')
        ->join('fasi_catalogo as fc', 'of.fase_catalogo_id', 'fc.id')
        ->join('reparti as r', 'fc.reparto_id', 'r.id')
        ->where('r.nome', 'digitale')
        ->select('of.stato', DB::raw('COUNT(*) as n'))
        ->groupBy('of.stato')
        ->orderBy('of.stato')
        ->get();
    foreach ($rows as $r) {
        echo "  stato={$r->stato} : {$r->n}\n";
    }
}

echo "\n";
echo $apply ? "Fix applicato.\n" : "DRY-RUN. Rilancia con --apply per applicare.\n";
