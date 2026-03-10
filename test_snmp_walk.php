<?php
/**
 * SNMP Walk per scoprire tutti gli OID disponibili sulla Canon iPR V900
 */
$ip = '192.168.1.206';
$community = 'public';

echo "=== SNMP Walk Canon iPR V900 (via Fiery $ip) ===\n\n";

// 1. Walk dell'albero contatori Canon (.1.3.6.1.4.1.1602)
echo "--- Albero Canon (enterprise 1602) ---\n";
$results = @snmprealwalk($ip, $community, '.1.3.6.1.4.1.1602');
if ($results) {
    foreach ($results as $oid => $val) {
        echo "  $oid = $val\n";
    }
    echo "\nTotale OID Canon: " . count($results) . "\n";
} else {
    echo "  Nessun risultato o errore\n";
}

// 2. Walk albero Fiery/EFI (.1.3.6.1.4.1.2543 = EFI)
echo "\n--- Albero EFI/Fiery (enterprise 2543) ---\n";
$results2 = @snmprealwalk($ip, $community, '.1.3.6.1.4.1.2543');
if ($results2) {
    foreach ($results2 as $oid => $val) {
        // Tronca valori lunghi
        $short = strlen($val) > 100 ? substr($val, 0, 100) . '...' : $val;
        echo "  $oid = $short\n";
    }
    echo "\nTotale OID EFI: " . count($results2) . "\n";
} else {
    echo "  Nessun risultato\n";
}

// 3. Printer MIB standard (.1.3.6.1.2.1.43 = Printer-MIB)
echo "\n--- Printer MIB standard (43) ---\n";
$results3 = @snmprealwalk($ip, $community, '.1.3.6.1.2.1.43');
if ($results3) {
    foreach ($results3 as $oid => $val) {
        $short = strlen($val) > 100 ? substr($val, 0, 100) . '...' : $val;
        echo "  $oid = $short\n";
    }
    echo "\nTotale OID Printer-MIB: " . count($results3) . "\n";
} else {
    echo "  Nessun risultato\n";
}

// 4. Host Resources MIB (.1.3.6.1.2.1.25)
echo "\n--- Host Resources MIB (25) ---\n";
$results4 = @snmprealwalk($ip, $community, '.1.3.6.1.2.1.25');
if ($results4) {
    $count = 0;
    foreach ($results4 as $oid => $val) {
        $short = strlen($val) > 100 ? substr($val, 0, 100) . '...' : $val;
        echo "  $oid = $short\n";
        $count++;
        if ($count > 50) { echo "  ... (troncato, totale: " . count($results4) . ")\n"; break; }
    }
    echo "\nTotale OID Host-Resources: " . count($results4) . "\n";
} else {
    echo "  Nessun risultato\n";
}

echo "\n=== Fine walk ===\n";
