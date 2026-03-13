<?php
// Uso: php cerca_fase_profonda.php 0066731-26 [TAGLIACARTE]
// Cerca una fase in TUTTE le tabelle Onda rilevanti

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessa = $argv[1] ?? '0066731-26';
$cercaFase = strtoupper($argv[2] ?? 'TAGLIACARTE');

echo "========================================\n";
echo "  RICERCA APPROFONDITA\n";
echo "  Commessa: $commessa | Fase: $cercaFase\n";
echo "========================================\n\n";

function searchTable($table, $where, $params, $cercaFase) {
    try {
        $rows = DB::connection('onda')->select("SELECT TOP 50 * FROM $table WHERE $where", $params);
        echo "  $table: " . count($rows) . " righe\n";
        $found = false;
        foreach ($rows as $r) {
            $json = strtoupper(json_encode($r));
            $hasFase = strpos($json, $cercaFase) !== false;
            $hasTaglia = strpos($json, 'TAGLIA') !== false;
            if ($hasFase || $hasTaglia) {
                $found = true;
                $cols = [];
                foreach ($r as $k => $v) {
                    if ($v !== null && $v !== '' && $v !== 0 && $v !== '0' && $v !== '.000000' && $v !== '.0')
                        $cols[] = "$k=$v";
                }
                echo "    >>> " . implode(' | ', $cols) . "\n";
            }
        }
        if (!$found && count($rows) > 0) {
            // Mostra prima riga come esempio
            $first = (array)$rows[0];
            $cols = [];
            foreach ($first as $k => $v) {
                if ($v !== null && $v !== '' && $v !== 0) $cols[] = "$k=" . mb_substr((string)$v, 0, 30);
            }
            echo "    (nessun match '$cercaFase'. Esempio: " . implode(' | ', array_slice($cols, 0, 6)) . ")\n";
        }
        return $rows;
    } catch (\Exception $e) {
        echo "  $table: " . mb_substr($e->getMessage(), 0, 80) . "\n";
        return [];
    }
}

// Trova ID documenti
$docs = DB::connection('onda')->select("SELECT IdDoc, CodArt FROM PRDDocTeste WHERE CodCommessa = ?", [$commessa]);
$prdIdDocs = array_map(fn($d) => $d->IdDoc, $docs);
$codArt = $docs[0]->CodArt ?? null;

$att = DB::connection('onda')->select("SELECT IdDoc FROM ATTDocTeste WHERE CodCommessa = ?", [$commessa]);
$attIdDocs = array_map(fn($a) => $a->IdDoc, $att);

// Trova righe ATT
$attRigheIds = [];
try {
    $attRighe = DB::connection('onda')->select("SELECT IdDoc, IdRiga FROM ATTDocRighe WHERE IdDoc IN (" . implode(',', $attIdDocs ?: [0]) . ")");
    $attRigheIds = array_map(fn($r) => ['doc' => $r->IdDoc, 'riga' => $r->IdRiga], $attRighe);
} catch (\Exception $e) {}

echo "PRD IdDoc: " . implode(', ', $prdIdDocs) . " | ATT IdDoc: " . implode(', ', $attIdDocs) . " | CodArt: $codArt\n\n";

echo "=== 1. PRDDocFasi (ciclo produttivo) ===\n";
searchTable('PRDDocFasi', 'IdDoc IN (' . implode(',', $prdIdDocs ?: [0]) . ')', [], $cercaFase);

echo "\n=== 2. PRDDistintaFasi (distinta base) ===\n";
if ($codArt) {
    searchTable('PRDDistintaFasi', "CodPadre = ?", [$codArt], $cercaFase);
}

echo "\n=== 3. PRDCicliRighe (cicli standard) ===\n";
if ($codArt) {
    searchTable('PRDCicliRighe', "CodArt = ?", [$codArt], $cercaFase);
}

echo "\n=== 4. OC_ATTDocLavorazioni ===\n";
searchTable('OC_ATTDocLavorazioni', 'IdDoc IN (' . implode(',', $attIdDocs ?: [0]) . ')', [], $cercaFase);

echo "\n=== 5. OC_ATTDocCartotecnicaLav ===\n";
searchTable('OC_ATTDocCartotecnicaLav', 'IdDoc IN (' . implode(',', $attIdDocs ?: [0]) . ')', [], $cercaFase);

echo "\n=== 6. OC_ATTDocCartotecnica ===\n";
searchTable('OC_ATTDocCartotecnica', 'IdDoc IN (' . implode(',', $attIdDocs ?: [0]) . ')', [], $cercaFase);

echo "\n=== 7. ATTDocRighe ===\n";
searchTable('ATTDocRighe', 'IdDoc IN (' . implode(',', $attIdDocs ?: [0]) . ')', [], $cercaFase);

echo "\n=== 8. OC_ATTDocRigheSegnature ===\n";
foreach ($attRigheIds as $r) {
    searchTable('OC_ATTDocRigheSegnature', 'IdDoc = ? AND IdRiga = ?', [$r['doc'], $r['riga']], $cercaFase);
}

echo "\n=== 9. OC_ATTDocRigheExt ===\n";
foreach ($attRigheIds as $r) {
    searchTable('OC_ATTDocRigheExt', 'IdDoc = ? AND IdRiga = ?', [$r['doc'], $r['riga']], $cercaFase);
}

echo "\n=== 10. OC_ATTDocRigheExt0 ===\n";
foreach (array_slice($attRigheIds, 0, 3) as $r) {
    searchTable('OC_ATTDocRigheExt0', 'IdDoc = ? AND IdRiga = ?', [$r['doc'], $r['riga']], $cercaFase);
}

echo "\n=== 11. OC_PRDDocAttrezzature ===\n";
searchTable('OC_PRDDocAttrezzature', 'IdDoc IN (' . implode(',', $prdIdDocs ?: [0]) . ')', [], $cercaFase);

echo "\n=== 12. Commento produzione ATTDocTeste ===\n";
$comm = DB::connection('onda')->select("SELECT OC_CommentoProduz FROM ATTDocTeste WHERE CodCommessa = ?", [$commessa]);
foreach ($comm as $c) {
    $testo = $c->OC_CommentoProduz ?? '(vuoto)';
    $mark = stripos($testo, $cercaFase) !== false ? ' <<<< TROVATO!' : '';
    echo "  $testo$mark\n";
}

echo "\n=== 13. Ricerca GLOBALE: tabelle con '$cercaFase' per la commessa ===\n";
// Cerca in tutte le tabelle che contengono IdDoc
$tabelleExtra = [
    'OC_ATTDocRigheTirature',
    'OC_ATTDocAgglomerato',
    'OC_ATTDocAgglomeratoDet',
    'NVP_PRDDistintaSupporti',
    'OC_PRDDistintaMacro',
    'OC_ATTDocRigheSegnatureLibere',
    'OC_ATTDocCartotecnicaAttr',
    'OC_ATTDocCartotecnicaMat',
    'OC_ATTDocRigheInk',
    'OC_ATTDocRigheLingue',
];
foreach ($tabelleExtra as $tab) {
    // Prova con IdDoc ATT
    searchTable($tab, 'IdDoc IN (' . implode(',', $attIdDocs ?: [0]) . ')', [], $cercaFase);
}

echo "\n========================================\n";
echo "  RISULTATO FINALE\n";
echo "========================================\n";
echo "  Se non è stato trovato '$cercaFase' in nessuna tabella,\n";
echo "  la fase NON esiste in Onda per questa commessa.\n";
echo "  Chiedere ai colleghi in quale schermata la vedono.\n\n";
