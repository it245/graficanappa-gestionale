<?php
/**
 * Simulazione v2: match word-boundary, cliché più lungo vince.
 *
 * Strategia:
 *  - Normalizza descrizioni (uppercase, collassa spazi, rimuove accenti/apostrofi)
 *  - Ordina cliché per lunghezza DESC (il più specifico tenta match per primo)
 *  - Match = la desc MES contiene la desc cliché come "parola intera"
 *    (preceduta/seguita da inizio-stringa, spazio, o punteggiatura)
 *  - Primo cliché che matcha vince → "LES NOISETTES X" batte "NOISETTES X"
 *
 * Uso:
 *   php simula_collegamento_cliche_v2.php "C:\condivisa\mes\file.xlsx"
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

$path = $argv[1] ?? 'C:\\condivisa\\mes\\Numerazione_Clice_2000_2300 1 (1) (1).xlsx';
if (!file_exists($path)) die("File non trovato: $path\n");

error_reporting(E_ALL & ~E_DEPRECATED);

function normDesc(?string $s): string {
    $s = (string) $s;
    $s = mb_strtoupper($s, 'UTF-8');
    $s = str_replace(["'", "'", '"', '"', '`', '´', '.', ','], ' ', $s);
    $s = strtr($s, [
        'À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A',
        'È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E',
        'Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I',
        'Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O',
        'Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'U',
    ]);
    // collassa tutto ciò che non è alfanumerico o spazio in spazio
    $s = preg_replace('/[^A-Z0-9 ]+/u', ' ', $s);
    $s = preg_replace('/\s+/u', ' ', $s);
    return trim($s);
}

// ==================== STEP 1: leggi Excel ====================
echo "=== STEP 1: lettura Excel ===\n";
$ss = IOFactory::load($path);
$rows = $ss->getActiveSheet()->toArray(null, false, false, true);

$cliche = []; // lista: ['cliche'=>N, 'raw'=>descOrig, 'norm'=>descNorm, 'qta'=>, 'note'=>]
$first = true;
foreach ($rows as $r) {
    if ($first) { $first = false; continue; }
    $n = trim((string)($r['A'] ?? ''));
    $art = trim((string)($r['B'] ?? ''));
    if ($n === '' || $art === '') continue;
    $cliche[] = [
        'cliche' => $n,
        'raw'    => $art,
        'norm'   => normDesc($art),
        'qta'    => $r['C'] ?? null,
        'note'   => $r['D'] ?? null,
    ];
}
// Ordina per lunghezza norm DESC
usort($cliche, fn($a,$b) => mb_strlen($b['norm']) - mb_strlen($a['norm']));
echo "  Righe Excel: " . count($cliche) . "\n";

// ==================== STEP 2: query fasi MES stato 0-1 ====================
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
echo "\n=== STEP 3: matching word-boundary (prefer longest) ===\n";
$matched = [];
$unmatchedFasi = [];
$usati = [];

foreach ($fasi as $f) {
    $mesNorm = normDesc($f->descrizione);
    if ($mesNorm === '') { $unmatchedFasi[] = $f; continue; }

    // Trova cliché con norm presente in mesNorm come "parola intera"
    $found = null;
    foreach ($cliche as $c) {
        if ($c['norm'] === '') continue;
        $pattern = '/(?:^|\s)' . preg_quote($c['norm'], '/') . '(?:\s|$)/u';
        if (preg_match($pattern, $mesNorm)) {
            $found = $c;
            break; // cliché ordinati per lunghezza desc → primo match è il più specifico
        }
    }

    if ($found) {
        $matched[] = ['f' => $f, 'c' => $found];
        $usati[$found['cliche']] = ($usati[$found['cliche']] ?? 0) + 1;
    } else {
        $unmatchedFasi[] = $f;
    }
}

$unmatchedExcel = array_filter($cliche, fn($c) => !isset($usati[$c['cliche']]));

echo "  MATCH:               " . count($matched) . "\n";
echo "  Fasi senza match:    " . count($unmatchedFasi) . "\n";
echo "  Cliché non usati:    " . count($unmatchedExcel) . " / " . count($cliche) . "\n";

// ==================== STEP 4: MATCH dettaglio ====================
echo "\n=== STEP 4: MATCH (primi 40) ===\n";
foreach (array_slice($matched, 0, 40) as $m) {
    $f = $m['f']; $c = $m['c'];
    printf("  [CL %4s] %-45s  ← %s (%s,%d) '%s'\n",
        $c['cliche'],
        mb_substr($c['raw'], 0, 45),
        $f->commessa, $f->fase, $f->stato,
        mb_substr($f->descrizione ?? '', 0, 50)
    );
}
if (count($matched) > 40) echo "  ... (+" . (count($matched)-40) . ")\n";

// Raggruppa fasi matchate per commessa (per vedere quante coperte)
$commesseMatch = [];
foreach ($matched as $m) $commesseMatch[$m['f']->commessa] = true;
$commesseUnmatched = [];
foreach ($unmatchedFasi as $f) $commesseUnmatched[$f->commessa] = true;
echo "\n  Commesse con almeno 1 match: " . count($commesseMatch) . "\n";
echo "  Commesse senza match:        " . count(array_diff_key($commesseUnmatched, $commesseMatch)) . "\n";

// ==================== STEP 5: fasi senza match (top descrizioni) ====================
$descUniche = [];
foreach ($unmatchedFasi as $f) {
    $k = normDesc($f->descrizione);
    if ($k !== '') $descUniche[$k] = ($descUniche[$k] ?? 0) + 1;
}
arsort($descUniche);
echo "\n=== STEP 5: top 25 descrizioni MES ancora senza match ===\n";
$i = 0;
foreach ($descUniche as $d => $cnt) {
    if ($i++ >= 25) break;
    printf("  %3dx  %s\n", $cnt, mb_substr($d, 0, 75));
}

// ==================== STEP 6: cliché non usati (esempi) ====================
echo "\n=== STEP 6: primi 25 cliché non usati ===\n";
$i = 0;
foreach ($unmatchedExcel as $c) {
    if ($i++ >= 25) break;
    printf("  [CL %s] %s\n", $c['cliche'], $c['raw']);
}

echo "\nDone. Nessuna modifica al DB.\n";
