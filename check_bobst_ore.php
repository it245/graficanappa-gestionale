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

echo "\nDone.\n";
