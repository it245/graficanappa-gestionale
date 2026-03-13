<?php
// Uso: php cerca_fasi_onda.php 0066649-26
// Cerca TUTTE le fasi in PRDDocTeste (senza passare per ATTDocTeste)
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessa = $argv[1] ?? '0066649-26';

echo "=== FASI PRDDocTeste per $commessa ===\n\n";

$righe = DB::connection('onda')->select("
    SELECT p.IdDoc, p.CodArt, p.OC_Descrizione, f.CodFase, f.CodMacchina, f.QtaDaLavorare, f.CodUnMis
    FROM PRDDocTeste p
    LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
    WHERE p.CodCommessa = ?
    ORDER BY p.IdDoc, f.CodFase
", [$commessa]);

if (empty($righe)) {
    echo "Nessun risultato.\n";
    exit;
}

printf("%-8s | %-25s | %-40s | %-20s | %-15s | %s\n", "IdDoc", "CodArt", "Descrizione", "CodFase", "Macchina", "Qta");
echo str_repeat('-', 130) . "\n";

foreach ($righe as $r) {
    printf("%-8s | %-25s | %-40s | %-20s | %-15s | %s %s\n",
        $r->IdDoc,
        mb_substr($r->CodArt ?? '', 0, 25),
        mb_substr($r->OC_Descrizione ?? '', 0, 40),
        $r->CodFase ?? '(nessuna)',
        $r->CodMacchina ?? '-',
        $r->QtaDaLavorare ?? '-',
        $r->CodUnMis ?? ''
    );
}

echo "\nTotale righe: " . count($righe) . "\n";
