<?php
/**
 * Simulazione v5: riduce falsi positivi.
 *  - Match contains SOLO se variante ≥ 2 parole e ≥ 10 caratteri
 *  - Cliché mono-parola: solo match esatto
 *  - Expand-list conservativo: espande solo se ogni parte ≥ 2 parole
 *    oppure se le parti condividono almeno 1 parola con la prima
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

$path = $argv[1] ?? 'C:\\condivisa\\mes\\Numerazione_Clice_2000_2300 1 (1) (1).xlsx';
if (!file_exists($path)) die("File non trovato: $path\n");
error_reporting(E_ALL & ~E_DEPRECATED);

function stripAccenti(string $s): string {
    return strtr($s, [
        'À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A',
        'È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E',
        'Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I',
        'Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O',
        'Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'U',
    ]);
}

function normCore(string $s): string {
    $s = mb_strtoupper($s, 'UTF-8');
    $s = stripAccenti($s);
    $s = str_replace(["'", "'", '"', '"', '`', '´'], ' ', $s);
    $s = preg_replace('/[^A-Z0-9 ]+/u', ' ', $s);
    $s = preg_replace('/(\d+)\s+KG\b/u', '$1KG', $s);
    $s = preg_replace('/\s+/u', ' ', $s);
    return trim($s);
}

function stripRumoreMes(string $s): string {
    $s = mb_strtoupper($s, 'UTF-8');
    $s = stripAccenti($s);
    $s = preg_replace('/\([^)]*\)/u', ' ', $s);
    $s = preg_replace('/\bSTAMPA\b.*$/u', ' ', $s);
    $s = preg_replace('/\bFS\d+\b/u', ' ', $s);
    $s = preg_replace('/\bF\s*\.?\s*TO\s*[\d.,]+\s*X\s*[\d.,]+(\s*CM|\s*MM)?/u', ' ', $s);
    $s = preg_replace('/\bDA\s+\d+\s+PAG\w*/u', ' ', $s);
    $s = preg_replace('/\bDRIP\s*OFF\b/u', ' ', $s);
    $s = preg_replace('/\bLASTRINA\b.*$/u', ' ', $s);
    $s = preg_replace('/\b\d+\s*[\/X+]?\s*\d*\s*COLORI\b/u', ' ', $s);
    $s = preg_replace('/\bPANTONE?\s+\w+(\s+\d+)?/u', ' ', $s);
    return $s;
}

/**
 * Expand-list conservativo. Espande solo se possibile detectare prefix valido.
 * Ritorna [] se troppo ambiguo (skip espansione).
 */
function expandExcelRow(string $art): array {
    if (strpos($art, ',') === false) return [$art];
    $parts = array_map('trim', explode(',', $art));
    $parts = array_values(array_filter($parts, fn($p) => $p !== ''));
    if (count($parts) < 2) return [$art];

    $first = $parts[0];
    $firstWords = preg_split('/\s+/u', $first);

    // Detect prefix: le parole iniziali di first che NON compaiono in nessuna altra parte
    // Euristica: trova first word della seconda parte. Se presente in first, tutto prima = prefix.
    $second = $parts[1];
    $secondWords = preg_split('/\s+/u', $second);
    if (count($secondWords) < 1) return [$art];

    $idx = array_search($secondWords[0], $firstWords, true);
    if ($idx === false || $idx === 0) {
        // Nessun prefix detectable → ambiguo, skip
        return [$art]; // tratta solo prima riga
    }
    $prefix = implode(' ', array_slice($firstWords, 0, $idx));
    if (mb_strlen($prefix) < 4) return [$art]; // prefix troppo corto → skip

    // Verifica che tutte le parti (eccetto prima) abbiano almeno 1 parola non numerica
    $out = [$first];
    for ($i = 1; $i < count($parts); $i++) {
        $pWords = preg_split('/\s+/u', trim($parts[$i]));
        $pWords = array_filter($pWords, fn($w) => $w !== '' && !ctype_digit($w));
        if (count($pWords) < 1) continue;
        $out[] = $prefix . ' ' . trim($parts[$i]);
    }
    return $out;
}

// ==================== STEP 1: Excel ====================
echo "=== STEP 1: lettura + espansione ===\n";
$ss = IOFactory::load($path);
$rows = $ss->getActiveSheet()->toArray(null, false, false, true);

$cliche = [];          // tutte le varianti (per match)
$clicheByNorm = [];
$first = true;
$skipped = 0;
$monoParolaCount = 0;
foreach ($rows as $r) {
    if ($first) { $first = false; continue; }
    $n = trim((string)($r['A'] ?? ''));
    $art = trim((string)($r['B'] ?? ''));
    if ($n === '' || $art === '') continue;

    $varianti = expandExcelRow($art);
    foreach ($varianti as $v) {
        $norm = normCore($v);
        if ($norm === '') continue;
        $nWords = count(preg_split('/\s+/u', $norm));
        $entry = [
            'cliche'=>$n, 'raw'=>$art, 'variante'=>$v,
            'norm'=>$norm, 'nwords'=>$nWords,
            // Abilita match contains SOLO se ≥ 2 parole E ≥ 10 caratteri
            'contains_ok' => ($nWords >= 2 && mb_strlen($norm) >= 10),
        ];
        if ($nWords < 2) $monoParolaCount++;
        $cliche[] = $entry;
        if (!isset($clicheByNorm[$norm])) $clicheByNorm[$norm] = $entry;
    }
}
usort($cliche, fn($a,$b) => mb_strlen($b['norm']) - mb_strlen($a['norm']));
echo "  Varianti totali: " . count($cliche) . " (cliché distinti: " . count(array_unique(array_column($cliche, 'cliche'))) . ")\n";
echo "  Varianti mono-parola (no contains): $monoParolaCount\n";
echo "  Varianti valide per contains: " . count(array_filter($cliche, fn($c) => $c['contains_ok'])) . "\n";

// ==================== STEP 2: fasi MES ====================
echo "\n=== STEP 2: fasi MES stato 0-1 ===\n";
$fasi = DB::table('ordine_fasi as of')
    ->join('ordini as o', 'o.id', '=', 'of.ordine_id')
    ->whereIn('of.stato', [0, 1])
    ->whereNull('of.deleted_at')
    ->select('of.id as fase_id', 'of.fase', 'of.stato',
             'o.id as ordine_id', 'o.commessa', 'o.descrizione')
    ->get();
echo "  Fasi: " . count($fasi) . "\n";

// ==================== STEP 3: match ====================
$exact=0; $contains=0;
$matched=[]; $unmatched=[]; $usati=[];

foreach ($fasi as $f) {
    $raw = (string)($f->descrizione ?? '');
    $stripped = stripRumoreMes($raw);
    $mesNorm = normCore($stripped);
    if ($mesNorm === '') { $unmatched[] = [$f, $raw, '']; continue; }

    $found = null; $type = null;
    if (isset($clicheByNorm[$mesNorm])) {
        $found = $clicheByNorm[$mesNorm]; $type='exact'; $exact++;
    } else {
        foreach ($cliche as $c) {
            if (!$c['contains_ok']) continue;  // skip mono-parola/corti
            $pat = '/(?:^|\s)' . preg_quote($c['norm'], '/') . '(?:\s|$)/u';
            if (preg_match($pat, $mesNorm)) { $found=$c; $type='contains'; $contains++; break; }
        }
    }

    if ($found) {
        $matched[] = ['f'=>$f, 'c'=>$found, 'type'=>$type, 'mesNorm'=>$mesNorm];
        $usati[$found['cliche']] = ($usati[$found['cliche']] ?? 0) + 1;
    } else {
        $unmatched[] = [$f, $raw, $mesNorm];
    }
}

echo "\n=== STEP 3: RISULTATI ===\n";
echo "  MATCH esatti:    $exact\n";
echo "  MATCH contains:  $contains\n";
echo "  TOTALE:          " . count($matched) . "\n";
echo "  Non matchate:    " . count($unmatched) . "\n";
echo "  Cliché usati:    " . count($usati) . " / " . count(array_unique(array_column($cliche, 'cliche'))) . "\n";

echo "\n=== STEP 4: MATCH (primi 60) ===\n";
foreach (array_slice($matched, 0, 60) as $m) {
    printf("  [CL %4s][%-8s] '%s' ← %s '%s'\n",
        $m['c']['cliche'], $m['type'],
        mb_substr($m['c']['variante'], 0, 38),
        $m['f']->commessa,
        mb_substr($m['mesNorm'], 0, 42)
    );
}
if (count($matched) > 60) echo "  ... (+" . (count($matched)-60) . ")\n";

// Match aggregato per cliché
echo "\n=== Match per cliché ===\n";
arsort($usati);
foreach ($usati as $cl => $cnt) {
    echo "  CL $cl: $cnt fasi\n";
}

// STEP 5: unmatched
$descUniche = [];
foreach ($unmatched as $u) {
    [$f, $raw, $norm] = $u;
    if ($norm !== '') $descUniche[$norm] = ($descUniche[$norm] ?? 0) + 1;
}
arsort($descUniche);
echo "\n=== STEP 5: top 30 MES senza match ===\n";
$i = 0;
foreach ($descUniche as $d => $cnt) {
    if ($i++ >= 30) break;
    printf("  %3dx  %s\n", $cnt, mb_substr($d, 0, 75));
}

// STEP 6: cliché skippati da contains (mono-parola)
echo "\n=== STEP 6: cliché esclusi da contains (mono-parola, < 10 char) ===\n";
$esclusi = array_filter($cliche, fn($c) => !$c['contains_ok']);
$i = 0;
foreach ($esclusi as $c) {
    if ($i++ >= 20) break;
    printf("  [CL %s] '%s' (norm='%s')\n", $c['cliche'], $c['variante'], $c['norm']);
}
if (count($esclusi) > 20) echo "  ... (+" . (count($esclusi)-20) . ")\n";

echo "\nDone.\n";
