<?php
// Controlla le commesse anomale nel dettaglio
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commesse = ['0066648-26', '0066623-26', '0066654-26', '0066649-26'];

foreach ($commesse as $commessa) {
    echo "\n========================================\n";
    echo "  $commessa\n";
    echo "========================================\n";

    // Onda
    echo "\n  ONDA:\n";
    $righe = DB::connection('onda')->select("
        SELECT p.CodArt, f.CodFase, f.CodMacchina, f.QtaDaLavorare
        FROM ATTDocTeste t
        INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
        LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
        WHERE t.CodCommessa = ? AND t.TipoDocumento = '2'
        ORDER BY f.CodFase
    ", [$commessa]);

    if (empty($righe)) {
        // Prova query diretta PRDDocTeste
        $righe = DB::connection('onda')->select("
            SELECT p.CodArt, f.CodFase, f.CodMacchina, f.QtaDaLavorare
            FROM PRDDocTeste p
            LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
            WHERE p.CodCommessa = ?
            ORDER BY f.CodFase
        ", [$commessa]);
        if (!empty($righe)) echo "  (trovata via PRDDocTeste, non in ATTDocTeste)\n";
    }

    if (empty($righe)) {
        echo "  Non trovata in Onda\n";
    } else {
        printf("    %-20s | %-20s | %-15s | %s\n", "CodArt", "CodFase", "Macchina", "Qta");
        echo "    " . str_repeat('-', 70) . "\n";
        foreach ($righe as $r) {
            printf("    %-20s | %-20s | %-15s | %s\n",
                mb_substr($r->CodArt ?? '', 0, 20),
                $r->CodFase ?? '(nessuna)',
                $r->CodMacchina ?? '-',
                $r->QtaDaLavorare ?? '-'
            );
        }
    }

    // MES
    echo "\n  MES:\n";
    $ordini = App\Models\Ordine::where('commessa', $commessa)
        ->with(['fasi.faseCatalogo.reparto'])
        ->get();

    if ($ordini->isEmpty()) {
        echo "  Non trovata nel MES\n";
    } else {
        printf("    %-20s | %-15s | %-10s | %-8s | %s\n", "Fase", "Reparto", "Stato", "Esterno", "Qta");
        echo "    " . str_repeat('-', 80) . "\n";
        foreach ($ordini as $ordine) {
            foreach ($ordine->fasi as $fase) {
                $reparto = optional(optional($fase->faseCatalogo)->reparto)->nome ?? '-';
                $statoLabel = [0=>'caricato',1=>'pronto',2=>'avviato',3=>'terminato',4=>'consegnato'];
                printf("    %-20s | %-15s | %-10s | %-8s | %s\n",
                    $fase->fase,
                    mb_substr($reparto, 0, 15),
                    $statoLabel[$fase->stato] ?? $fase->stato,
                    $fase->esterno ? 'SI' : 'no',
                    $fase->qta_fase ?? '-'
                );
            }
        }
    }
}

echo "\n";
