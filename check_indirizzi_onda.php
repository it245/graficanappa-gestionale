<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
error_reporting(E_ALL & ~E_DEPRECATED);

// Cerca tabelle con "indirizzo" o "destinazione" nel nome
echo "=== TABELLE ONDA CON 'INDIRIZZO' O 'DEST' ===\n";
$tabelle = DB::connection('onda')->select("
    SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_NAME LIKE '%ndiri%' OR TABLE_NAME LIKE '%Dest%' OR TABLE_NAME LIKE '%Sede%'
    ORDER BY TABLE_NAME
");
foreach ($tabelle as $t) echo "  {$t->TABLE_NAME}\n";

// Cerca il cliente Italiana Confetti
echo "\n=== ITALIANA CONFETTI — IdAnagrafica ===\n";
$cliente = DB::connection('onda')->selectOne("
    SELECT IdAnagrafica, RagioneSociale, Indirizzo, Citta, Cap, Provincia
    FROM STDAnagrafiche WHERE RagioneSociale LIKE '%ITALIANA CONFETTI%'
");
if ($cliente) {
    echo "  Id={$cliente->IdAnagrafica} {$cliente->RagioneSociale}\n";
    echo "  {$cliente->Indirizzo}, {$cliente->Cap} {$cliente->Citta} {$cliente->Provincia}\n";

    // Cerca indirizzi secondari per questo cliente
    echo "\n=== INDIRIZZI SECONDARI (STDIndirizzi?) ===\n";
    $tabs = DB::connection('onda')->select("
        SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_NAME LIKE '%Indiriz%' OR TABLE_NAME LIKE '%Address%'
    ");
    foreach ($tabs as $t) {
        echo "\n  Tabella: {$t->TABLE_NAME}\n";
        $cols = DB::connection('onda')->select("
            SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = ? ORDER BY ORDINAL_POSITION
        ", [$t->TABLE_NAME]);
        echo "  Colonne: " . implode(', ', array_map(fn($c) => $c->COLUMN_NAME, $cols)) . "\n";

        // Cerca righe per questo cliente
        $hasIdAna = collect($cols)->contains(fn($c) => $c->COLUMN_NAME === 'IdAnagrafica');
        if ($hasIdAna) {
            $righe = DB::connection('onda')->select("
                SELECT TOP 5 * FROM [{$t->TABLE_NAME}] WHERE IdAnagrafica = ?
            ", [$cliente->IdAnagrafica]);
            foreach ($righe as $r) {
                echo "  → " . json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
            }
        }
    }
}
