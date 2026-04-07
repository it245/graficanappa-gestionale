<?php
// Prova il matching cliché su tutte le descrizioni Italiana Confetti nel MES
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;

// Carica EAN
$fileEan = 'C:\condivisa\mes\gestione_codici_a_barre.xlsx';
if (!file_exists($fileEan)) {
    // Prova percorso alternativo
    $fileEan = storage_path('app/clice/gestione_codici_a_barre.xlsx');
}
$ean = [];
if (file_exists($fileEan)) {
    $wb = IOFactory::load($fileEan);
    $ws = $wb->getActiveSheet();
    for ($row = 2; $row <= $ws->getHighestRow(); $row++) {
        $art = trim($ws->getCell('B' . $row)->getValue() ?? '');
        $cl = trim($ws->getCell('D' . $row)->getValue() ?? '');
        $sc = trim($ws->getCell('E' . $row)->getValue() ?? '');
        if ($art) $ean[] = ['articolo' => $art, 'clice' => $cl, 'scatola' => $sc];
    }
    echo "EAN caricati: " . count($ean) . "\n";
}

// Carica catalogo
$fileCat = 'C:\condivisa\mes\Numerazione_Clice.xlsx';
if (!file_exists($fileCat)) {
    $fileCat = storage_path('app/clice/Numerazione_Clice.xlsx');
}
$cat = [];
if (file_exists($fileCat)) {
    $wb = IOFactory::load($fileCat);
    $ws = $wb->getActiveSheet();
    for ($row = 2; $row <= $ws->getHighestRow(); $row++) {
        $num = trim($ws->getCell('A' . $row)->getValue() ?? '');
        $art = trim($ws->getCell('B' . $row)->getValue() ?? '');
        $sc = trim($ws->getCell('E' . $row)->getValue() ?? '');
        if ($num && $art) $cat[] = ['numero' => $num, 'articolo' => $art, 'scatola' => $sc];
    }
    echo "Catalogo caricati: " . count($cat) . "\n";
}

if (empty($ean) && empty($cat)) {
    echo "NOTA: file Excel non trovati. Uso solo DB se disponibile.\n";
}

// Descrizioni dal MES
$descrizioni = DB::table('ordini')
    ->where('cliente_nome', 'LIKE', '%ITALIANA CONFETTI%')
    ->whereNotNull('descrizione')
    ->select('commessa', 'descrizione')
    ->distinct()
    ->orderBy('descrizione')
    ->get();

echo "Descrizioni MES: {$descrizioni->count()}\n\n";

function pulisci($desc) {
    $d = strtolower($desc);
    $d = preg_split('/\b(stampa\s+\d|stampare|f\.to|plast\.|con\s+lastrina|carta\s+bianca|usare|fogli\s+bianchi|senza\s+stampa)\b/i', $d)[0];
    $d = preg_replace('/^(ast\.?\s*\d*\s*(kg|gr)?|scatola|vassoio|astuccio|coperchio|fondo|fascia|pack|shopper|sleeve|fondino|automontante|expo|taste\s*kit|vanity|finestra|bollini|cartoncini|cubo|cono|velina|biglietto|catalogo|copertina)\s*/i', '', $d);
    $d = preg_replace('/FS\d{3,5}[_\-]?\d*/i', '', $d);
    $d = preg_replace('/\d+\s*(kg|gr|g|pz|cm)\b\.?\s*/i', '', $d);
    $d = preg_replace('/\b(rev|resa|alro|singola)\b.*/i', '', $d);
    return trim(preg_replace('/\s+/', ' ', $d));
}

function pulisciEan($art) {
    $a = strtolower($art);
    $a = preg_replace('/^(ast\.?\s*|astuccio\s*|coperchio\s*|fondo\s*|fascia\s*)/i', '', $a);
    $a = preg_replace('/\d+\s*(gr|kg|g)\b\.?\s*/i', '', $a);
    $a = preg_replace('/\bcubotto\b|\bbag\b|\bcornet\b/i', '', $a);
    return trim(preg_replace('/\s+/', ' ', $a));
}

$matchati = 0;
$nonMatchati = 0;

echo str_pad('METODO', 6) . str_pad('CLICHE', 16) . str_pad('COMMESSA', 14) . "DESCRIZIONE\n";
echo str_repeat('-', 100) . "\n";

foreach ($descrizioni as $d) {
    $dp = pulisci($d->descrizione);
    $metodo = ''; $matchCl = ''; $matchSc = ''; $matchArt = '';

    if (strlen($dp) < 3) {
        echo str_pad('-', 6) . str_pad('-', 16) . str_pad($d->commessa, 14) . substr($d->descrizione, 0, 60) . "\n";
        $nonMatchati++;
        continue;
    }

    // M1: esatto nell'EAN
    foreach ($ean as $e) {
        if ($e['clice'] && strpos(pulisciEan($e['articolo']), $dp) !== false) {
            $metodo = 'M1'; $matchCl = $e['clice']; $matchSc = $e['scatola']; $matchArt = $e['articolo']; break;
        }
    }
    // M1: esatto nel catalogo
    if (!$metodo) {
        foreach ($cat as $c) {
            if (strpos(strtolower($c['articolo']), $dp) !== false) {
                $metodo = 'M1c'; $matchCl = $c['numero']; $matchSc = $c['scatola']; $matchArt = $c['articolo']; break;
            }
        }
    }

    // M2: linea+variante
    if (!$metodo) {
        $linee = ['enzo miccio','two milk','les noisettes','bon bon','amor goloso','maxtris','stellato','avola',
                   'nomi ','nome ','lettere ','numeri ','classiche','diamond','tresor','crystal','twist','maxidea','maxmoji',
                   'dolce sposa','royal','sposa novella','pelatina','napoletanit','sicilianit','chocolate','choco'];
        $lt = ''; $var = $dp;
        foreach ($linee as $kw) {
            if (strpos($dp, $kw) !== false) {
                $lt = trim($kw);
                $pos = strpos($dp, $kw);
                $var = trim(substr($dp, $pos + strlen($kw)));
                $var = preg_replace('/^nuance[s]?\s*/i', '', $var);
                $var = trim($var);
                break;
            }
        }
        if ($lt && $var) {
            foreach ($ean as $e) {
                if (!$e['clice']) continue;
                $ep = pulisciEan($e['articolo']);
                if (strpos($ep, $lt) !== false && strpos($ep, $var) !== false) {
                    $metodo = 'M2'; $matchCl = $e['clice']; $matchSc = $e['scatola']; $matchArt = $e['articolo']; break;
                }
            }
        }
        if (!$metodo && $lt && $var) {
            foreach ($cat as $c) {
                $cl = strtolower($c['articolo']);
                if (strpos($cl, $var) !== false) {
                    $metodo = 'M2c'; $matchCl = $c['numero']; $matchSc = $c['scatola']; $matchArt = $c['articolo']; break;
                }
            }
        }
    }

    if ($metodo) {
        echo str_pad($metodo, 6) . str_pad("C{$matchCl} Sc.{$matchSc}", 16) . str_pad($d->commessa, 14) . substr($d->descrizione, 0, 60) . "\n";
        $matchati++;
    } else {
        echo str_pad('-', 6) . str_pad("[ {$dp} ]", 16) . str_pad($d->commessa, 14) . substr($d->descrizione, 0, 60) . "\n";
        $nonMatchati++;
    }
}

echo "\n=== RIEPILOGO ===\n";
echo "Matchati: {$matchati}\n";
echo "Non matchati: {$nonMatchati}\n";
echo "Totale: " . ($matchati + $nonMatchati) . "\n";
