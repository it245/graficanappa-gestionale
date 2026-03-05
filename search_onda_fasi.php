<?php
/**
 * Cerca fasi in Onda per pattern.
 * Uso: php search_onda_fasi.php RILIEVO
 *      php search_onda_fasi.php (senza argomenti = mostra TUTTE le fasi distinte)
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$pattern = $argv[1] ?? null;

if ($pattern) {
    $righe = DB::connection('onda')->select(
        "SELECT DISTINCT f.CodFase FROM PRDDocFasi f WHERE f.CodFase LIKE ? ORDER BY f.CodFase",
        ['%' . $pattern . '%']
    );
    echo "Fasi Onda contenenti '{$pattern}':\n";
} else {
    $righe = DB::connection('onda')->select(
        "SELECT DISTINCT f.CodFase FROM PRDDocFasi f ORDER BY f.CodFase"
    );
    echo "Tutte le fasi Onda distinte:\n";
}

foreach ($righe as $r) {
    echo "  " . $r->CodFase . "\n";
}
echo "\nTotale: " . count($righe) . "\n";
