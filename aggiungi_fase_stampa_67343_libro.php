<?php
/**
 * Aggiunge fase STAMPAXL106.1 mancante al libro interno commessa 67343.
 * Onda ha la fase STAMPA seq=10 XL106-1 ma il sync MES l'ha skippata.
 * Esegui con --confirm.
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$confirm = in_array('--confirm', $argv);

// Trova ordine libro 67343
$ordineLibro = DB::table('ordini')
    ->where('commessa', '0067343-26')
    ->where('cod_art', 'Libri')
    ->first();

if (!$ordineLibro) {
    echo "❌ Ordine libro 67343 non trovato\n";
    exit(1);
}

echo "Ordine libro:\n";
echo "  id={$ordineLibro->id} | desc={$ordineLibro->descrizione}\n";

// Cerca fase_catalogo STAMPAXL106.1
$faseCat = DB::table('fasi_catalogo')->where('nome', 'STAMPAXL106.1')->first();
if (!$faseCat) {
    echo "❌ Fase catalogo STAMPAXL106.1 non trovata\n";
    exit(1);
}

echo "Fase catalogo: id={$faseCat->id} | nome={$faseCat->nome} | reparto_id={$faseCat->reparto_id}\n";

// Verifica non esista già
$esistente = DB::table('ordine_fasi')
    ->where('ordine_id', $ordineLibro->id)
    ->where('fase', 'STAMPAXL106.1')
    ->whereNull('deleted_at')
    ->first();
if ($esistente) {
    echo "Fase già esiste (id={$esistente->id}). Nulla da fare.\n";
    exit(0);
}

// Calcola qta_carta da copertina (assumiamo stesso target stampa)
$qtaCopertina = DB::table('ordine_fasi as f')
    ->join('ordini as o', 'o.id', 'f.ordine_id')
    ->where('o.commessa', '0067343-26')
    ->where('f.fase', 'STAMPAXL106.1')
    ->value('f.qta_fase');

echo "qta_carta copertina come riferimento: " . ($qtaCopertina ?? 'NULL') . "\n";

if (!$confirm) {
    echo "\n⚠️  PREVIEW. Esegui con --confirm per aggiungere fase.\n";
    exit(0);
}

$nuovoId = DB::table('ordine_fasi')->insertGetId([
    'ordine_id'        => $ordineLibro->id,
    'fase'             => 'STAMPAXL106.1',
    'fase_catalogo_id' => $faseCat->id,
    'stato'            => '1',
    'priorita'         => 0,
    'qta_fase'         => $qtaCopertina ?? 0,
    'manuale'          => 1,
    'created_at'       => now(),
    'updated_at'       => now(),
]);

echo "\n✅ Fase aggiunta: id={$nuovoId} | STAMPAXL106.1 | ordine={$ordineLibro->id}\n";
