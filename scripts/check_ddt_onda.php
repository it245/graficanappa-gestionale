<?php
/**
 * Query Onda DDT vendita: testa + righe reali con cod_art e qta.
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
$ddtNum = ltrim($ddt, '0');

echo "=== TESTA ATTDocTeste per DDT $ddt (filtro anno 2024+) ===\n";
$teste = DB::connection('onda')->select("
    SELECT IdDoc, TipoDocumento, NumeroDocumento, DataDocumento, IdAnagrafica
    FROM ATTDocTeste
    WHERE (NumeroDocumento = ? OR NumeroDocumento = ?)
      AND DataDocumento >= '2024-01-01'
    ORDER BY DataDocumento DESC
", [$ddtNum, $ddt]);

if (empty($teste)) {
    echo "Nessuna testa trovata. Provo wildcard...\n";
    $teste = DB::connection('onda')->select("
        SELECT TOP 10 IdDoc, TipoDocumento, NumeroDocumento, DataDocumento
        FROM ATTDocTeste
        WHERE NumeroDocumento LIKE ?
    ", ['%' . $ddtNum]);
}

if (empty($teste)) {
    echo "Nessun match.\n";
    exit(1);
}

foreach ($teste as $t) {
    echo "  IdDoc={$t->IdDoc} | Tipo={$t->TipoDocumento} | Num={$t->NumeroDocumento} | Data={$t->DataDocumento} | IdAnag={$t->IdAnagrafica}\n";

    // Righe DDT
    echo "\n  === RIGHE ATTDocRighe (IdDoc {$t->IdDoc}) ===\n";
    $righe = DB::connection('onda')->select("
        SELECT IdDoc, IdRiga, NrRiga, TipoRiga, CodArt, Descrizione, Qta, QtaConsegnata, CodUnMis
        FROM ATTDocRighe
        WHERE IdDoc = ?
        ORDER BY NrRiga
    ", [$t->IdDoc]);

    if (empty($righe)) {
        echo "  (nessuna riga)\n";
        continue;
    }

    foreach ($righe as $r) {
        echo sprintf("  riga#%d | cod=%s | qta=%s %s | %s\n",
            $r->NrRiga,
            $r->CodArt ?: '-',
            number_format((float) $r->Qta, 2, ',', '.'),
            $r->CodUnMis ?: '',
            mb_substr((string) $r->Descrizione, 0, 80)
        );
    }
}
