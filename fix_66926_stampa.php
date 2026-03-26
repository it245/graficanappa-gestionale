<?php
// Fix 66926: aggiungi STAMPA XL mancante per l'ordine cataloghi
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessa = '0066926-26';

// Ordine cataloghi (senza STAMPA XL)
$ordineCataloghi = DB::table('ordini')
    ->where('commessa', $commessa)
    ->where('cod_art', 'cataloghi')
    ->first();

if (!$ordineCataloghi) { echo "Ordine cataloghi non trovato\n"; exit(1); }

// Controlla se ha già una STAMPA XL
$haStampa = DB::table('ordine_fasi')
    ->where('ordine_id', $ordineCataloghi->id)
    ->where('fase', 'LIKE', 'STAMPAXL%')
    ->whereNull('deleted_at')
    ->exists();

if ($haStampa) { echo "Ordine cataloghi #{$ordineCataloghi->id} ha già STAMPA XL\n"; exit(0); }

// Prendi il fase_catalogo_id dalla COPERTINA
$stampaCopertina = DB::table('ordine_fasi')
    ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
    ->where('ordini.commessa', $commessa)
    ->where('ordine_fasi.fase', 'LIKE', 'STAMPAXL%')
    ->whereNull('ordine_fasi.deleted_at')
    ->select('ordine_fasi.fase', 'ordine_fasi.fase_catalogo_id', 'ordine_fasi.scarti_previsti')
    ->first();

if (!$stampaCopertina) { echo "STAMPA XL non trovata nella commessa\n"; exit(1); }

echo "Ordine cataloghi #{$ordineCataloghi->id} | QtaCarta: {$ordineCataloghi->qta_carta}\n";
echo "Copio da: {$stampaCopertina->fase}\n";

DB::table('ordine_fasi')->insert([
    'ordine_id' => $ordineCataloghi->id,
    'fase' => $stampaCopertina->fase,
    'fase_catalogo_id' => $stampaCopertina->fase_catalogo_id,
    'stato' => 0,
    'qta_fase' => $ordineCataloghi->qta_carta ?? 16300,
    'qta_prod' => 0,
    'scarti_previsti' => $stampaCopertina->scarti_previsti,
    'esterno' => 0,
    'manuale' => 0,
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "STAMPA XL creata per cataloghi (qta: {$ordineCataloghi->qta_carta})\n";
