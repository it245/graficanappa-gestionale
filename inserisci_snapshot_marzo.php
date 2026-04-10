<?php
/**
 * Inserisce snapshot SNMP del 01/03 e 31/03 basati sui dati reali della fattura SAE.
 * Allinea il report MES con la fattura SAE di marzo 2026.
 *
 * Eseguire sul server: php inserisci_snapshot_marzo.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
error_reporting(E_ALL & ~E_DEPRECATED);

use App\Models\ContatoreStampante;

// Letture SAE in FOGLI x 2 = lati (i contatori SNMP sono in lati stampati)
$inizio_01_03 = [
    'totale_1'       => (426 + 4782 + 3641 + 79309 + 8580) * 2,
    'nero_piccolo'   => 426 * 2,
    'colore_piccolo' => 4782 * 2,
    'nero_grande'    => 3641 * 2,
    'colore_grande'  => 79309 * 2,
    'foglio_lungo'   => 8580 * 2,
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

$eliminati = ContatoreStampante::where('stampante', 'Canon iPR V900')
    ->where(function ($q) {
        $q->whereDate('rilevato_at', '2026-03-01')
          ->orWhereDate('rilevato_at', '2026-03-31');
    })
    ->delete();

echo "Snapshot esistenti eliminati: $eliminati\n";

$snap1 = ContatoreStampante::create(array_merge($inizio_01_03, [
    'stampante'   => 'Canon iPR V900',
    'ip'          => '192.168.1.206',
    'rilevato_at' => '2026-03-01 08:00:00',
]));
echo "Snapshot 01/03/2026 08:00 creato (ID: {$snap1->id})\n";

$snap2 = ContatoreStampante::create(array_merge($fine_31_03, [
    'stampante'   => 'Canon iPR V900',
    'ip'          => '192.168.1.206',
    'rilevato_at' => '2026-03-31 23:59:00',
]));
echo "Snapshot 31/03/2026 23:59 creato (ID: {$snap2->id})\n";

echo "\nCompletato!\n";
