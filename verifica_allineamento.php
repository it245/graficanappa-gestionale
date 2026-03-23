<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICA ALLINEAMENTO ONDA ↔ MES ===" . PHP_EOL;
echo "Data: " . date('d/m/Y H:i') . PHP_EOL . PHP_EOL;

// Commesse che avevano fasi rinominate
$commesse = [
    '0066470-26','0066508-26','0066509-26','0066510-26','0066512-26','0066513-26',
    '0066826-26','0066839-26','0066816-26','0066672-26','0066507-26','0066514-26',
    '0066515-26','0066566-26','0066632-26','0066692-26','0066797-26','0066801-26',
    '0066819-26','0066818-26','0066622-26','0066798-26','0066783-26','0066802-26',
    '0066822-26','0066878-26','0066720-26','0066793-26','0066717-26','0066722-26',
    '0066787-26','0066856-26','0066434-26','0066749-26','0066804-26','0066800-26',
    '0066676-26'
];

$commesse = array_unique($commesse);
sort($commesse);

$errori = 0;
$ok = 0;

foreach ($commesse as $commessa) {
    // Fasi Onda
    $fasiOnda = DB::connection('onda')->select("
        SELECT f.CodFase, f.TipoRiga, f.QtaDaLavorare
        FROM PRDDocFasi f
        JOIN PRDDocTeste p ON f.IdDoc = p.IdDoc
        WHERE p.CodCommessa = ?
        ORDER BY f.CodFase
    ", [$commessa]);

    // Fasi MES
    $fasiMes = DB::table('ordine_fasi')
        ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
        ->where('ordini.commessa', $commessa)
        ->where('ordine_fasi.stato', '<', 4)
        ->select('ordine_fasi.fase', 'ordine_fasi.esterno', 'ordine_fasi.id')
        ->get();

    $problemi = [];

    // Per ogni fase Onda, verifica nel MES
    foreach ($fasiOnda as $fo) {
        $nome = $fo->CodFase;
        $tipoRiga = (int)$fo->TipoRiga;
        $deveEsterno = ($tipoRiga === 2);

        // Cerca nel MES (escludi STAMPA generica che viene rimappata)
        if ($nome === 'STAMPA') continue;

        $mesFase = $fasiMes->firstWhere('fase', $nome);

        if (!$mesFase) {
            // Potrebbe essere rimappata (STAMPAXL106.1, STAMPAINDIGO, ecc.)
            if (str_starts_with($nome, 'STAMPAXL106') || $nome === 'STAMPAINDIGO' || $nome === 'STAMPAINDIGOBN') continue;
            // BRT dedup per commessa — potrebbe essere su altro ordine
            if ($nome === 'BRT1') continue;

            $problemi[] = "MANCA nel MES: {$nome} (Onda TipoRiga={$tipoRiga})";
            continue;
        }

        // Verifica esterno
        $mesEsterno = (bool)$mesFase->esterno;
        if ($deveEsterno && !$mesEsterno) {
            $problemi[] = "ESTERNO SBAGLIATO: {$nome} | Onda=ESTERNA(2) MES=interno";
        } elseif (!$deveEsterno && $mesEsterno) {
            // Potrebbe essere stata inviata manualmente — check nota
            $fase = \App\Models\OrdineFase::find($mesFase->id);
            $inviatoManuale = !empty($fase->ddt_fornitore_id) || ($fase->note && preg_match('/Inviato a:/i', $fase->note));
            if (!$inviatoManuale) {
                $problemi[] = "ESTERNO SBAGLIATO: {$nome} | Onda=INTERNA(1) MES=esterno (non inviata manualmente)";
            }
        }
    }

    if (empty($problemi)) {
        $ok++;
    } else {
        echo "❌ {$commessa}:" . PHP_EOL;
        foreach ($problemi as $p) {
            echo "    {$p}" . PHP_EOL;
        }
        $errori += count($problemi);
    }
}

echo PHP_EOL . "=== RISULTATO ===" . PHP_EOL;
echo "Commesse verificate: " . count($commesse) . PHP_EOL;
echo "Commesse OK: {$ok}" . PHP_EOL;
echo "Commesse con problemi: " . (count($commesse) - $ok) . PHP_EOL;
echo "Errori totali: {$errori}" . PHP_EOL;

if ($errori === 0) {
    echo PHP_EOL . "✅ TUTTO ALLINEATO AL 100%" . PHP_EOL;
}
echo PHP_EOL . "DONE" . PHP_EOL;
