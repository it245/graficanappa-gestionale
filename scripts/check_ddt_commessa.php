<?php
/**
 * Mostra articoli + DDT vendita Onda per una commessa.
 * Uso: php scripts\check_ddt_commessa.php 0067164-26
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ordine;

$commessa = $argv[1] ?? null;
if (!$commessa) {
    echo "Uso: php scripts\\check_ddt_commessa.php <commessa>\n";
    echo "Esempio: php scripts\\check_ddt_commessa.php 0067164-26\n";
    exit(1);
}

$ordini = Ordine::where('commessa', $commessa)
    ->select('id', 'commessa', 'cod_art', 'descrizione', 'qta_richiesta',
             'numero_ddt_vendita', 'qta_ddt_vendita', 'vettore_ddt', 'cliente_nome')
    ->orderBy('cod_art')
    ->get();

if ($ordini->isEmpty()) {
    echo "Nessun ordine trovato per commessa: $commessa\n";
    exit(1);
}

echo "===========================================================\n";
echo "COMMESSA: $commessa\n";
echo "Cliente: " . ($ordini->first()->cliente_nome ?? '-') . "\n";
echo "Articoli totali: " . $ordini->count() . "\n";
echo "===========================================================\n\n";

$qtaTot = 0;
$qtaSped = 0;
$ddtUnici = [];

foreach ($ordini as $i => $o) {
    $qta = (int) $o->qta_richiesta;
    $qtaDdt = (int) $o->qta_ddt_vendita;
    $qtaTot += $qta;
    $qtaSped += $qtaDdt;
    if ($o->numero_ddt_vendita) {
        $ddtUnici[$o->numero_ddt_vendita] = ($ddtUnici[$o->numero_ddt_vendita] ?? 0) + $qtaDdt;
    }
    $stato = $qtaDdt >= $qta ? '[SPED]' : ($qtaDdt > 0 ? '[PARZ]' : '[ATT ]');

    printf("%s #%d  cod_art: %s\n", $stato, $i + 1, $o->cod_art ?: '-');
    printf("       descr:   %s\n", mb_substr($o->descrizione ?? '-', 0, 100));
    printf("       qta:     %s richiesta | %s spedita\n",
        number_format($qta, 0, ',', '.'),
        number_format($qtaDdt, 0, ',', '.'));
    printf("       DDT:     %s  vettore: %s\n",
        $o->numero_ddt_vendita ?: '(non spedito)',
        $o->vettore_ddt ?: '-');
    echo "\n";
}

echo "===========================================================\n";
echo "RIEPILOGO COMMESSA $commessa\n";
echo "  Qta richiesta totale: " . number_format($qtaTot, 0, ',', '.') . "\n";
echo "  Qta spedita totale:   " . number_format($qtaSped, 0, ',', '.') . "\n";
echo "  Differenza residua:   " . number_format($qtaTot - $qtaSped, 0, ',', '.') . "\n";
echo "  DDT unici emessi:     " . count($ddtUnici) . "\n";
foreach ($ddtUnici as $ddt => $qta) {
    echo "    - DDT $ddt: " . number_format($qta, 0, ',', '.') . " pz\n";
}
echo "===========================================================\n";

// Articoli inclusi nello STESSO DDT da altre commesse (consolidamento)
foreach ($ddtUnici as $ddt => $_) {
    $altriNelDDT = Ordine::where('numero_ddt_vendita', $ddt)
        ->where('commessa', '!=', $commessa)
        ->select('commessa', 'cod_art', 'descrizione', 'qta_ddt_vendita')
        ->get();
    if ($altriNelDDT->isNotEmpty()) {
        echo "\nDDT $ddt include anche articoli da altre commesse:\n";
        foreach ($altriNelDDT as $a) {
            printf("  - commessa %s | cod %s | qta %s | %s\n",
                $a->commessa,
                $a->cod_art ?: '-',
                number_format((int) $a->qta_ddt_vendita, 0, ',', '.'),
                mb_substr($a->descrizione ?? '-', 0, 60));
        }
    }
}
