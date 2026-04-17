<?php
/**
 * Simulazione v7: v6 + synonym map.
 *
 * Sinonimi applicati in normalizzazione (token → forma canonica):
 *   AST, AST., ASTUCCIO, ASTUCCI → AST
 *   VASSOIO, VASS               → VASSOIO
 *   SCATOLA, SCAT               → SCATOLA
 *   ...
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

$path = $argv[1] ?? 'C:\\condivisa\\mes\\Numerazione_Clice_2000_2300 1 (1) (1).xlsx';
if (!file_exists($path)) die("File non trovato: $path\n");
error_reporting(E_ALL & ~E_DEPRECATED);

// Synonym: token alternativo => forma canonica
const SYNONYM = [
    'ASTUCCIO'   => 'AST',
    'ASTUCCI'    => 'AST',
    'VASS'       => 'VASSOIO',
    'VASSOI'     => 'VASSOIO',
    'SCAT'       => 'SCATOLA',
    'COP'        => 'COPERCHIO',
    'FOND'       => 'FONDO',
    'CUBO'       => 'CUBO',
];

const STOPLIST = [
    // articoli/preposizioni
    'DI', 'LA', 'IL', 'E', 'DEL', 'DELLA', 'CON', 'AL', 'ALLA', 'DA',
    // categorie prodotto/parole neutre nel matching
    'NUANCE',
];

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

function tokenize(string $norm): array {
    $toks = preg_split('/\s+/u', trim($norm));
    $out = [];
    foreach ($toks as $t) {
        if ($t === '' || in_array($t, STOPLIST, true)) continue;
        $out[] = SYNONYM[$t] ?? $t;
    }
    return $out;
}

function expandExcelRow(string $art): array {
    if (strpos($art, ',') === false) return [$art];
    $parts = array_map('trim', explode(',', $art));
    $parts = array_values(array_filter($parts, fn($p) => $p !== ''));
    if (count($parts) < 2) return [$art];
    $first = $parts[0];
    $firstWords = preg_split('/\s+/u', $first);
    $second = $parts[1];
    $secondWords = preg_split('/\s+/u', $second);
    if (count($secondWords) < 1) return [$art];
    $idx = array_search($secondWords[0], $firstWords, true);
    if ($idx === false || $idx === 0) return [$art];
    $prefix = implode(' ', array_slice($firstWords, 0, $idx));
    if (mb_strlen($prefix) < 4) return [$art];
    $out = [$first];
    for ($i = 1; $i < count($parts); $i++) {
        $p = trim($parts[$i]);
        if ($p !== '') $out[] = $prefix . ' ' . $p;
    }
    return $out;
}

// ==================== STEP 1: Excel ====================
echo "=== STEP 1: Excel ===\n";
$ss = IOFactory::load($path);
$rows = $ss->getActiveSheet()->toArray(null, false, false, true);

$cliche = [];
$clicheByTokHash = []; // exact match via token hash
$first = true;
foreach ($rows as $r) {
    if ($first) { $first = false; continue; }
    $n = trim((string)($r['A'] ?? ''));
    $art = trim((string)($r['B'] ?? ''));
    if ($n === '' || $art === '') continue;
    foreach (expandExcelRow($art) as $v) {
        $norm = normCore($v);
        if ($norm === '') continue;
        $tokens = tokenize($norm);
        if (empty($tokens)) continue;
        $hash = implode(' ', (function($t) { sort($t); return $t; })($tokens));
        $entry = [
            'cliche'=>$n, 'raw'=>$art, 'variante'=>$v,
            'norm'=>$norm, 'tokens'=>$tokens, 'ntok'=>count($tokens),
            'hash'=>$hash,
        ];
        $cliche[] = $entry;
        if (!isset($clicheByTokHash[$hash])) $clicheByTokHash[$hash] = $entry;
    }
}
echo "  Varianti: " . count($cliche) . " (cliché distinti: " . count(array_unique(array_column($cliche, 'cliche'))) . ")\n";

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
$exact=0; $subset=0;
$matched=[]; $unmatched=[]; $usati=[];

foreach ($fasi as $f) {
    $raw = (string)($f->descrizione ?? '');
    $stripped = stripRumoreMes($raw);
    $mesNorm = normCore($stripped);
    $mesTokens = tokenize($mesNorm);
    $mesSet = array_flip($mesTokens);
    $mesHash = implode(' ', (function($t) { sort($t); return $t; })($mesTokens));

    $bestMatch = null; $bestScore = 0; $type = null;

    if (isset($clicheByTokHash[$mesHash])) {
        $bestMatch = $clicheByTokHash[$mesHash];
        $bestScore = $bestMatch['ntok'];
        $type = 'exact';
        $exact++;
    } else {
        foreach ($cliche as $c) {
            if ($c['ntok'] < 2) continue;
            $ok = true;
            foreach ($c['tokens'] as $t) {
                if (!isset($mesSet[$t])) { $ok = false; break; }
            }
            if ($ok && $c['ntok'] > $bestScore) {
                $bestMatch = $c; $bestScore = $c['ntok']; $type = 'subset';
            }
        }
        if ($bestMatch) $subset++;
    }

    if ($bestMatch) {
        $matched[] = ['f'=>$f, 'c'=>$bestMatch, 'type'=>$type, 'mesNorm'=>$mesNorm, 'score'=>$bestScore];
        $usati[$bestMatch['cliche']] = ($usati[$bestMatch['cliche']] ?? 0) + 1;
    } else {
        $unmatched[] = [$f, $raw, $mesNorm];
    }
}

echo "\n=== STEP 3: RISULTATI ===\n";
echo "  MATCH exact:  $exact\n";
echo "  MATCH subset: $subset\n";
echo "  TOTALE:       " . count($matched) . "\n";
echo "  Non match:    " . count($unmatched) . "\n";
echo "  Cliché usati: " . count($usati) . " / " . count(array_unique(array_column($cliche, 'cliche'))) . "\n";

echo "\n=== STEP 4: MATCH (primi 80) ===\n";
foreach (array_slice($matched, 0, 80) as $m) {
    printf("  [CL %4s][%-6s sc=%d] '%s' ← %s '%s'\n",
        $m['c']['cliche'], $m['type'], $m['score'],
        mb_substr($m['c']['variante'], 0, 35),
        $m['f']->commessa,
        mb_substr($m['mesNorm'], 0, 45)
    );
}
if (count($matched) > 80) echo "  ... (+" . (count($matched)-80) . ")\n";

arsort($usati);
echo "\n=== Match per cliché ===\n";
foreach ($usati as $cl => $cnt) echo "  CL $cl: $cnt fasi\n";

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

echo "\nDone.\n";
