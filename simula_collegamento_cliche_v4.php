<?php
/**
 * Simulazione v4:
 *  - Strip rumore MES token-based (rimuove singoli token, non tronca)
 *  - Espansione liste Excel: "LES NOISETTES CLASSIC ROSA, CLASSIC ROSSO"
 *    → ["LES NOISETTES CLASSIC ROSA", "LES NOISETTES CLASSIC ROSSO"]
 *  - Match esatto dopo strip + expand
 *  - Fallback contains (prefer longest)
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

/**
 * Strip token-based rumore MES.
 * Rimuove SOLO i singoli token rumore, non tronca.
 */
function stripRumoreMes(string $s): string {
    $s = mb_strtoupper($s, 'UTF-8');
    $s = stripAccenti($s);

    // 1) parentesi + contenuto
    $s = preg_replace('/\([^)]*\)/u', ' ', $s);
    // 2) "STAMPA ..." fino a fine (o fino a nuova parentesi già rimossa)
    $s = preg_replace('/\bSTAMPA\b.*$/u', ' ', $s);
    // 3) codici fustella FS<cifre>
    $s = preg_replace('/\bFS\d+\b/u', ' ', $s);
    // 4) formato "F.to NNxNN" "F.to NN,N x NN" ecc.
    $s = preg_replace('/\bF\s*\.?\s*TO\s*[\d.,]+\s*X\s*[\d.,]+(\s*CM|\s*MM)?/u', ' ', $s);
    // 5) "DA N PAG" / "DA N PAGINE"
    $s = preg_replace('/\bDA\s+\d+\s+PAG\w*/u', ' ', $s);
    // 6) token "DRIP OFF"
    $s = preg_replace('/\bDRIP\s*OFF\b/u', ' ', $s);
    // 7) "LASTRINA ..." (ma strumento di produzione, non parte nome)
    $s = preg_replace('/\bLASTRINA\b.*$/u', ' ', $s);
    // 8) "4 COLORI" / "4/4 COLORI" / "PANTONE ..."
    $s = preg_replace('/\b\d+\s*[\/X+]?\s*\d*\s*COLORI\b/u', ' ', $s);
    $s = preg_replace('/\bPANTONE?\s+\w+(\s+\d+)?/u', ' ', $s);
    // 9) lettere isolate tipo "F" "L" dopo codice fustella rimosso (marker lato)
    //    conservativo: lascia così, non toccare

    return $s;
}

/**
 * Espande una voce Excel con liste virgolate in varianti complete.
 * Esempi:
 *   "LES NOISETTES CLASSIC ROSA, CLASSIC ROSSO, CLASSIC AZZURRO"
 *     → [ "LES NOISETTES CLASSIC ROSA", "LES NOISETTES CLASSIC ROSSO", "LES NOISETTES CLASSIC AZZURRO" ]
 *   "NOISETTES NUANCE BUORDEAUX, NUANCE NUDE, ACQUAMARINA, BLU"
 *     → best-effort: prefix = "NOISETTES", suffissi "NUANCE BUORDEAUX", "NUANCE NUDE", "ACQUAMARINA", "BLU"
 */
function expandExcelRow(string $art): array {
    if (strpos($art, ',') === false) return [$art];

    $parts = array_map('trim', explode(',', $art));
    $parts = array_values(array_filter($parts, fn($p) => $p !== ''));
    if (count($parts) < 2) return [$art];

    $first = $parts[0];
    $firstWords = preg_split('/\s+/u', $first);

    // Detect prefix: la parola dove il secondo item comincia
    $second = $parts[1];
    $secondFirstWord = strtok($second, ' ');

    $prefix = '';
    $idx = array_search($secondFirstWord, $firstWords, true);
    if ($idx !== false && $idx > 0) {
        $prefix = implode(' ', array_slice($firstWords, 0, $idx));
    }

    $out = [$first];
    for ($i = 1; $i < count($parts); $i++) {
        if ($prefix !== '') {
            $out[] = trim($prefix . ' ' . $parts[$i]);
        } else {
            // nessun prefix detectable → tieni il part come sta
            $out[] = $parts[$i];
        }
    }
    return $out;
}

// ==================== STEP 1: leggi + espandi Excel ====================
echo "=== STEP 1: lettura + espansione Excel ===\n";
$ss = IOFactory::load($path);
$rows = $ss->getActiveSheet()->toArray(null, false, false, true);

$cliche = [];
$clicheByNorm = [];
$first = true;
$espansi = 0;
foreach ($rows as $r) {
    if ($first) { $first = false; continue; }
    $n = trim((string)($r['A'] ?? ''));
    $art = trim((string)($r['B'] ?? ''));
    if ($n === '' || $art === '') continue;
    $varianti = expandExcelRow($art);
    if (count($varianti) > 1) $espansi++;
    foreach ($varianti as $v) {
        $norm = normCore($v);
        if ($norm === '') continue;
        $entry = ['cliche'=>$n, 'raw'=>$art, 'variante'=>$v, 'norm'=>$norm];
        $cliche[] = $entry;
        $clicheByNorm[$norm][] = $entry;
    }
}
usort($cliche, fn($a,$b) => mb_strlen($b['norm']) - mb_strlen($a['norm']));
echo "  Righe Excel (espansi): " . count($cliche) . " (totale cliché distinti " . count(array_unique(array_column($cliche, 'cliche'))) . ")\n";
echo "  Righe con lista espansa: $espansi\n";
echo "  Desc uniche norm: " . count($clicheByNorm) . "\n";

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
$matchedExact = 0; $matchedContains = 0;
$matched = []; $unmatched = []; $usati = [];

foreach ($fasi as $f) {
    $raw = (string)($f->descrizione ?? '');
    $stripped = stripRumoreMes($raw);
    $mesNorm = normCore($stripped);
    if ($mesNorm === '') { $unmatched[] = [$f, $raw, '']; continue; }

    $found = null; $matchType = null;
    if (isset($clicheByNorm[$mesNorm])) {
        $found = $clicheByNorm[$mesNorm][0]; $matchType = 'exact'; $matchedExact++;
    } else {
        foreach ($cliche as $c) {
            $pat = '/(?:^|\s)' . preg_quote($c['norm'], '/') . '(?:\s|$)/u';
            if (preg_match($pat, $mesNorm)) {
                $found = $c; $matchType = 'contains'; $matchedContains++;
                break;
            }
        }
    }

    if ($found) {
        $matched[] = ['f'=>$f, 'c'=>$found, 'type'=>$matchType, 'mesNorm'=>$mesNorm];
        $usati[$found['cliche']] = ($usati[$found['cliche']] ?? 0) + 1;
    } else {
        $unmatched[] = [$f, $raw, $mesNorm];
    }
}

echo "\n=== STEP 3: RISULTATI ===\n";
echo "  MATCH esatti:    $matchedExact\n";
echo "  MATCH contains:  $matchedContains\n";
echo "  TOTALE:          " . count($matched) . "\n";
echo "  Non matchate:    " . count($unmatched) . "\n";
echo "  Cliché usati:    " . count($usati) . " / " . count(array_unique(array_column($cliche, 'cliche'))) . "\n";

echo "\n=== STEP 4: MATCH (primi 50) ===\n";
foreach (array_slice($matched, 0, 50) as $m) {
    printf("  [CL %4s][%-8s] '%s'  ← %s | '%s'\n",
        $m['c']['cliche'], $m['type'],
        mb_substr($m['c']['variante'], 0, 38),
        $m['f']->commessa,
        mb_substr($m['mesNorm'], 0, 42)
    );
}
if (count($matched) > 50) echo "  ... (+" . (count($matched)-50) . ")\n";

// STEP 5: unmatched
$descUniche = [];
foreach ($unmatched as $u) {
    [$f, $raw, $norm] = $u;
    if ($norm !== '') $descUniche[$norm] = ($descUniche[$norm] ?? 0) + 1;
}
arsort($descUniche);
echo "\n=== STEP 5: top 30 MES post-strip senza match ===\n";
$i = 0;
foreach ($descUniche as $d => $cnt) {
    if ($i++ >= 30) break;
    printf("  %3dx  %s\n", $cnt, mb_substr($d, 0, 75));
}

// STEP 6: esempi strip
echo "\n=== STEP 6: esempi strip MES (20) ===\n";
$i = 0;
foreach ($fasi as $f) {
    if ($i++ >= 20) break;
    $raw = (string)($f->descrizione ?? '');
    $stripped = stripRumoreMes($raw);
    $norm = normCore($stripped);
    printf("  RAW : %s\n  NORM: %s\n", mb_substr($raw, 0, 85), mb_substr($norm, 0, 85));
}

// STEP 7: cliché espansi (esempi)
echo "\n=== STEP 7: primi 20 cliché espansi (liste virgolate) ===\n";
$shown = [];
foreach ($cliche as $c) {
    if ($c['raw'] !== $c['variante']) {
        $shown[$c['cliche']] = ($shown[$c['cliche']] ?? []);
        $shown[$c['cliche']][] = $c['variante'];
    }
}
$shown = array_slice($shown, 0, 20, true);
foreach ($shown as $num => $variants) {
    echo "  CL $num:\n";
    foreach ($variants as $v) echo "    → $v\n";
}

echo "\nDone.\n";
