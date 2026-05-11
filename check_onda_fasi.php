<?php
/**
 * Verifica APPROFONDITA fasi commessa: Onda vs MES.
 * Usage: php check_onda_fasi.php 0067339-26
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$commessa = $argv[1] ?? null;
if (!$commessa) {
    echo "Usage: php check_onda_fasi.php <commessa>\n";
    exit(1);
}

echo "\n=== Commessa: {$commessa} (verifica approfondita) ===\n\n";

$onda = DB::connection('onda');

// 1) ATTDocRighe + ATTDocTeste — TUTTI i tipi documento
echo "--- 1. ATTDocRighe (TUTTI i TipoDoc) ---\n";
try {
    $rows = $onda->select("
        SELECT
            t.TipoDoc,
            t.NumDoc,
            t.DataDocumento,
            t.DataRegistrazione,
            r.CodCommessa,
            r.CodFase,
            r.Descrizione,
            r.QtaDaLavorare,
            r.UMFase,
            r.CodMacchina,
            r.IdDoc,
            r.IdRiga
        FROM ATTDocTeste t
        INNER JOIN ATTDocRighe r ON t.IdDoc = r.IdDoc
        WHERE r.CodCommessa = ?
        ORDER BY t.TipoDoc, t.DataDocumento DESC, r.CodFase
    ", [$commessa]);

    if (empty($rows)) {
        echo "  Nessuna riga in ATTDoc* per commessa {$commessa}.\n";
    } else {
        $perTipo = [];
        foreach ($rows as $r) {
            $tipo = $r->TipoDoc;
            $perTipo[$tipo][] = $r;
        }
        foreach ($perTipo as $tipo => $rs) {
            echo "  TipoDoc={$tipo} ({" . count($rs) . " righe)\n";
            foreach ($rs as $r) {
                $data = $r->DataDocumento ? date('d/m/Y', strtotime($r->DataDocumento)) : '-';
                echo sprintf(
                    "    [%s] %-30s | Qta: %-12s | NumDoc: %s | IdDoc=%d IdRiga=%d\n",
                    $data,
                    $r->CodFase,
                    $r->QtaDaLavorare,
                    $r->NumDoc ?? '-',
                    $r->IdDoc,
                    $r->IdRiga
                );
            }
        }
    }
} catch (\Exception $e) {
    echo "  ERRORE: " . $e->getMessage() . "\n";
}

// 2) PLALUX specifico — query mirata
echo "\n--- 2. Ricerca PLALUX su tutta Onda (per debug) ---\n";
try {
    $plalux = $onda->select("
        SELECT TOP 20
            t.TipoDoc,
            t.DataDocumento,
            r.CodCommessa,
            r.CodFase,
            r.QtaDaLavorare,
            r.UMFase
        FROM ATTDocRighe r
        INNER JOIN ATTDocTeste t ON t.IdDoc = r.IdDoc
        WHERE r.CodCommessa = ?
          AND (r.CodFase LIKE '%PLALUX%' OR r.CodFase LIKE '%PLASTI%' OR r.Descrizione LIKE '%plastif%')
        ORDER BY t.DataDocumento DESC
    ", [$commessa]);

    if (empty($plalux)) {
        echo "  Nessuna fase plastificazione trovata.\n";
    } else {
        foreach ($plalux as $p) {
            $data = $p->DataDocumento ? date('d/m/Y', strtotime($p->DataDocumento)) : '-';
            echo sprintf(
                "  [%s %s] %s | Qta: %s %s\n",
                $data, $p->TipoDoc, $p->CodFase, $p->QtaDaLavorare, $p->UMFase
            );
        }
    }
} catch (\Exception $e) {
    echo "  ERRORE PLALUX: " . $e->getMessage() . "\n";
}

// 3) Controlla cronologia ordini Onda (TBOrdini se esiste)
echo "\n--- 3. TBOrdini (ordine principale) ---\n";
try {
    $ordini = $onda->select("
        SELECT TOP 5
            CodCommessa, CodArt, Descrizione, QtaRichiesta,
            DataRegistrazione, DataConsegna, Cliente
        FROM TBOrdini
        WHERE CodCommessa = ?
        ORDER BY DataRegistrazione DESC
    ", [$commessa]);

    if (empty($ordini)) {
        echo "  Nessun record TBOrdini per {$commessa}.\n";
    } else {
        foreach ($ordini as $o) {
            $data = $o->DataRegistrazione ? date('d/m/Y', strtotime($o->DataRegistrazione)) : '-';
            echo sprintf(
                "  [%s] CodArt=%s Qta=%s Cliente=%s\n",
                $data, $o->CodArt ?? '-', $o->QtaRichiesta ?? '-', $o->Cliente ?? '-'
            );
            echo "    Desc: " . substr($o->Descrizione ?? '', 0, 100) . "\n";
        }
    }
} catch (\Exception $e) {
    echo "  TBOrdini non accessibile: " . $e->getMessage() . "\n";
}

// 4) MES locale
echo "\n--- 4. MES (DB locale) — fasi correnti + soft-deleted ---\n";
$mesFasi = DB::table('ordini')
    ->join('ordine_fasi', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->where('ordini.commessa', $commessa)
    ->select(
        'ordine_fasi.id',
        'ordine_fasi.fase',
        'ordine_fasi.stato',
        'ordine_fasi.qta_fase',
        'ordine_fasi.qta_prod',
        'ordine_fasi.deleted_at',
        'ordine_fasi.created_at',
        'ordine_fasi.updated_at'
    )
    ->orderBy('ordine_fasi.priorita', 'desc')
    ->get();

foreach ($mesFasi as $f) {
    $del = $f->deleted_at ? ' [DELETED ' . substr($f->deleted_at, 0, 10) . ']' : '';
    echo sprintf(
        "  #%d %-28s | Stato=%-10s | Qta_fase=%-8s | Created=%s%s\n",
        $f->id,
        $f->fase,
        $f->stato,
        $f->qta_fase,
        substr($f->created_at, 0, 10),
        $del
    );
}

echo "\n";
