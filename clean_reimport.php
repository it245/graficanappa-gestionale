<?php
/**
 * Pulisce e reimporta una commessa da Onda.
 * Uso: php clean_reimport.php 0066492-26
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessa = $argv[1] ?? null;
if (!$commessa) {
    echo "Uso: php clean_reimport.php <commessa>\n";
    exit(1);
}

// Suffisso anno (es. 0066492-26 → 0066492)
$commessaBase = preg_replace('/-\d+$/', '', $commessa);

echo "Pulizia commessa {$commessa}...\n";

// Hard-delete tutte le fasi (incluse soft-deleted)
$ordineIds = DB::table('ordini')->where('commessa', $commessa)->pluck('id');
$fasiDelete = DB::table('ordine_fasi')->whereIn('ordine_id', $ordineIds)->delete();
echo "  Fasi eliminate: {$fasiDelete}\n";

// Hard-delete ordini
$ordiniDelete = DB::table('ordini')->where('commessa', $commessa)->delete();
echo "  Ordini eliminati: {$ordiniDelete}\n";

echo "\nReimportazione da Onda ({$commessaBase})...\n";

// Chiama import_commessa come processo separato
passthru("php " . __DIR__ . "/import_commessa.php {$commessaBase}");
