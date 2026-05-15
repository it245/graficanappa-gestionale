<?php
/**
 * Esplora schema COGScadenze + sample data per cashflow forecast dashboard.
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Schema COGScadenze ===\n";
$cols = DB::connection('onda')->select("
    SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'COGScadenze'
    ORDER BY ORDINAL_POSITION
");
foreach ($cols as $c) {
    $len = $c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : '';
    echo "  {$c->COLUMN_NAME} - {$c->DATA_TYPE}{$len} ".($c->IS_NULLABLE === 'YES' ? '?' : '')."\n";
}

echo "\n=== Top 3 record campione ===\n";
$rows = DB::connection('onda')->select("SELECT TOP 3 * FROM COGScadenze ORDER BY DataScadenza DESC");
foreach ($rows as $r) {
    print_r((array)$r);
    echo "---\n";
}

echo "\n=== Conteggi per stato ===\n";
$counts = DB::connection('onda')->select("
    SELECT
        COUNT(*) AS totale,
        SUM(CASE WHEN DataScadenza > GETDATE() THEN 1 ELSE 0 END) AS future,
        SUM(CASE WHEN DataScadenza <= GETDATE() THEN 1 ELSE 0 END) AS passate,
        SUM(CASE WHEN DataScadenza BETWEEN GETDATE() AND DATEADD(DAY, 30, GETDATE()) THEN 1 ELSE 0 END) AS prossimi_30gg
    FROM COGScadenze
    WHERE DataScadenza IS NOT NULL
");
print_r((array)$counts[0]);

echo "\n=== Prossime 10 scadenze 30gg APERTE (non pareggiate, non disabilitate) ===\n";
$next = DB::connection('onda')->select("
    SELECT TOP 10
        s.DataScadenza, s.ImportoDare, s.ImportoAvere,
        s.TipoAnagrafica, s.NumeroDocumento, s.Annotazioni,
        a.RagioneSociale
    FROM COGScadenze s
    LEFT JOIN STDAnagrafiche a ON a.IdAnagrafica = s.IdAnagrafica
    WHERE s.DataScadenza BETWEEN GETDATE() AND DATEADD(DAY, 30, GETDATE())
      AND s.Disabilitata = 0
      AND (s.Pareggiata = 0 OR s.Pareggiata IS NULL)
    ORDER BY s.DataScadenza ASC
");
foreach ($next as $r) {
    $tipo = $r->TipoAnagrafica == 1 ? 'CLIENTE (incasso)' : ($r->TipoAnagrafica == 2 ? 'FORNITORE (pago)' : '?');
    $imp = $r->TipoAnagrafica == 1 ? $r->ImportoDare : $r->ImportoAvere;
    echo "  {$r->DataScadenza} | €" . number_format($imp ?: 0, 2, ',', '.') . " | $tipo | " . ($r->RagioneSociale ?: '(?)') . " | doc {$r->NumeroDocumento}\n";
}

echo "\n=== Saldo previsto 30/60/90 gg ===\n";
$saldi = DB::connection('onda')->select("
    SELECT
        SUM(CASE WHEN DataScadenza BETWEEN GETDATE() AND DATEADD(DAY, 30, GETDATE())
                  AND TipoAnagrafica = 1 THEN ImportoDare ELSE 0 END) AS incassi_30,
        SUM(CASE WHEN DataScadenza BETWEEN GETDATE() AND DATEADD(DAY, 30, GETDATE())
                  AND TipoAnagrafica = 2 THEN ImportoAvere ELSE 0 END) AS pagamenti_30,
        SUM(CASE WHEN DataScadenza BETWEEN GETDATE() AND DATEADD(DAY, 60, GETDATE())
                  AND TipoAnagrafica = 1 THEN ImportoDare ELSE 0 END) AS incassi_60,
        SUM(CASE WHEN DataScadenza BETWEEN GETDATE() AND DATEADD(DAY, 60, GETDATE())
                  AND TipoAnagrafica = 2 THEN ImportoAvere ELSE 0 END) AS pagamenti_60,
        SUM(CASE WHEN DataScadenza BETWEEN GETDATE() AND DATEADD(DAY, 90, GETDATE())
                  AND TipoAnagrafica = 1 THEN ImportoDare ELSE 0 END) AS incassi_90,
        SUM(CASE WHEN DataScadenza BETWEEN GETDATE() AND DATEADD(DAY, 90, GETDATE())
                  AND TipoAnagrafica = 2 THEN ImportoAvere ELSE 0 END) AS pagamenti_90
    FROM COGScadenze
    WHERE Disabilitata = 0 AND (Pareggiata = 0 OR Pareggiata IS NULL)
");
$s = $saldi[0];
$fmt = fn($n) => '€' . number_format($n ?: 0, 2, ',', '.');
echo "  30 giorni: incassi {$fmt($s->incassi_30)} | pagamenti {$fmt($s->pagamenti_30)} | netto {$fmt($s->incassi_30 - $s->pagamenti_30)}\n";
echo "  60 giorni: incassi {$fmt($s->incassi_60)} | pagamenti {$fmt($s->pagamenti_60)} | netto {$fmt($s->incassi_60 - $s->pagamenti_60)}\n";
echo "  90 giorni: incassi {$fmt($s->incassi_90)} | pagamenti {$fmt($s->pagamenti_90)} | netto {$fmt($s->incassi_90 - $s->pagamenti_90)}\n";
