<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use Illuminate\Support\Facades\DB;

echo "=== FIX + VERIFICA ALLINEAMENTO ONDA ↔ MES ===" . PHP_EOL;
echo "Data: " . date('d/m/Y H:i') . PHP_EOL . PHP_EOL;

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

$fixati = 0;
$errori = 0;
$ok = 0;

foreach ($commesse as $commessa) {
    $fasiOnda = DB::connection('onda')->select("
        SELECT f.CodFase, f.TipoRiga
        FROM PRDDocFasi f
        JOIN PRDDocTeste p ON f.IdDoc = p.IdDoc
        WHERE p.CodCommessa = ?
    ", [$commessa]);

    $problemi = [];

    foreach ($fasiOnda as $fo) {
        $nome = $fo->CodFase;
        $tipoRiga = (int)$fo->TipoRiga;
        $deveEsterno = ($tipoRiga === 2);

        if ($nome === 'STAMPA' || $nome === 'BRT1') continue;
        if (str_starts_with($nome, 'STAMPAXL106') || $nome === 'STAMPAINDIGO' || $nome === 'STAMPAINDIGOBN') continue;

        // Cerca nel MES
        $mesFase = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
            ->where('fase', $nome)
            ->where('stato', '<', 4)
            ->first();

        if (!$mesFase) {
            $problemi[] = "MANCA: {$nome} (TipoRiga={$tipoRiga})";
            continue;
        }

        // Fix esterno se sbagliato
        $inviatoManuale = !empty($mesFase->ddt_fornitore_id) || ($mesFase->note && preg_match('/Inviato a:/i', $mesFase->note));

        if ($deveEsterno && !$mesFase->esterno && !$inviatoManuale) {
            $mesFase->esterno = 1;
            $mesFase->save();
            echo "FIX ESTERNO: {$commessa} | {$nome} → esterno:SI" . PHP_EOL;
            $fixati++;
        } elseif (!$deveEsterno && $mesFase->esterno && !$inviatoManuale) {
            $mesFase->esterno = 0;
            $mesFase->save();
            echo "FIX INTERNO: {$commessa} | {$nome} → esterno:NO" . PHP_EOL;
            $fixati++;
        }

        // Ri-verifica
        $mesFase->refresh();
        $mesEsterno = (bool)$mesFase->esterno;
        if ($deveEsterno && !$mesEsterno && !$inviatoManuale) {
            $problemi[] = "ANCORA SBAGLIATO: {$nome} | Onda=EST(2) MES=int";
        } elseif (!$deveEsterno && $mesEsterno && !$inviatoManuale) {
            $problemi[] = "ANCORA SBAGLIATO: {$nome} | Onda=INT(1) MES=est";
        }
    }

    if (empty($problemi)) {
        $ok++;
    } else {
        echo "  ❌ {$commessa}:" . PHP_EOL;
        foreach ($problemi as $p) {
            echo "      {$p}" . PHP_EOL;
        }
        $errori++;
    }
}

echo PHP_EOL . "=== RISULTATO ===" . PHP_EOL;
echo "Commesse: " . count($commesse) . PHP_EOL;
echo "OK: {$ok}" . PHP_EOL;
echo "Con problemi residui: {$errori}" . PHP_EOL;
echo "Fasi fixate ora: {$fixati}" . PHP_EOL;

if ($errori === 0) {
    echo PHP_EOL . "TUTTO ALLINEATO AL 100%" . PHP_EOL;
}
echo "DONE" . PHP_EOL;
