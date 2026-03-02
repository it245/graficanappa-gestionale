<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessa = $argv[1] ?? '0066621-26';

echo "=== Dati Onda per commessa $commessa ===\n\n";

try {
    $righe = \Illuminate\Support\Facades\DB::connection('onda')->select("
        SELECT
            t.CodCommessa,
            t.OC_Descrizione,
            carta.CodArt,
            f.CodFase,
            f.CodMacchina,
            f.QtaDaLavorare,
            f.CodUnMis AS UMFase,
            t.QtaDaProdurre
        FROM ATTDocTeste t
        INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
        LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
        OUTER APPLY (
            SELECT TOP 1 r.CodArt, r.Descrizione, r.Qta, r.CodUnMis
            FROM PRDDocRighe r WHERE r.IdDoc = p.IdDoc
            ORDER BY r.Sequenza
        ) carta
        WHERE t.CodCommessa = ?
    ", [$commessa]);

    if (empty($righe)) {
        echo "Nessuna riga trovata in Onda per $commessa\n";
        exit(0);
    }

    echo sprintf("%-20s %-18s %-15s %-10s %-10s\n", 'CodFase', 'CodMacchina', 'QtaDaLavorare', 'UMFase', 'QtaDaProdurre');
    echo str_repeat('-', 80) . "\n";

    foreach ($righe as $r) {
        echo sprintf("%-20s %-18s %-15s %-10s %-10s\n",
            $r->CodFase ?? '-',
            $r->CodMacchina ?? '-',
            $r->QtaDaLavorare ?? '-',
            $r->UMFase ?? '-',
            $r->QtaDaProdurre ?? '-'
        );
    }

    // Mostra anche qta_fase salvata nel nostro DB
    echo "\n=== Confronto con DB locale ===\n";
    $ordine = \App\Models\Ordine::where('commessa', $commessa)->first();
    if ($ordine) {
        $fasi = \App\Models\OrdineFase::where('ordine_id', $ordine->id)
            ->with('faseCatalogo')
            ->orderBy('priorita')
            ->get();
        echo sprintf("%-25s %-10s %-12s %-10s\n", 'Fase', 'qta_fase', 'qta_prod', 'stato');
        echo str_repeat('-', 60) . "\n";
        foreach ($fasi as $f) {
            echo sprintf("%-25s %-10s %-12s %-10s\n",
                $f->faseCatalogo->nome ?? $f->fase,
                $f->qta_fase ?? '-',
                $f->qta_prod ?? '-',
                $f->stato
            );
        }
    }
} catch (\Exception $e) {
    echo "ERRORE: " . $e->getMessage() . "\n";
}
