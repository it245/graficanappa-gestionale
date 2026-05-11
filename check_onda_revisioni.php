<?php
/**
 * Verifica revisioni PRDDoc per commessa.
 * Usage: php check_onda_revisioni.php 0067339-26
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = $argv[1] ?? '0067339-26';
$onda = DB::connection('onda');

echo "\n=== Commessa: {$commessa} — revisioni Onda ===\n\n";

// 1) PRDDocTeste: quanti documenti produzione esistono?
echo "--- 1. PRDDocTeste (testate produzione) ---\n";
try {
    $teste = $onda->select("
        SELECT IdDoc, DataDocumento, DataRegistrazione, CodCommessa, TipoDocumento
        FROM PRDDocTeste
        WHERE CodCommessa = ?
        ORDER BY DataDocumento DESC, IdDoc DESC
    ", [$commessa]);

    if (empty($teste)) {
        echo "  Nessun PRDDocTeste.\n";
    } else {
        foreach ($teste as $t) {
            $data = $t->DataDocumento ? date('d/m/Y H:i', strtotime($t->DataDocumento)) : '-';
            $reg = $t->DataRegistrazione ? date('d/m/Y H:i', strtotime($t->DataRegistrazione)) : '-';
            echo sprintf(
                "  IdDoc=%-8s Data=%-16s Reg=%-16s TipoDoc=%s\n",
                $t->IdDoc, $data, $reg, $t->TipoDocumento ?? '-'
            );
        }
        echo "  Totale: " . count($teste) . "\n";
    }
} catch (\Exception $e) {
    echo "  ERRORE: " . $e->getMessage() . "\n";
}

// 2) PRDDocFasi per OGNI IdDoc trovato
echo "\n--- 2. PRDDocFasi PER OGNI IdDoc (vedi diff revisioni) ---\n";
if (!empty($teste)) {
    foreach ($teste as $t) {
        echo "\n  >> IdDoc={$t->IdDoc} (Data " . ($t->DataDocumento ? date('d/m/Y', strtotime($t->DataDocumento)) : '-') . "):\n";
        try {
            $fasi = $onda->select("
                SELECT CodFase, CodMacchina, QtaDaLavorare, CodUnMis
                FROM PRDDocFasi
                WHERE IdDoc = ?
                ORDER BY CodFase
            ", [$t->IdDoc]);
            if (empty($fasi)) {
                echo "       Nessuna fase.\n";
            } else {
                foreach ($fasi as $f) {
                    echo sprintf("       %-30s Qta=%-10s %s\n",
                        $f->CodFase, $f->QtaDaLavorare, $f->CodUnMis ?? '');
                }
            }
        } catch (\Exception $e) {
            echo "       ERRORE: " . $e->getMessage() . "\n";
        }
    }
}

// 3) Quale doc MES sync userebbe (TipoDocumento=2 + DataRegistrazione >= filtro)
echo "\n--- 3. Doc che OndaSyncService VEDE (TipoDocumento=2) ---\n";
try {
    $sync = $onda->select("
        SELECT
            t.IdDoc AS AttIdDoc,
            t.DataRegistrazione,
            t.TipoDocumento,
            p.IdDoc AS PrdIdDoc,
            p.DataDocumento AS PrdData
        FROM ATTDocTeste t
        INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
        WHERE t.CodCommessa = ?
          AND t.TipoDocumento = '2'
        ORDER BY p.DataDocumento DESC
    ", [$commessa]);

    if (empty($sync)) {
        echo "  Nessun match (TipoDocumento=2 non trovato).\n";
    } else {
        foreach ($sync as $s) {
            $reg = $s->DataRegistrazione ? date('d/m/Y H:i', strtotime($s->DataRegistrazione)) : '-';
            $prd = $s->PrdData ? date('d/m/Y H:i', strtotime($s->PrdData)) : '-';
            echo sprintf(
                "  ATT=%s RegATT=%s | PRD=%s DataPRD=%s\n",
                $s->AttIdDoc, $reg, $s->PrdIdDoc, $prd
            );
        }
    }
} catch (\Exception $e) {
    echo "  ERRORE: " . $e->getMessage() . "\n";
}

echo "\n";
