<?php
// Uso: php confronta_fasi.php 0066710-26
// Confronta le fasi di una commessa tra Onda (ERP) e MES (MySQL)

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessa = $argv[1] ?? '0066710-26';
// Estrai il codice numerico per Onda (senza trattino)
$codCommessa = $commessa;

echo "========================================\n";
echo "  CONFRONTO FASI: $commessa\n";
echo "========================================\n\n";

// ===== 1. FASI DA ONDA (SQL Server) =====
echo "--- FASI IN ONDA (PRDDocFasi) ---\n";
$righeOnda = DB::connection('onda')->select("
    SELECT
        t.CodCommessa,
        p.CodArt,
        p.OC_Descrizione,
        p.QtaDaProdurre,
        f.CodFase,
        f.CodMacchina,
        f.QtaDaLavorare,
        f.CodUnMis
    FROM ATTDocTeste t
    INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
    LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
    WHERE t.CodCommessa = ?
      AND t.TipoDocumento = '2'
    ORDER BY p.OC_Descrizione, f.CodFase
", [$codCommessa]);

if (empty($righeOnda)) {
    echo "  Nessuna fase trovata in Onda per '$codCommessa'\n";
    // Prova senza trattino
    $codSenza = explode('-', $codCommessa)[0];
    echo "  Provo con '$codSenza'...\n";
    $righeOnda = DB::connection('onda')->select("
        SELECT
            t.CodCommessa,
            p.CodArt,
            p.OC_Descrizione,
            p.QtaDaProdurre,
            f.CodFase,
            f.CodMacchina,
            f.QtaDaLavorare,
            f.CodUnMis
        FROM ATTDocTeste t
        INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
        LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
        WHERE t.CodCommessa LIKE ?
          AND t.TipoDocumento = '2'
        ORDER BY p.OC_Descrizione, f.CodFase
    ", ["%$codSenza%"]);
}

$fasiOnda = [];
if (!empty($righeOnda)) {
    printf("  %-30s | %-20s | %-15s | %s\n", "Descrizione", "CodFase", "Macchina", "Qta");
    echo "  " . str_repeat('-', 90) . "\n";
    foreach ($righeOnda as $r) {
        $desc = mb_substr($r->OC_Descrizione ?? '-', 0, 30);
        printf("  %-30s | %-20s | %-15s | %s %s\n",
            $desc,
            $r->CodFase ?? '(nessuna)',
            $r->CodMacchina ?? '-',
            $r->QtaDaLavorare ?? '-',
            $r->CodUnMis ?? ''
        );
        if ($r->CodFase) {
            $key = trim($r->CodFase);
            $fasiOnda[$key] = ($fasiOnda[$key] ?? 0) + 1;
        }
    }
    echo "\n  Riepilogo fasi Onda: ";
    foreach ($fasiOnda as $fase => $count) {
        echo "$fase(x$count) ";
    }
    echo "\n";
} else {
    echo "  Nessuna fase trovata in Onda.\n";
}

// ===== 2. FASI DAL MES (MySQL) =====
echo "\n--- FASI NEL MES (ordine_fasi) ---\n";
$ordini = App\Models\Ordine::where('commessa', $commessa)
    ->with(['fasi.faseCatalogo.reparto', 'fasi.operatori'])
    ->get();

if ($ordini->isEmpty()) {
    // Prova senza zero iniziale
    $ordini = App\Models\Ordine::where('commessa', 'like', "%$commessa%")
        ->with(['fasi.faseCatalogo.reparto', 'fasi.operatori'])
        ->get();
}

$fasiMes = [];
if ($ordini->isNotEmpty()) {
    printf("  %-30s | %-20s | %-10s | %-8s | %s\n", "Descrizione", "Fase", "Reparto", "Stato", "Qta");
    echo "  " . str_repeat('-', 100) . "\n";
    foreach ($ordini as $ordine) {
        foreach ($ordine->fasi as $fase) {
            $nomeFase = $fase->fase ?? ($fase->faseCatalogo->nome ?? '-');
            $reparto = optional(optional($fase->faseCatalogo)->reparto)->nome ?? '-';
            $statoLabel = [0 => 'caricato', 1 => 'pronto', 2 => 'avviato', 3 => 'terminato', 4 => 'consegnato'];
            $desc = mb_substr($ordine->descrizione ?? '-', 0, 30);
            printf("  %-30s | %-20s | %-10s | %-8s | %s\n",
                $desc,
                $nomeFase,
                mb_substr($reparto, 0, 10),
                $statoLabel[$fase->stato] ?? $fase->stato,
                $fase->qta_fase ?? '-'
            );
            $key = trim($nomeFase);
            $fasiMes[$key] = ($fasiMes[$key] ?? 0) + 1;
        }
    }
    echo "\n  Riepilogo fasi MES: ";
    foreach ($fasiMes as $fase => $count) {
        echo "$fase(x$count) ";
    }
    echo "\n";
} else {
    echo "  Nessun ordine trovato nel MES per '$commessa'\n";
}

// ===== 3. CONFRONTO =====
echo "\n--- CONFRONTO ---\n";

// Normalizza nomi: la mappatura Onda→MES non è 1:1
// Mostriamo le differenze grezze
$soloOnda = array_diff_key($fasiOnda, $fasiMes);
$soloMes = array_diff_key($fasiMes, $fasiOnda);
$comuni = array_intersect_key($fasiOnda, $fasiMes);

if (!empty($comuni)) {
    echo "  PRESENTI IN ENTRAMBI:\n";
    foreach ($comuni as $fase => $countOnda) {
        $countMes = $fasiMes[$fase] ?? 0;
        $match = $countOnda == $countMes ? 'OK' : "DIVERSO (Onda:$countOnda vs MES:$countMes)";
        echo "    $fase  →  $match\n";
    }
}

if (!empty($soloOnda)) {
    echo "\n  SOLO IN ONDA (mancano nel MES):\n";
    foreach ($soloOnda as $fase => $count) {
        echo "    $fase (x$count)\n";
    }
}

if (!empty($soloMes)) {
    echo "\n  SOLO NEL MES (non in Onda):\n";
    foreach ($soloMes as $fase => $count) {
        echo "    $fase (x$count)\n";
    }
}

if (empty($soloOnda) && empty($soloMes) && !empty($comuni)) {
    echo "\n  ✓ Tutte le fasi corrispondono!\n";
}

echo "\n  NOTA: I nomi fasi possono differire per la rimappatura Onda→MES\n";
echo "  (es. STAMPA → STAMPAXL106, CodMacchina determina il nome MES)\n";
echo "\n";
