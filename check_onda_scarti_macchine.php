<?php
/**
 * Verifica su quali macchine Onda popola OC_TotScarti > 0.
 * Uso: php check_onda_scarti_macchine.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Macchine con OC_TotScarti > 0 (da OC_ATTDocRigheExt) ===\n";
$rows = DB::connection('onda')->select("
    SELECT
        ext.OC_CodMacchina AS macchina,
        COUNT(*) AS n_righe,
        MIN(ext.OC_TotScarti) AS min_scarti,
        AVG(CAST(ext.OC_TotScarti AS float)) AS media_scarti,
        MAX(ext.OC_TotScarti) AS max_scarti,
        SUM(ext.OC_TotScarti) AS tot_scarti
    FROM OC_ATTDocRigheExt ext
    INNER JOIN ATTDocTeste t ON ext.OC_IdDoc = t.IdDoc
    WHERE ext.OC_TotScarti > 0
      AND t.TipoDocumento = '2'
      AND t.DataRegistrazione >= CAST('20260101' AS datetime)
      AND ext.OC_CodMacchina IS NOT NULL AND ext.OC_CodMacchina != ''
    GROUP BY ext.OC_CodMacchina
    ORDER BY n_righe DESC
");

printf("  %-25s %-10s %-10s %-12s %-10s %-12s\n", 'MACCHINA', 'N.RIGHE', 'MIN', 'MEDIA', 'MAX', 'TOTALE');
echo str_repeat('-', 80) . "\n";
foreach ($rows as $r) {
    printf("  %-25s %-10d %-10d %-12.1f %-10d %-12d\n",
        $r->macchina, $r->n_righe, $r->min_scarti, $r->media_scarti, $r->max_scarti, $r->tot_scarti);
}

echo "\n=== Cross-ref con fasi MES (PRDDocFasi.CodFase mappate) ===\n";
$fasi = DB::connection('onda')->select("
    SELECT DISTINCT
        ext.OC_CodMacchina AS macchina,
        f.CodFase AS cod_fase,
        COUNT(*) AS n
    FROM OC_ATTDocRigheExt ext
    INNER JOIN ATTDocTeste t ON ext.OC_IdDoc = t.IdDoc
    INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
    INNER JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc AND f.CodMacchina = ext.OC_CodMacchina
    WHERE ext.OC_TotScarti > 0
      AND t.TipoDocumento = '2'
      AND t.DataRegistrazione >= CAST('20260101' AS datetime)
    GROUP BY ext.OC_CodMacchina, f.CodFase
    ORDER BY ext.OC_CodMacchina, n DESC
");

printf("  %-25s %-30s %-8s\n", 'MACCHINA ONDA', 'CodFase PRDDocFasi', 'N.USI');
echo str_repeat('-', 70) . "\n";
foreach ($fasi as $r) {
    printf("  %-25s %-30s %-8d\n", $r->macchina, $r->cod_fase, $r->n);
}

echo "\n=== Esempi righe con scarti, commesse recenti ===\n";
$esempi = DB::connection('onda')->select("
    SELECT TOP 20
        t.CodCommessa,
        f.CodFase,
        ext.OC_CodMacchina,
        ext.OC_TotScarti,
        ext.OC_ScartoFogli,
        ext.OC_ScartoTirature
    FROM OC_ATTDocRigheExt ext
    INNER JOIN ATTDocTeste t ON ext.OC_IdDoc = t.IdDoc
    LEFT JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
    LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc AND f.CodMacchina = ext.OC_CodMacchina
    WHERE ext.OC_TotScarti > 0
      AND t.DataRegistrazione >= CAST('20260301' AS datetime)
    ORDER BY t.DataRegistrazione DESC
");

printf("  %-15s %-25s %-15s %-8s %-8s %-8s\n", 'COMMESSA', 'CodFase', 'CodMacchina', 'Tot', 'Fogli', 'Tiratur');
echo str_repeat('-', 90) . "\n";
foreach ($esempi as $r) {
    printf("  %-15s %-25s %-15s %-8d %-8d %-8d\n",
        $r->CodCommessa, $r->CodFase ?? '-', $r->OC_CodMacchina,
        $r->OC_TotScarti, $r->OC_ScartoFogli ?? 0, $r->OC_ScartoTirature ?? 0);
}
