<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Reparto;

$oggi = now()->format('Y-m-d');
echo "=== DEBUG BOBST ORE SEGNATE {$oggi} ===\n\n";

// 1. Reparto fustella piana
$repartoIds = Reparto::whereIn('nome', ['fustella piana'])->pluck('id');
echo "Reparto IDs fustella piana: " . $repartoIds->implode(', ') . "\n\n";

// 2. Fasi stato 2 nel reparto
$fasiAperte = DB::table('ordine_fasi')
    ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
    ->whereIn('fasi_catalogo.reparto_id', $repartoIds)
    ->whereNull('ordine_fasi.deleted_at')
    ->where('ordine_fasi.stato', 2)
    ->select('ordine_fasi.id', 'ordine_fasi.fase', 'ordine_fasi.stato', 'ordine_fasi.data_inizio', 'ordine_fasi.esterno', 'ordine_fasi.note')
    ->get();

echo "--- Fasi stato 2 fustella piana ---\n";
foreach ($fasiAperte as $f) {
    echo "  ID={$f->id} Fase={$f->fase} Stato={$f->stato} DataInizio={$f->data_inizio} Esterno={$f->esterno} Note=" . substr($f->note ?? '-', 0, 30) . "\n";
}

// 3. Fasi stato NON numerico (pausa) nel reparto - come 22522
$fasiPausa = DB::table('ordine_fasi')
    ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
    ->whereIn('fasi_catalogo.reparto_id', $repartoIds)
    ->whereNull('ordine_fasi.deleted_at')
    ->whereRaw("ordine_fasi.stato NOT REGEXP '^[0-5]$'")
    ->where('ordine_fasi.stato', '!=', 4)
    ->select('ordine_fasi.id', 'ordine_fasi.fase', 'ordine_fasi.stato', 'ordine_fasi.data_inizio')
    ->limit(5)
    ->get();

echo "\n--- Fasi in pausa fustella piana ---\n";
foreach ($fasiPausa as $f) {
    echo "  ID={$f->id} Fase={$f->fase} Stato={$f->stato} DataInizio={$f->data_inizio}\n";
}

// 4. Calcolo ore come fa il kiosk
$inizioTurno = $oggi . ' 06:00:00';
$secAperte = DB::table('ordine_fasi')
    ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
    ->whereIn('fasi_catalogo.reparto_id', $repartoIds)
    ->whereNull('ordine_fasi.deleted_at')
    ->where(fn($q) => $q->where('ordine_fasi.esterno', 0)->orWhereNull('ordine_fasi.esterno'))
    ->where(fn($q) => $q->whereNull('ordine_fasi.note')->orWhere('ordine_fasi.note', 'NOT LIKE', '%Inviato a:%'))
    ->where('ordine_fasi.stato', 2)
    ->selectRaw("SUM(TIMESTAMPDIFF(SECOND, GREATEST(COALESCE(ordine_fasi.data_inizio, ?), ?), NOW())) as sec", [$inizioTurno, $inizioTurno])
    ->value('sec');

echo "\n--- Calcolo ore ---\n";
echo "  Secondi fasi aperte: " . ($secAperte ?? 0) . "\n";
echo "  Ore: " . round(($secAperte ?? 0) / 3600, 1) . "h\n";

// 5. Replica ESATTA della query kiosk utilizzo per BOBST
echo "\n--- 5. Query ESATTA kiosk utilizzo ---\n";
$ieri = \Carbon\Carbon::yesterday()->format('Y-m-d');

// A. Fasi aperte stato 2
$secA = DB::table('ordine_fasi')
    ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
    ->whereIn('fasi_catalogo.reparto_id', $repartoIds)
    ->whereNull('ordine_fasi.deleted_at')
    ->where(fn($q) => $q->where('ordine_fasi.esterno', 0)->orWhereNull('ordine_fasi.esterno'))
    ->where(fn($q) => $q->whereNull('ordine_fasi.note')->orWhere('ordine_fasi.note', 'NOT LIKE', '%Inviato a:%'))
    ->where('ordine_fasi.stato', 2)
    ->selectRaw("SUM(TIMESTAMPDIFF(SECOND, GREATEST(COALESCE(ordine_fasi.data_inizio, ?), ?), NOW())) as sec", [$inizioTurno, $inizioTurno])
    ->value('sec');
echo "  A. Fasi aperte stato 2: " . ($secA ?? 0) . "s = " . round(($secA ?? 0)/3600, 1) . "h\n";

// B. Fasi chiuse oggi avviate da ieri
$secB = DB::table('ordine_fasi')
    ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
    ->whereIn('fasi_catalogo.reparto_id', $repartoIds)
    ->whereNull('ordine_fasi.deleted_at')
    ->where(fn($q) => $q->where('ordine_fasi.esterno', 0)->orWhereNull('ordine_fasi.esterno'))
    ->where(fn($q) => $q->whereNull('ordine_fasi.note')->orWhere('ordine_fasi.note', 'NOT LIKE', '%Inviato a:%'))
    ->where('ordine_fasi.stato', 3)
    ->whereDate('ordine_fasi.data_fine', $oggi)
    ->where('ordine_fasi.data_inizio', '>=', $ieri . ' 00:00:00')
    ->selectRaw("SUM(TIMESTAMPDIFF(SECOND, GREATEST(ordine_fasi.data_inizio, ?), ordine_fasi.data_fine)) as sec", [$inizioTurno])
    ->value('sec');
echo "  B. Fasi chiuse oggi (recenti): " . ($secB ?? 0) . "s = " . round(($secB ?? 0)/3600, 1) . "h\n";

// Pause
$secP = DB::table('pausa_operatores')
    ->join('ordini', 'pausa_operatores.ordine_id', '=', 'ordini.id')
    ->join('ordine_fasi', function ($j) {
        $j->on('ordine_fasi.ordine_id', '=', 'ordini.id')
          ->on('ordine_fasi.fase', '=', 'pausa_operatores.fase');
    })
    ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
    ->whereIn('fasi_catalogo.reparto_id', $repartoIds)
    ->whereNull('ordine_fasi.deleted_at')
    ->whereDate('pausa_operatores.data_ora', $oggi)
    ->selectRaw("SUM(TIMESTAMPDIFF(SECOND, GREATEST(pausa_operatores.data_ora, ?), COALESCE(pausa_operatores.fine, NOW()))) as sec", [$inizioTurno])
    ->value('sec');
echo "  Pause oggi: " . ($secP ?? 0) . "s = " . round(($secP ?? 0)/3600, 1) . "h\n";

$totale = max(($secA ?? 0) + ($secB ?? 0) - ($secP ?? 0), 0);
$oreUsate = round($totale / 3600, 1);
$oraCorrente = (float) now()->format('H') + (float) now()->format('i') / 60;
$oreDispOra = max(min($oraCorrente - 6, 16), 0.5);
$pct = $oreDispOra > 0 ? min(round(($oreUsate / $oreDispOra) * 100), 100) : 0;
echo "  Totale netto: {$totale}s = {$oreUsate}h\n";
echo "  Ore disponibili finora: {$oreDispOra}h\n";
echo "  Percentuale: {$pct}%\n";

echo "\nDone.\n";
