<?php
/**
 * Test lettura contatori Canon iPR V900 via SNMP (attraverso Fiery)
 */
$ip = '192.168.1.206';
$community = 'public';
$base = '.1.3.6.1.4.1.1602.1.11.1.3.1.4.';

$oids = [
    101 => 'Totale 1',
    112 => 'Nero / Grande formato',
    113 => 'Nero / Piccolo formato',
    122 => 'Colore / Grande formato',
    123 => 'Colore / Piccolo formato',
    501 => 'Scansioni',
    471 => 'Foglio lungo',
];

echo "=== Contatori Canon iPR V900 (via Fiery $ip) ===\n\n";

foreach ($oids as $id => $nome) {
    $val = @snmpget($ip, $community, $base . $id);
    if ($val === false) {
        echo sprintf("  %-30s : ERRORE\n", $nome);
    } else {
        // Estrai solo il numero dal formato "Counter32: 12345"
        $num = preg_replace('/^.*:\s*/', '', $val);
        echo sprintf("  %-30s : %s\n", $nome, number_format((int)$num, 0, ',', '.'));
    }
}

echo "\nLettura completata.\n";
