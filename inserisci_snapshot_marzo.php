<?php
/**
 * Inserisce snapshot SNMP fittizi del 01/03 e 31/03 basati sui dati reali della fattura SAE.
 * Questo allinea il report MES con la fattura SAE di marzo 2026.
 *
 * Letture SAE marzo 2026:
 *   B/N A4: 426 -> 905 (479 fogli)
 *   Colore A4: 4.782 -> 15.539 (10.757 fogli)
 *   B/N A3: 3.641 -> 7.543 (3.902 fogli)
 *   Colore A3: 79.309 -> 156.702 (77.393 fogli)
 *   Banner: 8.580 -> 12.920 (4.340 fogli)
 *
 * I contatori SNMP del nostro DB sono in "lati stampati" (duplex = 2 per foglio)
 * SAE invece e' in fogli, quindi moltiplichiamo per 2 per simulare i contatori SNMP grezzi.
 *
 * Eseguire sul server: php inserisci_snapshot_marzo.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
error_reporting(E_ALL & ~E_DEPRECATED);

use App\Models\ContatoreStampante;

// Letture SAE in FOGLI -> moltiplicate per 2 per simulare contatori SNMP (lati)
$inizio_01_03 = [
    'totale_1'       => (426 + 4782 + 3641 + 79309 + 8580) * 2,
    'nero_piccolo'   => 426 * 2,    // B/N A4
    'colore_piccolo' => 4782 * 2,   // Colore A4
    'nero_grande'    => 3641 * 2,   // B/N A3
    'colore_grande'  => 79309 * 2,  // Colore A3
    'foglio_lungo'   => 8580 * 2,   // Banner
    'scansioni'      => 0,
];

$fine_31_03 = [
    'totale_1'       => (905 + 15539 + 7543 + 156702 + 12920) * 2,
    'nero_piccolo'   => 905 * 2,
    'colore_piccolo' => 15539 * 2,
    'nero_grande'    => 7543 * 2,
    'colore_grande'  => 156702 * 2,
    'foglio_lungo'   => 12920 * 2,
    'scansioni'      => 0,
];

// Elimina eventuali snapshot esistenti del 01/03 e 31/03 (per evitare duplicati)
$eliminati = ContatoreStampante::where('stampante', 'Canon iPR V900')
    ->where(function ($q) {
        $q->whereDate('rilevato_at', '2026-03-01')
          ->orWhereDate('rilevato_at', '2026-03-31');
    })
    ->delete();

echo "Snapshot esistenti eliminati: $eliminati\n";

// Inserisci snapshot 01/03/2026 alle 08:00
$snap1 = ContatoreStampante::create(array_merge($inizio_01_03, [
    'stampante'   => 'Canon iPR V900',
    'ip'          => '192.168.1.206',
    'rilevato_at' => '2026-03-01 08:00:00',
]));
echo "Snapshot 01/03/2026 08:00 creato (ID: {$snap1->id})\n";
echo "  Totale: " . number_format($snap1->totale_1, 0, ',', '.') . " lati\n";

// Inserisci snapshot 31/03/2026 alle 23:59
$snap2 = ContatoreStampante::create(array_merge($fine_31_03, [
    'stampante'   => 'Canon iPR V900',
    'ip'          => '192.168.1.206',
    'rilevato_at' => '2026-03-31 23:59:00',
]));
echo "Snapshot 31/03/2026 23:59 creato (ID: {$snap2->id})\n";
echo "  Totale: " . number_format($snap2->totale_1, 0, ',', '.') . " lati\n";

// Verifica calcolo
echo "\n=== Differenza (in fogli, /2) ===\n";
$campi = ['nero_piccolo' => 'B/N A4', 'colore_piccolo' => 'Colore A4', 'nero_grande' => 'B/N A3', 'colore_grande' => 'Colore A3', 'foglio_lungo' => 'Banner'];
foreach ($campi as $k => $label) {
    $diff_lati = $fine_31_03[$k] - $inizio_01_03[$k];
    $fogli = $diff_lati / 2;
    echo "  $label: " . number_format($fogli, 0, ',', '.') . " fogli\n";
}

echo "\nCompletato!\n";
