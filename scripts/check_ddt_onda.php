<?php
/**
 * Query Onda SQL Server: righe DDT vendita con cod_art reale.
 * Uso: php scripts\check_ddt_onda.php 0001177
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$ddt = $argv[1] ?? null;
if (!$ddt) {
    echo "Uso: php scripts\\check_ddt_onda.php <numero_ddt>\n";
    exit(1);
}

echo "Query Onda DB per DDT $ddt...\n\n";

// Cerco struttura tabelle DDT in Onda
try {
    $tabelle = DB::connection('onda')->select("
        SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_NAME LIKE '%DDT%' OR TABLE_NAME LIKE '%ATT%Vendita%' OR TABLE_NAME LIKE '%ATTVe%'
        ORDER BY TABLE_NAME
    ");
    echo "Tabelle DDT/ATT trovate in Onda:\n";
    foreach ($tabelle as $t) echo "  - " . $t->TABLE_NAME . "\n";
    echo "\n";
} catch (\Throwable $e) {
    echo "Errore connessione Onda: " . $e->getMessage() . "\n";
    exit(1);
}

// Tentativo standard Onda: ATTVeTeste + ATTVeRighe (DDT vendita)
$ddtNum = ltrim($ddt, '0');
$candidates = [
    ['teste' => 'ATTVeTeste', 'righe' => 'ATTVeRighe', 'col_doc' => 'ATTVe_NumDocumento'],
    ['teste' => 'DDTVeTeste', 'righe' => 'DDTVeRighe', 'col_doc' => 'DDTVe_NumDocumento'],
];

foreach ($candidates as $c) {
    try {
        $teste = DB::connection('onda')->select("
            SELECT TOP 5 * FROM {$c['teste']} WHERE {$c['col_doc']} = ? OR {$c['col_doc']} LIKE ?
        ", [$ddtNum, '%' . $ddtNum]);

        if (count($teste) === 0) continue;

        echo "=== TESTA DDT (tabella {$c['teste']}) ===\n";
        foreach ($teste as $t) {
            $arr = (array) $t;
            $idDoc = $arr['ATTVe_IdDocumento'] ?? $arr['DDTVe_IdDocumento'] ?? null;
            $cliente = $arr['ATTVe_RagioneSociale'] ?? $arr['ATTVe_DescAnagrafica'] ?? '-';
            $data = $arr['ATTVe_Data'] ?? '-';
            echo "  IdDoc: $idDoc | Num: $ddtNum | Data: $data | Cliente: $cliente\n";

            if ($idDoc) {
                $righe = DB::connection('onda')->select("
                    SELECT * FROM {$c['righe']} WHERE ATTVe_IdDocumento = ? OR DDTVe_IdDocumento = ?
                ", [$idDoc, $idDoc]);

                echo "  Righe: " . count($righe) . "\n";
                foreach ($righe as $r) {
                    $ar = (array) $r;
                    $cod = $ar['ATTVe_CodiceArt'] ?? $ar['ATTVe_CodArt'] ?? '?';
                    $desc = mb_substr($ar['ATTVe_Descrizione'] ?? '-', 0, 70);
                    $qta = $ar['ATTVe_Quantita'] ?? $ar['ATTVe_Qta'] ?? 0;
                    $um = $ar['ATTVe_UM'] ?? '';
                    echo sprintf("    - cod=%s | qta=%s %s | desc=%s\n", $cod, $qta, $um, $desc);
                }
            }
        }
        exit(0);
    } catch (\Throwable $e) {
        // Tabella non esiste, prova prossima
        continue;
    }
}

echo "Nessuna tabella DDT match. Mostro schema candidato (TOP 5 colonne ATTVe%)...\n";
$cols = DB::connection('onda')->select("
    SELECT TOP 30 TABLE_NAME, COLUMN_NAME
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME LIKE 'ATTVe%' OR TABLE_NAME LIKE 'DDTVe%'
    ORDER BY TABLE_NAME, ORDINAL_POSITION
");
foreach ($cols as $c) {
    echo "  {$c->TABLE_NAME}.{$c->COLUMN_NAME}\n";
}
